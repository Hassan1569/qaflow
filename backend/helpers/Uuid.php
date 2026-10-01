<?php
declare(strict_types=1);

/**
 * QAFlow — UUID and token helpers.
 *
 * Generates RFC 4122 v4 UUIDs for stored filenames, and 64-character
 * hexadecimal tokens for opaque authentication. Both are cryptographically
 * random and safe for public exposure.
 */

namespace QAFlow\Helpers;

use Random\RandomException;

final class Uuid
{
    /**
     * Generate a v4 UUID, e.g. `b6c9a3f2-1d4e-4a7b-9c8d-2f0e5a6b7c8d`.
     */
    public static function v4(): string
    {
        try {
            $bytes = random_bytes(16);
        } catch (RandomException $e) {
            // Fallback: should never be reached on a supported platform.
            $bytes = openssl_random_pseudo_bytes(16);
        }

        // Set version (4) and variant (10xx) bits.
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);

        return vsprintf(
            '%s%s-%s-%s-%s-%s%s%s',
            str_split(bin2hex($bytes), 4)
        );
    }

    /**
     * Generate a 64-character lowercase hex token.
     */
    public static function token(int $bytes = 32): string
    {
        if ($bytes < 16) {
            $bytes = 16;
        }

        try {
            return bin2hex(random_bytes($bytes));
        } catch (RandomException $e) {
            return bin2hex(openssl_random_pseudo_bytes($bytes));
        }
    }

    /**
     * Generate a short hex identifier (default 16 chars). Used for log
     * correlation ids; not for security-sensitive purposes.
     */
    public static function short(int $bytes = 8): string
    {
        if ($bytes < 4) {
            $bytes = 4;
        }
        return bin2hex(random_bytes($bytes));
    }

    /**
     * Generate a safe stored filename for an upload. Preserves only the
     * extension from the original filename; the stem is a fresh UUID.
     */
    public static function storedFilename(string $originalName): string
    {
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        // Reject unusual extensions. Only keep letters and digits.
        $ext = preg_replace('/[^a-z0-9]/', '', $ext) ?? '';

        $name = self::v4();

        return $ext === '' ? $name : $name . '.' . $ext;
    }

    /**
     * Validate a v4 UUID string.
     */
    public static function isV4(string $value): bool
    {
        return (bool) preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $value
        );
    }

    /**
     * Validate a token produced by `token()` (64 lowercase hex chars).
     */
    public static function isToken(string $value): bool
    {
        return (bool) preg_match('/^[a-f0-9]{64}$/', $value);
    }
}