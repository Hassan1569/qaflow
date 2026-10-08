<?php
declare(strict_types=1);

/**
 * QAFlow — Base model.
 *
 * Thin wrapper around an associative row from a repository. Models provide
 * typed accessors and `toArray()` for the API layer. They intentionally hold
 * no SQL and no business rules; that work belongs in repositories and
 * services respectively.
 *
 * Models are constructed with the raw DB row (already fetched with the right
 * column aliases) plus an optional list of hidden fields that must never be
 * serialized (e.g. password_hash).
 */

namespace QAFlow\Models;

use JsonSerializable;

abstract class BaseModel implements JsonSerializable
{
    /** @var array<string,mixed> */
    protected array $attributes;

    /**
     * Field names to strip from `toArray()` and `jsonSerialize()`.
     *
     * @var array<int,string>
     */
    protected array $hidden = [];

    /**
     * Cast rules: field => 'int' | 'float' | 'bool' | 'json'.
     *
     * @var array<string,string>
     */
    protected array $casts = [];

    /**
     * @param array<string,mixed> $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
    }

    /**
     * Build a model from a raw DB row. Returns null when the row is null.
     *
     * @param array<string,mixed>|null $row
     */
    public static function fromRow(?array $row): ?static
    {
        if ($row === null) {
            return null;
        }

        return new static($row);
    }

    /**
     * Build a list of models from an array of rows.
     *
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,static>
     */
    public static function fromRows(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            $out[] = new static($row);
        }
        return $out;
    }

    // -----------------------------------------------------------------------
    // Attribute access
    // -----------------------------------------------------------------------

    /**
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        if (!array_key_exists($key, $this->attributes)) {
            return $default;
        }

        return $this->cast($key, $this->attributes[$key]);
    }

    /**
     * @param mixed $value
     */
    public function set(string $key, $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->attributes);
    }

    public function id(): int
    {
        return (int) ($this->attributes['id'] ?? 0);
    }

    /**
     * @return array<string,mixed>
     */
    public function attributes(): array
    {
        return $this->attributes;
    }

    // -----------------------------------------------------------------------
    // Serialization
    // -----------------------------------------------------------------------

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        $out = [];

        foreach ($this->attributes as $key => $value) {
            if (in_array($key, $this->hidden, true)) {
                continue;
            }
            $out[$key] = $this->cast($key, $value);
        }

        return $out;
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    // -----------------------------------------------------------------------
    // Casting
    // -----------------------------------------------------------------------

    /**
     * @param mixed $value
     * @return mixed
     */
    protected function cast(string $key, $value)
    {
        if (!isset($this->casts[$key]) || $value === null) {
            return $value;
        }

        switch ($this->casts[$key]) {
            case 'int':
                return (int) $value;
            case 'float':
                return (float) $value;
            case 'bool':
                return (int) $value === 1 || $value === true;
            case 'json':
                if (is_array($value)) {
                    return $value;
                }
                if (is_string($value) && $value !== '') {
                    $decoded = json_decode($value, true);
                    return is_array($decoded) ? $decoded : [];
                }
                return [];
            default:
                return $value;
        }
    }
}