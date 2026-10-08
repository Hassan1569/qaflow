<?php
declare(strict_types=1);

/**
 * QAFlow — Test run model.
 *
 * Wraps a row from the test_runs table. Provides status labels and progress
 * helpers. Counts are computed by the repository and passed in the row; the
 * model simply labels them for the UI.
 */

namespace QAFlow\Models;

final class TestRun extends BaseModel
{
    /** @var array<string,string> */
    protected array $casts = [
        'id'            => 'int',
        'project_id'    => 'int',
        'plan_id'       => 'int',
        'environment_id'=> 'int',
        'assigned_to'   => 'int',
        'created_by'    => 'int',
        'total_cases'   => 'int',
        'passed_cases'  => 'int',
        'failed_cases'  => 'int',
        'blocked_cases' => 'int',
    ];

    public function name(): string
    {
        return (string) $this->get('name', '');
    }

    public function status(): string
    {
        return (string) $this->get('status', 'not_started');
    }

    public function statusLabel(): string
    {
        return match ($this->status()) {
            'not_started' => 'Not Started',
            'in_progress' => 'In Progress',
            'completed'   => 'Completed',
            'aborted'     => 'Aborted',
            default       => ucfirst(str_replace('_', ' ', $this->status())),
        };
    }

    public function totalCases(): int
    {
        return (int) $this->get('total_cases', 0);
    }

    public function passedCases(): int
    {
        return (int) $this->get('passed_cases', 0);
    }

    public function failedCases(): int
    {
        return (int) $this->get('failed_cases', 0);
    }

    public function blockedCases(): int
    {
        return (int) $this->get('blocked_cases', 0);
    }

    public function notRunCases(): int
    {
        return max(0, $this->totalCases()
            - $this->passedCases()
            - $this->failedCases()
            - $this->blockedCases());
    }

    /**
     * Progress percentage based on executed cases (pass + fail + blocked).
     */
    public function progressPercent(): float
    {
        $total = $this->totalCases();
        if ($total === 0) {
            return 0.0;
        }

        $executed = $this->passedCases() + $this->failedCases() + $this->blockedCases();
        return round(($executed / $total) * 100, 2);
    }

    /**
     * Pass rate over executed cases (excludes blocked).
     */
    public function passRate(): float
    {
        $executed = $this->passedCases() + $this->failedCases();
        if ($executed === 0) {
            return 0.0;
        }

        return round(($this->passedCases() / $executed) * 100, 2);
    }

    public function environmentName(): string
    {
        return (string) $this->get('environment_name', '');
    }

    public function assignedName(): string
    {
        return (string) $this->get('assigned_name', '');
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        $out = parent::toArray();
        $out['status_label']     = $this->statusLabel();
        $out['not_run_cases']    = $this->notRunCases();
        $out['progress_percent'] = $this->progressPercent();
        $out['pass_rate']        = $this->passRate();
        return $out;
    }
}