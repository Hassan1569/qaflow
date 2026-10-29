<?php
declare(strict_types=1);

/**
 * QAFlow — Test case validator.
 *
 * Field-level shape checks for test case create and update requests. Test
 * steps are validated structurally (each step needs an action); the service
 * replaces the stored steps wholesale on update.
 */

namespace QAFlow\Validators;

use QAFlow\Core\AppException;

final class TestCaseValidator
{
    private const PRIORITIES = ['critical', 'high', 'medium', 'low'];

    private const SEVERITIES = ['critical', 'high', 'medium', 'low'];

    private const TYPES = [
        'functional', 'regression', 'smoke', 'sanity', 'integration',
        'ui', 'api', 'security', 'performance', 'compatibility',
    ];

    private const AUTOMATION = ['manual', 'automated', 'planned'];

    /**
     * @param array<string,mixed> $data
     */
    public static function validateCreate(array $data): void
    {
        $v = new Validator();

        $v->field('project_id')
            ->required($data['project_id'] ?? null)
            ->integer($data['project_id'] ?? null, 1);

        $v->field('suite_id')
            ->nullable($data['suite_id'] ?? null)
            ->integer($data['suite_id'] ?? null, 1);

        $v->field('title')
            ->required($data['title'] ?? null)
            ->string($data['title'] ?? null, 2, 220);

        $v->field('description')
            ->nullable($data['description'] ?? null)
            ->string($data['description'] ?? null, null, 10000);

        $v->field('preconditions')
            ->nullable($data['preconditions'] ?? null)
            ->string($data['preconditions'] ?? null, null, 10000);

        $v->field('priority')
            ->nullable($data['priority'] ?? null)
            ->inArray($data['priority'] ?? null, self::PRIORITIES);

        $v->field('severity')
            ->nullable($data['severity'] ?? null)
            ->inArray($data['severity'] ?? null, self::SEVERITIES);

        $v->field('test_type')
            ->nullable($data['test_type'] ?? null)
            ->inArray($data['test_type'] ?? null, self::TYPES);

        $v->field('automation_status')
            ->nullable($data['automation_status'] ?? null)
            ->inArray($data['automation_status'] ?? null, self::AUTOMATION);

        self::validateSteps($data['steps'] ?? null, $v);

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

        if (array_key_exists('suite_id', $data)) {
            $v->field('suite_id')
                ->nullable($data['suite_id'])
                ->integer($data['suite_id'], 1);
        }

        if (array_key_exists('title', $data)) {
            $v->field('title')
                ->required($data['title'])
                ->string($data['title'], 2, 220);
        }

        if (array_key_exists('description', $data)) {
            $v->field('description')
                ->nullable($data['description'])
                ->string($data['description'], null, 10000);
        }

        if (array_key_exists('preconditions', $data)) {
            $v->field('preconditions')
                ->nullable($data['preconditions'])
                ->string($data['preconditions'], null, 10000);
        }

        if (array_key_exists('priority', $data)) {
            $v->field('priority')
                ->required($data['priority'])
                ->inArray($data['priority'], self::PRIORITIES);
        }

        if (array_key_exists('severity', $data)) {
            $v->field('severity')
                ->required($data['severity'])
                ->inArray($data['severity'], self::SEVERITIES);
        }

        if (array_key_exists('test_type', $data)) {
            $v->field('test_type')
                ->required($data['test_type'])
                ->inArray($data['test_type'], self::TYPES);
        }

        if (array_key_exists('automation_status', $data)) {
            $v->field('automation_status')
                ->required($data['automation_status'])
                ->inArray($data['automation_status'], self::AUTOMATION);
        }

        if (array_key_exists('steps', $data)) {
            self::validateSteps($data['steps'], $v);
        }

        if ($v->hasErrors()) {
            throw AppException::validation('Validation failed.', $v->errors());
        }
    }

    /**
     * @param mixed $steps
     */
    private static function validateSteps($steps, Validator $v): void
    {
        if ($steps === null) {
            return;
        }

        if (!is_array($steps)) {
            $v->field('steps')->custom(null, static fn () => false, 'Steps must be a list.');
            return;
        }

        if ($steps === []) {
            return;
        }

        foreach ($steps as $index => $step) {
            $n = $index + 1;

            if (!is_array($step)) {
                $v->field('steps')->custom(null, static fn () => false, "Step $n must be an object.");
                continue;
            }

            $action = $step['action'] ?? null;
            if (!is_string($action) || trim($action) === '') {
                $v->field('steps')->custom(null, static fn () => false, "Step $n: action is required.");
            } elseif (mb_strlen($action) > 5000) {
                $v->field('steps')->custom(null, static fn () => false, "Step $n: action is too long.");
            }

            $expected = $step['expected_result'] ?? null;
            if ($expected !== null && !is_string($expected)) {
                $v->field('steps')->custom(null, static fn () => false, "Step $n: expected result must be text.");
            } elseif (is_string($expected) && mb_strlen($expected) > 5000) {
                $v->field('steps')->custom(null, static fn () => false, "Step $n: expected result is too long.");
            }
        }
    }
}