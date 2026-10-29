<?php
declare(strict_types=1);

/**
 * QAFlow — Comment controller.
 *
 * HTTP boundary for comments. Comments are polymorphic: they can be attached
 * to any entity in the supported list (defects, test cases, requirements,
 * test runs, test plans).
 */

namespace QAFlow\Controllers;

use QAFlow\Core\AppException;
use QAFlow\Helpers\Request;
use QAFlow\Helpers\Response;
use QAFlow\Repositories\CommentRepository;

final class CommentController
{
    private CommentRepository $comments;

    public function __construct(CommentRepository $comments)
    {
        $this->comments = $comments;
    }

    /**
     * GET /api/comments
     *
     * Query params: entity_type, entity_id
     */
    public function index(Request $request): Response
    {
        $this->requireUser($request);

        $entityType = (string) ($request->query('entity_type') ?? '');
        $entityId   = (int) ($request->query('entity_id') ?? 0);

        $this->validateEntity($entityType, $entityId);

        return Response::json($this->comments->listForEntity($entityType, $entityId));
    }

    /**
     * POST /api/comments
     *
     * Body: { entity_type, entity_id, content }
     */
    public function store(Request $request): Response
    {
        $user = $this->requireUser($request);
        $data = $request->json();

        $entityType = (string) ($data['entity_type'] ?? '');
        $entityId   = (int) ($data['entity_id'] ?? 0);
        $content    = trim((string) ($data['content'] ?? ''));

        $this->validateEntity($entityType, $entityId);

        if ($content === '') {
            throw AppException::validation('Validation failed.', [
                'content' => ['Comment cannot be empty.'],
            ]);
        }

        if (mb_strlen($content) > 10000) {
            throw AppException::validation('Validation failed.', [
                'content' => ['Comment is too long.'],
            ]);
        }

        $id = $this->comments->create([
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'content'     => $content,
            'author_id'   => $user['id'],
        ]);

        $comment = $this->comments->findById($id);
        if ($comment === null) {
            throw AppException::serverError('Failed to load comment after create.');
        }

        return Response::created($comment);
    }

    private function validateEntity(string $type, int $id): void
    {
        if (!in_array($type, CommentRepository::ENTITY_TYPES, true)) {
            throw AppException::validation('Validation failed.', [
                'entity_type' => ['Invalid entity type.'],
            ]);
        }
        if ($id < 1) {
            throw AppException::validation('Validation failed.', [
                'entity_id' => ['Required.'],
            ]);
        }
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