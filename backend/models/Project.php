<?php
declare(strict_types=1);

/**
 * QAFlow — Project model.
 *
 * Wraps a row from the projects table. Provides status helpers and the
 * label used by the UI for archived vs active projects.
 */

namespace QAFlow\Models;

final class Project extends BaseModel
{
    /** @var array<string,string> */
    protected array $casts = [
        'id'                 => 'int',
        'owner_id'           => 'int',
        'requirements_count' => 'int',
        'test_cases_count'   => 'int',
        'open_defects_count' => 'int',
    ];

    public function keyCode(): string
    {
        return (string) $this->get('key_code', '');
    }

    public function name(): string
    {
        return (string) $this->get('name', '');
    }

    public function status(): string
    {
        return (string) $this->get('status', 'active');
    }

    public function isActive(): bool
    {
        return $this->status() === 'active';
    }

    public function isArchived(): bool
    {
        return $this->status() === 'archived';
    }

    public function statusLabel(): string
    {
        return match ($this->status()) {
            'active'   => 'Active',
            'archived' => 'Archived',
            'on_hold'  => 'On Hold',
            default    => ucfirst($this->status()),
        };
    }

    public function ownerName(): string
    {
        return (string) $this->get('owner_name', '');
    }

    public function displayName(): string
    {
        $key = $this->keyCode();
        return $key === '' ? $this->name() : $key . ' — ' . $this->name();
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        $out = parent::toArray();
        $out['status_label'] = $this->statusLabel();
        $out['display_name'] = $this->displayName();
        return $out;
    }
}