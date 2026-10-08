<?php
declare(strict_types=1);

/**
 * QAFlow — Test plan model.
 *
 * Wraps a row from the test_plans table. Provides status labels and a date
 * range helper used by list views.
 */

namespace QAFlow\Models;

final class TestPlan extends BaseModel
{
    /** @var array<string,string> */
    protected array $casts = [
        'id'        => 'int',
        'project_id'=> 'int',
        'owner_id'  => 'int',
        'run_count' => 'int',
    ];

    public function name(): string
    {
        return (string) $this->get('name', '');
    }

    public function releaseVersion(): string
    {
        return (string) $this->get('release_version', '');
    }

    public function status(): string
    {
        return (string) $this->get('status', 'draft');
    }

    public function statusLabel(): string
    {
        return match ($this->status()) {
            'draft'     => 'Draft',
            'active'    => 'Active',
            'completed' => 'Completed',
            'archived'  => 'Archived',
            default     => ucfirst($this->status()),
        };
    }

    public function ownerName(): string
    {
        return (string) $this->get('owner_name', '');
    }

    /**
     * Human-readable date range, e.g. "2026-09-01 → 2026-09-30".
     */
    public function dateRangeLabel(): string
    {
        $start = (string) $this->get('start_date', '');
        $end   = (string) $this->get('end_date', '');

        if ($start === '' && $end === '') {
            return 'Not scheduled';
        }
        if ($start !== '' && $end === '') {
            return 'From ' . $start;
        }
        if ($start === '' && $end !== '') {
            return 'Until ' . $end;
        }

        return $start . ' → ' . $end;
    }

    public function displayName(): string
    {
        $release = $this->releaseVersion();
        return $release === '' ? $this->name() : $this->name() . ' (' . $release . ')';
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        $out = parent::toArray();
        $out['status_label']     = $this->statusLabel();
        $out['date_range_label'] = $this->dateRangeLabel();
        $out['display_name']     = $this->displayName();
        return $out;
    }
}