<?php
declare(strict_types=1);

/**
 * QAFlow — Test plan validator.
 *
 * Field-level shape checks for test plan create and update requests. Date
 * ordering is enforced here (start <= end) so the service can trust the
 * shape.
 */

namespace QAFlow\Validators;

use QAFlow\Core\AppException;

final class TestPlanValidator
{
    private const STATUSES = ['draft', 'active', 'completed', 'archived'];

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

        $v->field('release_version')
            ->nullable($data['release_version'] ?? null)
            ->string($data['release_version'] ?? null, null, 60);

        $v->field('description')
            ->nullable($data['description'] ?? null)
            ->string($data['description'] ?? null, null, 10000);

        $v->field('scope')
            ->nullable($data['scope'] ?? null)
            ->string($data['scope'] ?? null, null, 10000);

        $v->field('status')
            ->nullable($data['status'] ?? null)
            ->inArray($data['status'] ?? null, self::STATUSES);

        $v->field('start_date')
            ->nullable($data['start_date'] ?? null)
            ->date($data['start_date'] ?? null);

        $v->field('end_date')
            ->nullable($data['end_date'] ?? null)
            ->date($data['end_date'] ?? null);

        $v->field('owner_id')
            ->required($data['owner_id'] ?? null)
            ->integer($data['owner_id'] ?? null, 1);

        self::validateDateOrder($data, $v);

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

        if (array_key_exists('release_version', $data)) {
            $v->field('release_version')
                ->nullable($data['release_version'])
                ->string($data['release_version'], null, 60);
        }

        if (array_key_exists('description', $data)) {
            $v->field('description')
                ->nullable($data['description'])
                ->string($data['description'], null, 10000);
        }

        if (array_key_exists('scope', $data)) {
            $v->field('scope')
                ->nullable($data['scope'])
                ->string($data['scope'], null, 10000);
        }

        if (array_key_exists('status', $data)) {
            $v->field('status')
                ->required($data['status'])
                ->inArray($data['status'], self::STATUSES);
        }

        if (array_key_exists('start_date', $data)) {
            $v->field('start_date')
                ->nullable($data['start_date'])
                ->date($data['start_date']);
        }

        if (array_key_exists('end_date', $data)) {
            $v->field('end_date')
                ->nullable($data['end_date'])
                ->date($data['end_date']);
        }

        if (array_key_exists('owner_id', $data)) {
            $v->field('owner_id')
                ->required($data['owner_id'])
                ->integer($data['owner_id'], 1);
        }

        if (array_key_exists('start_date', $data) && array_key_exists('end_date', $data)) {
            self::validateDateOrder($data, $v);
        }

        if ($v->hasErrors()) {
            throw AppException::validation('Validation failed.', $v->errors());
        }
    }

    /**
     * @param array<string,mixed> $data
     */
    private static function validateDateOrder(array $data, Validator $v): void
    {
        $start = $data['start_date'] ?? null;
        $end   = $data['end_date'] ?? null;

        if (is_string($start) && is_string($end) && $start !== '' && $end !== '') {
            if ($start > $end) {
                $v->field('end_date')->custom(null, static fn () => false, 'End date must be on or after start date.');
            }
        }
    }
}