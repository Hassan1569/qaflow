<?php
declare(strict_types=1);

/**
 * QAFlow — Defect model.
 *
 * Wraps a row from the defects table. Severity and priority are distinct
 * concepts; the model exposes separate labels for both.
 */

namespace QAFlow\Models;

final class Defect extends BaseModel
{
    /** @var array<string,string> */
    protected array $casts = [
        'id'             => 'int',
        'project_id'     => 'int',
        'test_case_id'   => 'int',
        'requirement_id' => 'int',
        'test_result_id' => 'int',
        'environment_id' => 'int',
        'reporter_id'    => 'int',
        'assignee_id'    => 'int',
    ];

    public function code(): string
    {
        return (string) $this->get('code', '');
    }

    public function title(): string
    {
        return (string) $this->get('title', '');
    }

    public function severity(): string
    {
        return (string) $this->get('severity', 'medium');
    }

    public function priority(): string
    {
        return (string) $this->get('priority', 'medium');
    }

    public function status(): string
    {
        return (string) $this->get('status', 'open');
    }

    public function severityLabel(): string
    {
        return ucfirst($this->severity());
    }

    public function priorityLabel(): string
    {
        return ucfirst($this->priority());
    }

    public function statusLabel(): string
    {
        return match ($this->status()) {
            'open'        => 'Open',
            'assigned'    => 'Assigned',
            'in_progress' => 'In Progress',
            'fixed'       => 'Fixed',
            'retest'      => 'Retest',
            'verified'    => 'Verified',
            'closed'      => 'Closed',
            'reopened'    => 'Reopened',
            'rejected'    => 'Rejected',
            'duplicate'   => 'Duplicate',
            default       => ucfirst(str_replace('_', ' ', $this->status())),
        };
    }

    public function isOpen(): bool
    {
        return !in_array($this->status(), ['closed', 'verified', 'rejected', 'duplicate'], true);
    }

    public function isCritical(): bool
    {
        return $this->severity() === 'critical';
    }

    public function assigneeName(): string
    {
        return (string) $this->get('assignee_name', '');
    }

    public function reporterName(): string
    {
        return (string) $this->get('reporter_name', '');
    }

    public function environmentName(): string
    {
        return (string) $this->get('environment_name', '');
    }

    public function testCaseCode(): string
    {
        return (string) $this->get('test_case_code', '');
    }

    public function requirementCode(): string
    {
        return (string) $this->get('requirement_code', '');
    }

    public function displayCode(): string
    {
        return $this->code() . ' — ' . $this->title();
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        $out = parent::toArray();
        $out['severity_label'] = $this->severityLabel();
        $out['priority_label'] = $this->priorityLabel();
        $out['status_label']   = $this->statusLabel();
        $out['is_open']        = $this->isOpen();
        $out['display_code']   = $this->displayCode();
        return $out;
    }
}