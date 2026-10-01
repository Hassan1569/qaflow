<?php
declare(strict_types=1);

/**
 * QAFlow — Application exception.
 *
 * Every business or HTTP error thrown by the application should be an
 * AppException (or a subclass). The Handler maps it to an HTTP status and a
 * JSON error envelope. Anything else is treated as an unexpected error and
 * reported as a generic 500 without leaking internals.
 */

namespace QAFlow\Core;

use RuntimeException;

class AppException extends RuntimeException
{
    /** HTTP status code to send. */
    protected int $status = 400;

    /** Machine-readable error code (e.g. VALIDATION_ERROR). */
    protected string $errorCode = 'ERROR';

    /** @var array<string,mixed> */
    protected array $details = [];

    /**
     * @param array<string,mixed> $details
     */
    public function __construct(string $message = '', array $details = [], ?string $errorCode = null, ?int $status = null)
    {
        parent::__construct($message);

        if ($status !== null) {
            $this->status = $status;
        }
        if ($errorCode !== null) {
            $this->errorCode = $errorCode;
        }
        $this->details = $details;
    }

    public function status(): int
    {
        return $this->status;
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * @return array<string,mixed>
     */
    public function details(): array
    {
        return $this->details;
    }

    // -----------------------------------------------------------------------
    // Named constructors
    // -----------------------------------------------------------------------

    /** 400 — Malformed request. */
    public static function badRequest(string $message = 'Bad request', array $details = []): self
    {
        return new self($message, $details, 'BAD_REQUEST', 400);
    }

    /** 401 — Missing or invalid credentials. */
    public static function unauthorized(string $message = 'Unauthorized'): self
    {
        return new self($message, [], 'UNAUTHORIZED', 401);
    }

    /** 403 — Authenticated but not permitted. */
    public static function forbidden(string $message = 'Forbidden'): self
    {
        return new self($message, [], 'FORBIDDEN', 403);
    }

    /** 404 — Resource not found. */
    public static function notFound(string $message = 'Not found'): self
    {
        return new self($message, [], 'NOT_FOUND', 404);
    }

    /** 409 — Conflict with current state (e.g. duplicate key). */
    public static function conflict(string $message = 'Conflict', array $details = []): self
    {
        return new self($message, $details, 'CONFLICT', 409);
    }

    /** 422 — Field-level validation failed. */
    public static function validation(string $message = 'Validation failed', array $details = []): self
    {
        return new self($message, $details, 'VALIDATION_ERROR', 422);
    }

    /** 429 — Too many requests. */
    public static function tooManyRequests(string $message = 'Too many requests'): self
    {
        return new self($message, [], 'RATE_LIMITED', 429);
    }

    /** 500 — Unexpected server-side failure. */
    public static function serverError(string $message = 'Internal server error'): self
    {
        return new self($message, [], 'SERVER_ERROR', 500);
    }
}