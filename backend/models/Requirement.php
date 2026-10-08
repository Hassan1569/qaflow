<?php
declare(strict_types=1);

/**
 * QAFlow — Requirement model.
 *
 * Wraps a row from the requirements table. Adds derived status/priority
 * labels used by the UI and a coverage percentage on the traceability view.
 */

namespace QAFlow\Models;

final class Requirement extends BaseModel
{
    /** @var array<string,string> */
    protected array $casts = [
        'id'               => 'int',
        'project_id'       => 'int',
        'author_id'        => 'int',
        'test_case_count'  => 'int',
        'executed_count'   => 'int',
        'passed_count'     => 'int',
        'failed_count'     => 'int',
        'blocked_count'    => 'int',
        'defect_count'     => 'int',
    ];

    public function code(): string
    {
        return (string) $this->get('code', '');
    }

    public function title(): string
    {
        return (string) $this->get('title', '');
    }

    public function priority(): string
    {
        return (string) $this->get('priority', 'medium');
    }

    public function status(): string
    {
        return (string) $this->get('status', 'draft');
    }

    public function priorityLabel(): string
    {
        return ucfirst($this->priority());
    }

    public function statusLabel(): string
    {
        return match ($this->status()) {
            'draft'       => 'Draft',
            'in_review'   => 'In Review',
            'approved'    => 'Approved',
            'rejected'    => 'Rejected',
            'implemented' => 'Implemented',
            'deprecated'  => 'Deprecated',
            default       => ucfirst($this->status()),
        };
    }

    public function displayCode(): string
    {
        return $this->code() . ' — ' . $this->title();
    }

    /**
     * Coverage is 100% when at least one test case is linked, 0% otherwise.
     * Execution coverage and pass rate are separate metrics; see
     * architecture.md.
     */
    public function coverage(): float
    {
        return ((int) $this->get('test_case_count', 0)) > 0 ? 100.0 : 0.0;
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        $out = parent::toArray();
        $out['priority_label'] = $this->priorityLabel();
        $out['status_label']   = $this->statusLabel();
        $out['display_code']   = $this->displayCode();
        $out['coverage']       = $this->coverage();
        return $out;
    }
}