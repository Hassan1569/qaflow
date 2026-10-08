<?php
declare(strict_types=1);

/**
 * QAFlow — Comment model.
 *
 * Wraps a row from the comments table. Comments are polymorphic; the model
 * exposes the owning entity type and id alongside the author metadata.
 * A comment is considered edited when updated_at > created_at.
 */

namespace QAFlow\Models;

final class Comment extends BaseModel
{
    /** @var array<string,string> */
    protected array $casts = [
        'id'         => 'int',
        'entity_id'  => 'int',
        'author_id'  => 'int',
    ];

    public function entityType(): string
    {
        return (string) $this->get('entity_type', '');
    }

    public function entityId(): int
    {
        return (int) $this->get('entity_id', 0);
    }

    public function content(): string
    {
        return (string) $this->get('content', '');
    }

    public function authorId(): int
    {
        return (int) $this->get('author_id', 0);
    }

    public function authorName(): string
    {
        return (string) $this->get('author_name', '');
    }

    public function authorRole(): string
    {
        return (string) $this->get('author_role', '');
    }

    /**
     * A comment is treated as edited when updated_at differs from created_at.
     */
    public function isEdited(): bool
    {
        $created = (string) $this->get('created_at', '');
        $updated = (string) $this->get('updated_at', '');

        if ($created === '' || $updated === '') {
            return false;
        }

        return $updated > $created;
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        $out = parent::toArray();
        $out['is_edited'] = $this->isEdited();
        return $out;
    }
}