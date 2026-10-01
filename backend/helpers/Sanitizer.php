<?php
declare(strict_types=1);

/**
 * QAFlow — Input sanitization helpers.
 *
 * These helpers normalize incoming values so validators and repositories can
 * work with consistent types. They do not enforce business rules; that is the
 * job of the validators. Output is still encoded by `Response`.
 */

namespace QAFlow\Helpers;

final class Sanitizer
{
    /**
     * Trim a scalar into a string. Nulls and non-scalars become "".
     *
     * @param mixed $value
     */
    public static function string($value): string
    {
        if ($value === null || is_array($value) || is_object($value)) {
            return '';
        }
        return trim((string) $value);
    }

    /**
     * Trim a string and collapse inner whitespace runs into single spaces.
     *
     * @param mixed $value
     */
    public static function text($value): string
    {
        $s = self::string($value);
        if ($s === '') {
            return '';
        }
        return preg_replace('/\s+/u', ' ', $s) ?? $s;
    }

    /**
     * Multiline text. Preserves newlines but normalizes line endings.
     *
     * @param mixed $value
     */
    public static function multiline($value): string
    {
        $s = self::string($value);
        if ($s === '') {
            return '';
        }
        return preg_replace("/\r\n|\r/", "\n", $s) ?? $s;
    }

    /**
     * Coerce a value to an int. Returns 0 for non-numeric values.
     *
     * @param mixed $value
     */
    public static function int($value): int
    {
        if (is_int($value)) {
            return $value;
        }
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }
        if (is_numeric($value)) {
            return (int) $value;
        }
        return 0;
    }

    /**
     * Coerce a value to a non-negative int.
     *
     * @param mixed $value
     */
    public static function uint($value): int
    {
        return max(0, self::int($value));
    }

    /**
     * Coerce a value to a float. Returns 0.0 for non-numeric values.
     *
     * @param mixed $value
     */
    public static function float($value): float
    {
        if (is_float($value) || is_int($value)) {
            return (float) $value;
        }
        if (is_numeric($value)) {
            return (float) $value;
        }
        return 0.0;
    }

    /**
     * Interpret common truthy/falsey representations.
     *
     * @param mixed $value
     */
    public static function bool($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_int($value)) {
            return $value === 1;
        }
        $s = strtolower(self::string($value));
        return in_array($s, ['1', 'true', 'yes', 'on'], true);
    }

    /**
     * Lowercase and trim an email. Does not validate; validators do that.
     *
     * @param mixed $value
     */
    public static function email($value): string
    {
        return strtolower(self::string($value));
    }

    /**
     * Uppercase and strip whitespace from a code-like value (project key,
     * requirement code, etc.). Keeps A-Z, 0-9, dash, underscore.
     *
     * @param mixed $value
     */
    public static function code($value): string
    {
        $s = strtoupper(self::string($value));
        return preg_replace('/[^A-Z0-9_\-]/', '', $s) ?? $s;
    }

    /**
     * Keep only the characters that are safe in a slug. Lowercase.
     *
     * @param mixed $value
     */
    public static function slug($value): string
    {
        $s = strtolower(self::string($value));
        $s = preg_replace('/[^a-z0-9\-]+/', '-', $s) ?? $s;
        return trim($s, '-');
    }

    /**
     * CSV tags: trim each, lowercase, drop empties, deduplicate.
     *
     * @param mixed $value
     * @return array<int,string>
     */
    public static function tags($value): array
    {
        $s = self::string($value);
        if ($s === '') {
            return [];
        }
        $parts = array_map('trim', explode(',', $s));
        $parts = array_map('strtolower', $parts);
        $parts = array_values(array_filter($parts, static fn ($v) => $v !== ''));
        return array_values(array_unique($parts));
    }

    /**
     * Normalize a comma-separated tag list back into the canonical form for
     * storage.
     *
     * @param mixed $value
     */
    public static function tagsCsv($value): string
    {
        return implode(',', self::tags($value));
    }

    /**
     * Trim each string in an array and drop empty entries.
     *
     * @param mixed $value
     * @return array<int,string>
     */
    public static function stringArray($value): array
    {
        if (!is_array($value)) {
            return [];
        }
        $out = [];
        foreach ($value as $item) {
            if (is_scalar($item)) {
                $s = trim((string) $item);
                if ($s !== '') {
                    $out[] = $s;
                }
            }
        }
        return $out;
    }

    /**
     * Normalize an int array (e.g. a list of IDs). Non-numeric values are
     * dropped. Duplicates are removed and the order preserved.
     *
     * @param mixed $value
     * @return array<int,int>
     */
    public static function intArray($value): array
    {
        if (!is_array($value)) {
            return [];
        }
        $out = [];
        foreach ($value as $item) {
            if (is_numeric($item)) {
                $out[] = (int) $item;
            }
        }
        return array_values(array_unique($out));
    }

    /**
     * Escape a string for safe inclusion in HTML output. Use sparingly;
     * responses are JSON-encoded, so HTML escaping is only relevant when
     * rendering inside the app itself.
     *
     * @param mixed $value
     */
    public static function html($value): string
    {
        return htmlspecialchars(self::string($value), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Remove control characters except tab and newline. Useful before storing
     * user-provided text that may contain binary junk.
     *
     * @param mixed $value
     */
    public static function stripControls($value): string
    {
        $s = self::string($value);
        // Remove C0 and C1 control characters except \t (0x09) and \n (0x0A).
        return preg_replace('/[\x00-\x08\x0B-\x1F\x7F-\x9F]/u', '', $s) ?? $s;
    }
}