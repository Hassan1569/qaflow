<?php
declare(strict_types=1);

/**
 * QAFlow — Base validator.
 *
 * Provides a small, chainable rule set for field-level validation. Validators
 * are stateless collectors: you add rules, call `validate()`, and inspect the
 * error map. Services translate errors into AppException::validation().
 *
 * Rules are intentionally simple. Business rules belong in services; this
 * class enforces shape, type, range, and format only.
 */

namespace QAFlow\Validators;

final class Validator
{
    /** @var array<string,array<int,string>> */
    private array $errors = [];

    /**
     * The current field being inspected. Set by `field()`.
     */
    private string $currentField = '';

    /**
     * Scope the following rules to a field name.
     */
    public function field(string $name): self
    {
        $this->currentField = $name;
        return $this;
    }

    /**
     * @param array<string,mixed> $data
     */
    public function fromArray(array $data): self
    {
        // Currently unused directly; kept for symmetry with future iterations.
        unset($data);
        return $this;
    }

    // -----------------------------------------------------------------------
    // Rules
    // -----------------------------------------------------------------------

    /**
     * Value must be present and not empty after trimming.
     *
     * @param mixed $value
     */
    public function required($value, string $message = 'This field is required.'): self
    {
        if ($this->isEmpty($value)) {
            $this->addError($message);
        }
        return $this;
    }

    /**
     * Value may be empty, but if present must satisfy the surrounding rules.
     *
     * @param mixed $value
     */
    public function nullable($value): self
    {
        if ($this->isEmpty($value)) {
            // Signal to subsequent rules via a sentinel; simpler: mark as null.
            $this->nullableField = $this->currentField;
        }
        return $this;
    }

    private ?string $nullableField = null;

    /**
     * @param mixed $value
     */
    public function string($value, ?int $min = null, ?int $max = null): self
    {
        if ($this->skippable($value)) {
            return $this;
        }

        if (!is_string($value)) {
            $this->addError('Must be a string.');
            return $this;
        }

        $len = mb_strlen(trim($value));

        if ($min !== null && $len < $min) {
            $this->addError(sprintf('Must be at least %d characters.', $min));
        }
        if ($max !== null && $len > $max) {
            $this->addError(sprintf('Must be at most %d characters.', $max));
        }

        return $this;
    }

    /**
     * @param mixed $value
     */
    public function integer($value, ?int $min = null, ?int $max = null): self
    {
        if ($this->skippable($value)) {
            return $this;
        }

        if (!is_int($value) && !(is_string($value) && ctype_digit($value))) {
            $this->addError('Must be an integer.');
            return $this;
        }

        $n = (int) $value;
        if ($min !== null && $n < $min) {
            $this->addError(sprintf('Must be at least %d.', $min));
        }
        if ($max !== null && $n > $max) {
            $this->addError(sprintf('Must be at most %d.', $max));
        }

        return $this;
    }

    /**
     * @param mixed $value
     */
    public function numeric($value): self
    {
        if ($this->skippable($value)) {
            return $this;
        }
        if (!is_numeric($value)) {
            $this->addError('Must be numeric.');
        }
        return $this;
    }

    /**
     * @param mixed $value
     */
    public function boolean($value): self
    {
        if ($this->skippable($value)) {
            return $this;
        }
        if (!is_bool($value) && !in_array($value, [0, 1, '0', '1', 'true', 'false'], true)) {
            $this->addError('Must be a boolean.');
        }
        return $this;
    }

    /**
     * @param mixed $value
     */
    public function email($value): self
    {
        if ($this->skippable($value)) {
            return $this;
        }
        if (!is_string($value) || !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError('Must be a valid email address.');
        }
        return $this;
    }

    /**
     * Accepts a date in Y-m-d format.
     *
     * @param mixed $value
     */
    public function date($value): self
    {
        if ($this->skippable($value)) {
            return $this;
        }
        if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            $this->addError('Must be a valid date (YYYY-MM-DD).');
            return $this;
        }
        [$y, $m, $d] = array_map('intval', explode('-', $value));
        if (!checkdate($m, $d, $y)) {
            $this->addError('Must be a valid date.');
        }
        return $this;
    }

    /**
     * Value must be one of the allowed options.
     *
     * @param mixed              $value
     * @param array<int,string>  $allowed
     */
    public function inArray($value, array $allowed): self
    {
        if ($this->skippable($value)) {
            return $this;
        }
        if (!in_array($value, $allowed, true)) {
            $this->addError('Invalid option.');
        }
        return $this;
    }

    /**
     * Custom rule for one-off checks. Return false to add an error.
     *
     * @param mixed    $value
     * @param callable $fn
     */
    public function custom($value, callable $fn, string $message = 'Invalid value.'): self
    {
        if ($this->skippable($value)) {
            return $this;
        }
        if (!$fn($value)) {
            $this->addError($message);
        }
        return $this;
    }

    /**
     * Value must be an array.
     *
     * @param mixed $value
     */
    public function array($value): self
    {
        if ($this->skippable($value)) {
            return $this;
        }
        if (!is_array($value)) {
            $this->addError('Must be a list.');
        }
        return $this;
    }

    // -----------------------------------------------------------------------
    // Output
    // -----------------------------------------------------------------------

    /**
     * @return array<string,array<int,string>>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    public function hasErrors(): bool
    {
        return $this->errors !== [];
    }

    public function pass(): bool
    {
        return $this->errors === [];
    }

    /**
     * Throw an AppException::validation if any field failed. Call this in the
     * service before persisting.
     */
    public function throwIfFails(string $message = 'Validation failed.'): void
    {
        if ($this->hasErrors()) {
            throw \QAFlow\Core\AppException::validation($message, $this->errors);
        }
    }

    // -----------------------------------------------------------------------
    // Internals
    // -----------------------------------------------------------------------

    private function addError(string $message): void
    {
        $field = $this->currentField === '' ? '_' : $this->currentField;
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        if (!in_array($message, $this->errors[$field], true)) {
            $this->errors[$field][] = $message;
        }
    }

    /**
     * @param mixed $value
     */
    private function isEmpty($value): bool
    {
        if ($value === null) {
            return true;
        }
        if (is_string($value) && trim($value) === '') {
            return true;
        }
        if (is_array($value) && $value === []) {
            return true;
        }
        return false;
    }

    /**
     * Rules invoked after `nullable()` skip if the value is empty. This keeps
     * optional fields usable without repeating null checks.
     *
     * @param mixed $value
     */
    private function skippable($value): bool
    {
        if ($this->nullableField !== null && $this->nullableField === $this->currentField) {
            if ($this->isEmpty($value)) {
                return true;
            }
        }
        return false;
    }
}