<?php
declare(strict_types=1);

/**
 * QAFlow — Defect validator.
 *
 * Field-level shape checks for defect create and update requests. Status and
 * severity/priority enums are enforced here so services can trust the input.
 */

namespace QAFlow\Validators;

use QAFlow\Core\AppException;

final class DefectValidator
{
    private const SEVERITIES = ['critical', 'high', 'medium', 'low'];

    private const PRIORITIES = ['high', 'medium', 'low'];

    private const STATUSES = [
        'open', 'assigned', 'in_progress', 'fixed', 'retest',
        'verified', 'closed', 'reopened', 'rejected', 'duplicate',
    ];

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
            ->string($data['title'] ?? null, 2, 220);

        $v->field('description')
            ->nullable($data['description'] ?? null)
            ->string($data['description'] ?? null, null, 20000);

        $v->field('expected_result')
            ->nullable($data['expected_result'] ?? null)
            ->string($data['expected_result'] ?? null, null, 10000);

        $v->field('actual_result')
            ->nullable($data['actual_result'] ?? null)
            ->string($data['actual_result'] ?? null, null, 10000);

        $v->field('severity')
            ->nullable($data['severity'] ?? null)
            ->inArray($data['severity'] ?? null, self::SEVERITIES);

        $v->field('priority')
            ->nullable($data['priority'] ?? null)
            ->inArray($data['priority'] ?? null, self::PRIORITIES);

        $v->field('status')
            ->nullable($data['status'] ?? null)
            ->inArray($data['status'] ?? null, self::STATUSES);

        $v->field('test_case_id')
            ->nullable($data['test_case_id'] ?? null)
            ->integer($data['test_case_id'] ?? null, 1);

        $v->field('requirement_id')
            ->nullable($data['requirement_id'] ?? null)
            ->integer($data['requirement_id'] ?? null, 1);

        $v->field('test_result_id')
            ->nullable($data['test_result_id'] ?? null)
            ->integer($data['test_result_id'] ?? null, 1);

        $v->field('environment_id')
            ->nullable($data['environment_id'] ?? null)
            ->integer($data['environment_id'] ?? null, 1);

        $v->field('assignee_id')
            ->nullable($data['assignee_id'] ?? null)
            ->integer($data['assignee_id'] ?? null, 1);

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
                ->string($data['title'], 2, 220);
        }

        if (array_key_exists('description', $data)) {
            $v->field('description')
                ->nullable($data['description'])
                ->string($data['description'], null, 20000);
        }

        if (array_key_exists('expected_result', $data)) {
            $v->field('expected_result')
                ->nullable($data['expected_result'])
                ->string($data['expected_result'], null, 10000);
        }

        if (array_key_exists('actual_result', $data)) {
            $v->field('actual_result')
                ->nullable($data['actual_result'])
                ->string($data['actual_result'], null, 10000);
        }

        if (array_key_exists('severity', $data)) {
            $v->field('severity')
                ->required($data['severity'])
                ->inArray($data['severity'], self::SEVERITIES);
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

        if (array_key_exists('assignee_id', $data)) {
            $v->field('assignee_id')
                ->nullable($data['assignee_id'])
                ->integer($data['assignee_id'], 1);
        }

        if ($v->hasErrors()) {
            throw AppException::validation('Validation failed.', $v->errors());
        }
    }

    /**
     * @param array<string,mixed> $data
     */
    public static function validateStatusChange(array $data): void
    {
        $v = new Validator();

        $v->field('status')
            ->required($data['status'] ?? null)
            ->inArray($data['status'] ?? null, self::STATUSES);

        if ($v->hasErrors()) {
            throw AppException::validation('Validation failed.', $v->errors());
        }
    }
}