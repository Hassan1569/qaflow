<?php
declare(strict_types=1);

/**
 * QAFlow — Attachment controller.
 *
 * HTTP boundary for attachments. Business logic (validation, storage) lives
 * in AttachmentService. Uploads are accepted via multipart/form-data with a
 * `file` field plus `entity_type` and `entity_id` metadata.
 */

namespace QAFlow\Controllers;

use QAFlow\Core\AppException;
use QAFlow\Helpers\Request;
use QAFlow\Helpers\Response;
use QAFlow\Services\AttachmentService;

final class AttachmentController
{
    private AttachmentService $attachments;

    public function __construct(AttachmentService $attachments)
    {
        $this->attachments = $attachments;
    }

    /**
     * POST /api/attachments
     *
     * Multipart form fields: file, entity_type, entity_id
     */
    public function store(Request $request): Response
    {
        $user = $this->requireUser($request);

        if (!isset($_FILES['file'])) {
            throw AppException::validation('Validation failed.', [
                'file' => ['No file was uploaded.'],
            ]);
        }

        $meta = [
            'entity_type' => (string) ($request->input('entity_type') ?? ''),
            'entity_id'   => (int) ($request->input('entity_id') ?? 0),
        ];

        $attachment = $this->attachments->store($_FILES['file'], $meta, $user['id']);

        return Response::created($attachment);
    }

    /**
     * GET /api/attachments/{id}
     *
     * Streams the file to the client with a Content-Disposition header. The
     * stored path never appears in the response body.
     */
    public function show(Request $request, array $params): Response
    {
        $this->requireUser($request);
        $id = (int) ($params['id'] ?? 0);

        $attachment = $this->attachments->find($id);
        if ($attachment === null) {
            throw AppException::notFound('Attachment not found.');
        }

        $path = $this->attachments->absolutePath($attachment);
        if ($path === null || !is_file($path)) {
            throw AppException::notFound('Attachment file is missing on disk.');
        }

        $mime     = (string) $attachment['mime_type'];
        $name     = (string) $attachment['original_name'];
        $size     = filesize($path);
        $inline   = str_starts_with($mime, 'image/') || $mime === 'application/pdf';

        $disposition = $inline ? 'inline' : 'attachment';
        $safeName    = preg_replace('/[\r\n"]/', '_', $name) ?? 'download';

        if (!headers_sent()) {
            http_response_code(200);
            header('Content-Type: ' . $mime);
            header('Content-Length: ' . ($size === false ? 0 : $size));
            header(sprintf('Content-Disposition: %s; filename="%s"', $disposition, $safeName));
            header('X-Content-Type-Options: nosniff');
            header('Cache-Control: private, max-age=0, no-store');
        }

        // Stream in chunks so large files do not blow up memory.
        $handle = @fopen($path, 'rb');
        if ($handle === false) {
            throw AppException::serverError('Failed to read attachment.');
        }

        while (!feof($handle)) {
            $chunk = fread($handle, 8192);
            if ($chunk === false) {
                break;
            }
            echo $chunk;
        }
        fclose($handle);

        // Signal the router to stop (we already emitted the body).
        return new Response(200, null, [], null);
    }

    /**
     * DELETE /api/attachments/{id}
     */
    public function destroy(Request $request, array $params): Response
    {
        $user = $this->requireUser($request);
        $id   = (int) ($params['id'] ?? 0);

        $this->attachments->delete($id, $user['id']);

        return Response::noContent();
    }

    /**
     * @return array{id:int,role:string}
     */
    private function requireUser(Request $request): array
    {
        $user = $request->user();
        if ($user === null) {
            throw AppException::unauthorized('Authentication required.');
        }

        return ['id' => (int) $user['id'], 'role' => (string) $user['role']];
    }
}