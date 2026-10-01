<?php
declare(strict_types=1);

/**
 * QAFlow — Global error and exception handler.
 *
 * Installs PHP error/exception/shutdown handlers, and exposes methods used by
 * the front controller to render AppException and unexpected errors as JSON
 * without leaking internals to the client.
 */

namespace QAFlow\Core;

use QAFlow\Config\Config;
use QAFlow\Helpers\Logger;
use Throwable;
use ErrorException;

final class Handler
{
    private static bool $installed = false;

    /**
     * Install PHP-level handlers. Called once, early in the request.
     */
    public static function install(): void
    {
        if (self::$installed) {
            return;
        }

        error_reporting(E_ALL);

        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            if (!(error_reporting() & $severity)) {
                return false;
            }
            throw new ErrorException($message, 0, $severity, $file, $line);
        });

        set_exception_handler(static function (Throwable $e): void {
            if ($e instanceof AppException) {
                self::renderAppException($e);
                return;
            }
            self::renderUnexpected($e);
        });

        register_shutdown_function(static function (): void {
            $error = error_get_last();
            if ($error === null) {
                return;
            }
            $fatal = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR];
            if (!in_array($error['type'], $fatal, true)) {
                return;
            }
            Logger::error('Fatal error', [
                'message' => $error['message'],
                'file'    => $error['file'],
                'line'    => $error['line'],
            ]);
            if (!headers_sent()) {
                http_response_code(500);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'error' => [
                        'code'    => 'SERVER_ERROR',
                        'message' => 'Internal server error',
                    ],
                ], JSON_UNESCAPED_SLASHES);
            }
        });

        self::$installed = true;
    }

    /**
     * Render an AppException as a JSON envelope.
     */
    public static function renderAppException(AppException $e): void
    {
        $status = $e->status();
        $payload = [
            'error' => [
                'code'    => $e->errorCode(),
                'message' => $e->getMessage(),
            ],
        ];

        $details = $e->details();
        if ($details !== []) {
            $payload['error']['details'] = $details;
        }

        if ($status >= 500) {
            Logger::error('AppException ' . $e->errorCode(), [
                'message' => $e->getMessage(),
                'details' => $details,
            ]);
        }

        self::emit($status, $payload);
    }

    /**
     * Render an unexpected exception. Logs the full trace; returns a generic
     * 500 to the client unless APP_DEBUG is true.
     */
    public static function renderUnexpected(Throwable $e): void
    {
        Logger::error('Unhandled exception', [
            'class'   => get_class($e),
            'message' => $e->getMessage(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
            'trace'   => $e->getTraceAsString(),
        ]);

        $payload = [
            'error' => [
                'code'    => 'SERVER_ERROR',
                'message' => 'Internal server error',
            ],
        ];

        if (Config::isDebug()) {
            $payload['error']['debug'] = [
                'class'   => get_class($e),
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ];
        }

        self::emit(500, $payload);
    }

    /**
     * @param array<string,mixed> $payload
     */
    private static function emit(int $status, array $payload): void
    {
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: application/json; charset=utf-8');
        }

        echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}