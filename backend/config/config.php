<?php
declare(strict_types=1);

/**
 * QAFlow — Configuration loader.
 *
 * Reads the .env file (if present) into $_ENV / getenv(), then exposes a
 * Config class with typed getters. No secrets are hard-coded here; everything
 * comes from the environment.
 */

namespace QAFlow\Config;

final class Config
{
    /** @var array<string,string> */
    private static array $values = [];

    private static bool $loaded = false;

    /**
     * Load the .env file from the backend root and merge with real environment
     * variables. Real environment variables always win, so production can
     * override values without editing the file.
     */
    public static function load(string $backendRoot): void
    {
        if (self::$loaded) {
            return;
        }

        $envFile = $backendRoot . DIRECTORY_SEPARATOR . '.env';

        if (is_readable($envFile)) {
            self::$values = self::parseEnvFile($envFile);
        }

        // Merge real environment variables (they override .env).
        foreach (self::knownKeys() as $key) {
            $value = getenv($key);
            if ($value !== false && $value !== '') {
                self::$values[$key] = $value;
            }
        }

        // Publish into $_ENV and putenv for libraries that expect them.
        foreach (self::$values as $key => $value) {
            $_ENV[$key] = $value;
            putenv($key . '=' . $value);
        }

        self::applyTimezone();

        self::$loaded = true;
    }

    /**
     * @return array<string,string>
     */
    private static function parseEnvFile(string $path): array
    {
        $result = [];
        $lines  = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($lines === false) {
            return $result;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip comments.
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            // Split on the first '='.
            $pos = strpos($line, '=');
            if ($pos === false) {
                continue;
            }

            $key   = trim(substr($line, 0, $pos));
            $value = trim(substr($line, $pos + 1));

            // Strip surrounding quotes.
            $len = strlen($value);
            if ($len >= 2) {
                $first = $value[0];
                $last  = $value[$len - 1];
                if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                    $value = substr($value, 1, $len - 2);
                }
            }

            $result[$key] = $value;
        }

        return $result;
    }

    /**
     * @return array<int,string>
     */
    private static function knownKeys(): array
    {
        return [
            'APP_ENV', 'APP_DEBUG', 'APP_NAME', 'APP_URL', 'APP_TIMEZONE',
            'DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASS', 'DB_CHARSET',
            'CORS_ORIGIN',
            'TOKEN_TTL_HOURS',
            'LOGIN_RATE_LIMIT_PER_MIN',
            'UPLOAD_DIR', 'LOG_DIR', 'UPLOAD_MAX_BYTES', 'UPLOAD_ALLOWED_MIME',
            'AI_ENABLED',
        ];
    }

    private static function applyTimezone(): void
    {
        $tz = self::$values['APP_TIMEZONE'] ?? 'UTC';

        // Invalid timezones fall back to UTC rather than fatalling.
        if (!in_array($tz, timezone_identifiers_list(), true)) {
            $tz = 'UTC';
        }

        date_default_timezone_set($tz);
    }

    // -----------------------------------------------------------------------
    // Typed getters
    // -----------------------------------------------------------------------

    public static function get(string $key, ?string $default = null): ?string
    {
        return self::$values[$key] ?? $default;
    }

    public static function string(string $key, string $default = ''): string
    {
        $value = self::$values[$key] ?? $default;
        return $value === '' ? $default : (string) $value;
    }

    public static function int(string $key, int $default = 0): int
    {
        if (!isset(self::$values[$key])) {
            return $default;
        }
        return (int) self::$values[$key];
    }

    public static function bool(string $key, bool $default = false): bool
    {
        if (!isset(self::$values[$key])) {
            return $default;
        }
        $value = strtolower((string) self::$values[$key]);
        return in_array($value, ['1', 'true', 'yes', 'on'], true);
    }

    /**
     * @return array<int,string>
     */
    public static function list(string $key, array $default = []): array
    {
        if (!isset(self::$values[$key]) || self::$values[$key] === '') {
            return $default;
        }

        $parts = array_map('trim', explode(',', self::$values[$key]));
        return array_values(array_filter($parts, static fn ($v) => $v !== ''));
    }

    public static function isProduction(): bool
    {
        return self::string('APP_ENV', 'production') === 'production';
    }

    public static function isDebug(): bool
    {
        return self::bool('APP_DEBUG', false);
    }
}