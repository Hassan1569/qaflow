<?php
declare(strict_types=1);

/**
 * QAFlow — File logger.
 *
 * Appends structured log lines to `storage/logs/app.log`. One JSON object per
 * line so the file can be parsed with `jq`, `grep`, or shipped to an external
 * aggregator without a custom formatter.
 *
 * Rotation is delegated to the operating system (logrotate) or a scheduled
 * task; the logger never truncates the file on its own.
 */

namespace QAFlow\Helpers;

use QAFlow\Config\Config;

final class Logger
{
    private const LEVELS = ['debug', 'info', 'warning', 'error', 'critical'];

    public static function debug(string $message, array $context = []): void
    {
        self::write('debug', $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::write('info', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::write('warning', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::write('error', $message, $context);
    }

    public static function critical(string $message, array $context = []): void
    {
        self::write('critical', $message, $context);
    }

    /**
     * @param array<string,mixed> $context
     */
    private static function write(string $level, string $message, array $context): void
    {
        if (!in_array($level, self::LEVELS, true)) {
            $level = 'info';
        }

        // Debug lines are suppressed unless APP_DEBUG is on. This keeps log
        // files lean in production without changing call sites.
        if ($level === 'debug' && !Config::isDebug()) {
            return;
        }

        $line = [
            'time'     => date('c'),
            'level'    => $level,
            'message'  => $message,
            'context'  => self::sanitize($context),
            'request'  => self::requestContext(),
        ];

        $json = json_encode($line, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
        if ($json === false) {
            $json = '{"time":"' . date('c') . '","level":"error","message":"logger-encode-failed"}';
        }

        $path = self::logFile();

        // Best-effort write. If the log directory is unwritable, fall back to
        // PHP's error_log so the message is not lost silently.
        $handle = @fopen($path, 'ab');
        if ($handle === false) {
            error_log($json);
            return;
        }

        // Non-blocking advisory lock so concurrent writers do not interleave.
        @flock($handle, LOCK_EX);
        @fwrite($handle, $json . PHP_EOL);
        @flock($handle, LOCK_UN);
        @fclose($handle);
    }

    /**
     * @param array<string,mixed> $context
     * @return array<string,mixed>
     */
    private static function sanitize(array $context): array
    {
        // Never log secrets. Redact known sensitive keys recursively.
        $redact = ['password', 'password_hash', 'token', 'auth_token', 'authorization', 'secret', 'api_key'];
        return self::redactRecursive($context, $redact);
    }

    /**
     * @param array<string,mixed> $data
     * @param array<int,string>   $redact
     * @return array<string,mixed>
     */
    private static function redactRecursive(array $data, array $redact): array
    {
        foreach ($data as $key => $value) {
            if (is_string($key) && in_array(strtolower($key), $redact, true)) {
                $data[$key] = '[redacted]';
                continue;
            }
            if (is_array($value)) {
                $data[$key] = self::redactRecursive($value, $redact);
            }
        }
        return $data;
    }

    /**
     * @return array<string,mixed>
     */
    private static function requestContext(): array
    {
        return [
            'method' => $_SERVER['REQUEST_METHOD'] ?? null,
            'path'   => isset($_SERVER['REQUEST_URI']) ? parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) : null,
            'ip'     => $_SERVER['REMOTE_ADDR'] ?? null,
        ];
    }

    private static function logFile(): string
    {
        $dir = self::logDir();

        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        return $dir . DIRECTORY_SEPARATOR . 'app.log';
    }

    private static function logDir(): string
    {
        $configured = Config::string('LOG_DIR', 'storage/logs');

        // Interpret relative paths against the backend root.
        if (!preg_match('#^([A-Za-z]:|/)#', $configured)) {
            $backendRoot = dirname(__DIR__);
            return $backendRoot . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $configured);
        }

        return $configured;
    }
}