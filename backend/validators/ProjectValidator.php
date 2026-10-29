<?php
declare(strict_types=1);

/**
 * QAFlow — Project validator.
 *
 * Field-level shape checks for project create and update requests. Project
 * key uniqueness and ownership rules are enforced by ProjectService; this
 * file only validates inputs.
 */

namespace QAFlow\Validators;

use QAFlow\Core\AppException;

final class ProjectValidator
{
    private const STATUSES = ['active', 'archived', 'on_hold'];

    /**
     * @param array<string,mixed> $data
     */
    public static function validateCreate(array $data): void
    {
        $v = new Validator();

        $v->field('key_code')
            ->required($data['key_code'] ?? null)
            ->string($data['key_code'] ?? null, 2, 16)
            ->custom(
                $data['key_code'] ?? null,
                static fn ($value) => is_string($value) && preg_match('/^[A-Za-z][A-Za-z0-9_\-]*$/', $value) === 1,
                'Key must start with a letter and contain only letters, digits, dashes, or underscores.'
            );

        $v->field('name')
            ->required($data['name'] ?? null)
            ->string($data['name'] ?? null, 2, 160);

        $v->field('description')
            ->nullable($data['description'] ?? null)
            ->string($data['description'] ?? null, null, 5000);

        $v->field('owner_id')
            ->required($data['owner_id'] ?? null)
            ->integer($data['owner_id'] ?? null, 1);

        $v->field('status')
            ->nullable($data['status'] ?? null)
            ->inArray($data['status'] ?? null, self::STATUSES);

        if ($v->hasErrors()) {
            throw AppException::validation('Validation failed.', $v->errors());
        }
    }

    /**
     * @param array<string,mixed> $data
     */
    public static function validateUpdate(array $data): void
    {
        $v = new Validator();

        if (array_key_exists('name', $data)) {
            $v->field('name')
                ->required($data['name'])
                ->string($data['name'], 2, 160);
        }

        if (array_key_exists('description', $data)) {
            $v->field('description')
                ->nullable($data['description'])
                ->string($data['description'], null, 5000);
        }

        if (array_key_exists('owner_id', $data)) {
            $v->field('owner_id')
                ->required($data['owner_id'])
                ->integer($data['owner_id'], 1);
        }

        if (array_key_exists('status', $data)) {
            $v->field('status')
                ->required($data['status'])
                ->inArray($data['status'], self::STATUSES);
        }

        if ($v->hasErrors()) {
            throw AppException::validation('Validation failed.', $v->errors());
        }
    }

    /**
     * @param array<string,mixed> $data
     */
    public static function validateMember(array $data): void
    {
        $v = new Validator();

        $v->field('user_id')
            ->required($data['user_id'] ?? null)
            ->integer($data['user_id'] ?? null, 1);

        $v->field('role')
            ->required($data['role'] ?? null)
            ->inArray($data['role'] ?? null, ['admin', 'qa_lead', 'qa_engineer', 'developer', 'viewer']);

        if ($v->hasErrors()) {
            throw AppException::validation('Validation failed.', $v->errors());
        }
    }
}