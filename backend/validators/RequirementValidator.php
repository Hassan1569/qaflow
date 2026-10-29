<?php
declare(strict_types=1);

/**
 * QAFlow — Requirement validator.
 *
 * Field-level shape checks for requirement create and update requests.
 * Priority and status enums are enforced here so services can trust the
 * input shape.
 */

namespace QAFlow\Validators;

use QAFlow\Core\AppException;

final class RequirementValidator
{
    private const PRIORITIES = ['critical', 'high', 'medium', 'low'];

    private const STATUSES = ['draft', 'in_review', 'approved', 'rejected', 'implemented', 'deprecated'];

    /**
     * @param array<string,mixed> $data
     */
    public static function validateCreate(array $data): void
    {
        $v = new Validator();

        $v->field('project_id')
            ->required($data['project_id'] ?? null)
            ->integer($data['project_id'] ?? null, 1);

        $v->field('title')
            ->required($data['title'] ?? null)
            ->string($data['title'] ?? null, 2, 200);

        $v->field('description')
            ->nullable($data['description'] ?? null)
            ->string($data['description'] ?? null, null, 10000);

        $v->field('priority')
            ->nullable($data['priority'] ?? null)
            ->inArray($data['priority'] ?? null, self::PRIORITIES);

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

        if (array_key_exists('title', $data)) {
            $v->field('title')
                ->required($data['title'])
                ->string($data['title'], 2, 200);
        }

        if (array_key_exists('description', $data)) {
            $v->field('description')
                ->nullable($data['description'])
                ->string($data['description'], null, 10000);
        }

        if (array_key_exists('priority', $data)) {
            $v->field('priority')
                ->required($data['priority'])
                ->inArray($data['priority'], self::PRIORITIES);
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
}