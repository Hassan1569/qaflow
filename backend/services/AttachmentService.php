<?php
declare(strict_types=1);

/**
 * QAFlow — Attachment service.
 *
 * Handles file uploads, validation, and safe storage. Validates MIME type
 * (never trusts client-provided type), size, and generates a UUID filename.
 * Files are stored outside the web root.
 */

namespace QAFlow\Services;

use QAFlow\Config\Config;
use QAFlow\Core\AppException;
use QAFlow\Helpers\Uuid;
use QAFlow\Repositories\AttachmentRepository;

final class AttachmentService
{
    private AttachmentRepository $attachments;
    private ActivityService $activity;

    private string $uploadDir;
    private int $maxBytes;
    /** @var array<int,string> */
    private array $allowedMime;

    public function __construct(AttachmentRepository $attachments, ActivityService $activity)
    {
        $this->attachments = $attachments;
        $this->activity    = $activity;

        // Interpret relative paths against the backend root.
        $configured = Config::string('UPLOAD_DIR', 'storage/uploads');
        if (!preg_match('#^([A-Za-z]:|/)#', $configured)) {
            $backendRoot = dirname(__DIR__);
            $this->uploadDir = $backendRoot . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $configured);
        } else {
            $this->uploadDir = $configured;
        }

        $this->maxBytes    = Config::int('UPLOAD_MAX_BYTES', 10 * 1024 * 1024);
        $this->allowedMime = Config::list('UPLOAD_ALLOWED_MIME', [
            'image/png', 'image/jpeg', 'image/webp',
            'application/pdf', 'text/plain', 'text/csv', 'application/zip',
        ]);
    }

    /**
     * Store an uploaded file and record metadata.
     *
     * @param array<string,mixed> $file   The $_FILES entry for the upload.
     * @param array<string,mixed> $meta   Additional metadata: entity_type, entity_id.
     * @return array<string,mixed>
     */
    public function store(array $file, array $meta, int $userId): array
    {
        $entityType = (string) ($meta['entity_type'] ?? '');
        $entityId   = (int) ($meta['entity_id'] ?? 0);

        if (!in_array($entityType, AttachmentRepository::ENTITY_TYPES, true)) {
            throw AppException::validation('Validation failed.', [
                'entity_type' => ['Invalid entity type.'],
            ]);
        }

        if ($entityId < 1) {
            throw AppException::validation('Validation failed.', [
                'entity_id' => ['Entity is required.'],
            ]);
        }

        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw AppException::validation('Validation failed.', [
                'file' => ['No file was uploaded.'],
            ]);
        }

        if (!empty($file['error']) && (int) $file['error'] !== UPLOAD_ERR_OK) {
            throw AppException::validation('Validation failed.', [
                'file' => ['Upload failed.'],
            ]);
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > $this->maxBytes) {
            throw AppException::validation('Validation failed.', [
                'file' => [sprintf('File must be smaller than %d MB.', (int) ($this->maxBytes / 1024 / 1024))],
            ]);
        }

        $tmpPath      = (string) $file['tmp_name'];
        $originalName = (string) ($file['name'] ?? 'upload');
        $mime         = $this->detectMime($tmpPath);

        if (!in_array($mime, $this->allowedMime, true)) {
            throw AppException::validation('Validation failed.', [
                'file' => ['File type is not allowed.'],
            ]);
        }

        $storedName = Uuid::storedFilename($originalName);

        $now   = new \DateTimeImmutable();
        $year  = $now->format('Y');
        $month = $now->format('m');

        $targetDir = $this->uploadDir . DIRECTORY_SEPARATOR . $year . DIRECTORY_SEPARATOR . $month;
        if (!is_dir($targetDir)) {
            if (!@mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
                throw AppException::serverError('Failed to create upload directory.');
            }
        }

        $targetPath = $targetDir . DIRECTORY_SEPARATOR . $storedName;

        if (!move_uploaded_file($tmpPath, $targetPath)) {
            throw AppException::serverError('Failed to store uploaded file.');
        }

        // Relative path used by the repository. Always normalized with forward
        // slashes so it can be stored on any platform.
        $relativePath = str_replace(DIRECTORY_SEPARATOR, '/', $year . '/' . $month . '/' . $storedName);

        $id = $this->attachments->create([
            'entity_type'   => $entityType,
            'entity_id'     => $entityId,
            'original_name' => substr($originalName, 0, 255),
            'stored_path'   => $relativePath,
            'mime_type'     => $mime,
            'size_bytes'    => $size,
            'uploaded_by'   => $userId,
        ]);

        $this->activity->record(
            $userId,
            null,
            'attachment.uploaded',
            'attachment',
            $id,
            [
                'entity_type' => $entityType,
                'entity_id'   => $entityId,
                'name'        => $originalName,
            ]
        );

        $attachment = $this->attachments->findById($id);
        if ($attachment === null) {
            throw AppException::serverError('Failed to load attachment after create.');
        }

        return $attachment;
    }

    /**
     * @return array<string,mixed>|null
     */
    public function find(int $id): ?array
    {
        return $this->attachments->findById($id);
    }

    /**
     * Return the absolute path for a stored attachment, or null when the
     * file is missing on disk.
     */
    public function absolutePath(array $attachment): ?string
    {
        $relative = str_replace('/', DIRECTORY_SEPARATOR, (string) ($attachment['stored_path'] ?? ''));
        if ($relative === '') {
            return null;
        }

        $full = $this->uploadDir . DIRECTORY_SEPARATOR . $relative;

        return is_file($full) ? $full : null;
    }

    public function delete(int $id, int $userId): void
    {
        $attachment = $this->attachments->findById($id);
        if ($attachment === null) {
            throw AppException::notFound('Attachment not found.');
        }

        $full = $this->absolutePath($attachment);
        if ($full !== null) {
            @unlink($full);
        }

        $this->attachments->delete($id);

        $this->activity->record($userId, null, 'attachment.deleted', 'attachment', $id, [
            'name' => (string) $attachment['original_name'],
        ]);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function listForEntity(string $entityType, int $entityId): array
    {
        return $this->attachments->listForEntity($entityType, $entityId);
    }

    /**
     * Detect MIME by inspecting the file, not the filename extension.
     */
    private function detectMime(string $path): string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($path);

        return is_string($mime) ? $mime : 'application/octet-stream';
    }
}