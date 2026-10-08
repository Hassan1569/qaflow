<?php
declare(strict_types=1);

/**
 * QAFlow — Test result model.
 *
 * Wraps a row from the test_results table plus the joined run_case and case
 * metadata when present. Exposes status labels and helpers used by the
 * execution UI.
 */

namespace QAFlow\Models;

final class TestResult extends BaseModel
{
    /** @var array<string,string> */
    protected array $casts = [
        'id'             => 'int',
        'test_run_case_id' => 'int',
        'executed_by'    => 'int',
        'is_automated'   => 'bool',
        'duration_ms'    => 'int',
    ];

    public function status(): string
    {
        return (string) $this->get('status', 'not_run');
    }

    public function isPass(): bool
    {
        return $this->status() === 'pass';
    }

    public function isFail(): bool
    {
        return $this->status() === 'fail';
    }

    public function isBlocked(): bool
    {
        return $this->status() === 'blocked';
    }

    public function isNotRun(): bool
    {
        return $this->status() === 'not_run';
    }

    public function statusLabel(): string
    {
        return match ($this->status()) {
            'pass'    => 'Pass',
            'fail'    => 'Fail',
            'blocked' => 'Blocked',
            'not_run' => 'Not Run',
            default   => ucfirst(str_replace('_', ' ', $this->status())),
        };
    }

    public function isAutomated(): bool
    {
        return (bool) $this->get('is_automated', false);
    }

    public function durationMs(): ?int
    {
        $d = $this->get('duration_ms');
        return $d === null ? null : (int) $d;
    }

    /**
     * Human-readable duration, e.g. "1.2s" or "3m 05s". Null when unknown.
     */
    public function durationLabel(): ?string
    {
        $ms = $this->durationMs();
        if ($ms === null) {
            return null;
        }

        if ($ms < 1000) {
            return $ms . 'ms';
        }

        $seconds = $ms / 1000;
        if ($seconds < 60) {
            return number_format($seconds, 1) . 's';
        }

        $minutes = (int) floor($seconds / 60);
        $secs    = (int) ($seconds - ($minutes * 60));

        return sprintf('%dm %02ds', $minutes, $secs);
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        $out = parent::toArray();
        $out['status_label']   = $this->statusLabel();
        $out['duration_label'] = $this->durationLabel();
        return $out;
    }
}