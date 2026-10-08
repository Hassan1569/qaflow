<?php
declare(strict_types=1);

/**
 * QAFlow — User model.
 *
 * Wraps a row from the users table. The password hash is hidden from
 * serialization so it can never leak through the API. A role label is derived
 * for display purposes.
 */

namespace QAFlow\Models;

final class User extends BaseModel
{
    /** @var array<int,string> */
    protected array $hidden = ['password_hash'];

    /** @var array<string,string> */
    protected array $casts = [
        'id'        => 'int',
        'is_active' => 'bool',
    ];

    public function name(): string
    {
        return (string) $this->get('name', '');
    }

    public function email(): string
    {
        return (string) $this->get('email', '');
    }

    public function role(): string
    {
        return (string) $this->get('role', 'viewer');
    }

    public function isActive(): bool
    {
        return (bool) $this->get('is_active', true);
    }

    public function isAdmin(): bool
    {
        return $this->role() === 'admin';
    }

    /**
     * Human-readable role label used by the UI.
     */
    public function roleLabel(): string
    {
        return match ($this->role()) {
            'admin'        => 'Administrator',
            'qa_lead'      => 'QA Lead',
            'qa_engineer'  => 'QA Engineer',
            'developer'    => 'Developer',
            'viewer'       => 'Viewer',
            default        => ucfirst($this->role()),
        };
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        $out = parent::toArray();
        $out['role_label'] = $this->roleLabel();
        return $out;
    }
}