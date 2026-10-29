<?php
declare(strict_types=1);

/**
 * QAFlow — Test run validator.
 *
 * Field-level shape checks for creating and updating test runs. Test case
 * selections must be a non-empty list of integer IDs. Environment and
 * assignee references are validated for type only; existence is checked in
 * the service.
 */

namespace QAFlow\Validators;

use QAFlow\Core\AppException;

final class TestRunValidator
{
    private const STATUSES = ['not_started', 'in_progress', 'completed', 'aborted'];

    /**
     * @param array<string,mixed> $data
     */
    public static function validateCreate(array $data): void
    {
        $v = new Validator();

        $v->field('project_id')
            ->required($data['project_id'] ?? null)
            ->integer($data['project_id'] ?? null, 1);

        $v->field('name')
            ->required($data['name'] ?? null)
            ->string($data['name'] ?? null, 2, 180);

        $v->field('description')
            ->nullable($data['description'] ?? null)
            ->string($data['description'] ?? null, null, 10000);

        $v->field('plan_id')
            ->nullable($data['plan_id'] ?? null)
            ->integer($data['plan_id'] ?? null, 1);

        $v->field('environment_id')
            ->nullable($data['environment_id'] ?? null)
            ->integer($data['environment_id'] ?? null, 1);

        $v->field('assigned_to')
            ->nullable($data['assigned_to'] ?? null)
            ->integer($data['assigned_to'] ?? null, 1);

        $v->field('test_case_ids')
            ->required($data['test_case_ids'] ?? null)
            ->array($data['test_case_ids'] ?? null)
            ->custom(
                $data['test_case_ids'] ?? null,
                static function ($list): bool {
                    if (!is_array($list) || $list === []) {
                        return false;
                    }
                    foreach ($list as $id) {
                        if (!is_numeric($id) || (int) $id < 1) {
                            return false;
                        }
                    }
                    return true;
                },
                'At least one test case is required.'
            );

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
                ->string($data['name'], 2, 180);
        }

        if (array_key_exists('description', $data)) {
            $v->field('description')
                ->nullable($data['description'])
                ->string($data['description'], null, 10000);
        }

        if (array_key_exists('status', $data)) {
            $v->field('status')
                ->required($data['status'])
                ->inArray($data['status'], self::STATUSES);
        }

        if (array_key_exists('environment_id', $data)) {
            $v->field('environment_id')
                ->nullable($data['environment_id'])
                ->integer($data['environment_id'], 1);
        }

        if (array_key_exists('assigned_to', $data)) {
            $v->field('assigned_to')
                ->nullable($data['assigned_to'])
                ->integer($data['assigned_to'], 1);
        }

        if ($v->hasErrors()) {
            throw AppException::validation('Validation failed.', $v->errors());
        }
    }

    /**
     * @param array<string,mixed> $data
     */
    public static function validateResult(array $data): void
    {
        $v = new Validator();

        $v->field('status')
            ->required($data['status'] ?? null)
            ->inArray($data['status'] ?? null, ['pass', 'fail', 'blocked', 'not_run']);

        $v->field('actual_result')
            ->nullable($data['actual_result'] ?? null)
            ->string($data['actual_result'] ?? null, null, 10000);

        $v->field('notes')
            ->nullable($data['notes'] ?? null)
            ->string($data['notes'] ?? null, null, 10000);

        if ($v->hasErrors()) {
            throw AppException::validation('Validation failed.', $v->errors());
        }
    }

    /**
     * @param array<string,mixed> $data
     */
    public static function validateStepResult(array $data): void
    {
        $v = new Validator();

        $v->field('status')
            ->required($data['status'] ?? null)
            ->inArray($data['status'] ?? null, ['pass', 'fail', 'blocked', 'not_run']);

        $v->field('actual_result')
            ->nullable($data['actual_result'] ?? null)
            ->string($data['actual_result'] ?? null, null, 5000);

        $v->field('notes')
            ->nullable($data['notes'] ?? null)
            ->string($data['notes'] ?? null, null, 5000);

        if ($v->hasErrors()) {
            throw AppException::validation('Validation failed.', $v->errors());
        }
    }
}