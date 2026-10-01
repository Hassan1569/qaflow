<?php
declare(strict_types=1);

/**
 * QAFlow — HTTP response wrapper.
 *
 * Wraps a status code, a payload, and optional headers. Controllers build
 * one of these and return it. The Router calls `send()`.
 *
 * The API contract is:
 *   Success: { "data": <payload>, "meta": { ... } }
 *   Error:   { "error": { "code": "...", "message": "...", "details": {...} } }
 *
 * `Response::json()` and `Response::error()` helpers produce the correct shape.
 */

namespace QAFlow\Helpers;

final class Response
{
    private int $status;

    /** @var mixed */
    private $payload;

    /** @var array<string,string> */
    private array $headers;

    /** @var array<string,mixed>|null */
    private ?array $meta;

    /**
     * @param mixed                $payload
     * @param array<string,string> $headers
     * @param array<string,mixed>|null $meta
     */
    public function __construct(int $status = 200, $payload = null, array $headers = [], ?array $meta = null)
    {
        $this->status  = $status;
        $this->payload = $payload;
        $this->headers = $headers;
        $this->meta    = $meta;
    }

    // -----------------------------------------------------------------------
    // Named constructors
    // -----------------------------------------------------------------------

    /**
     * Success response. `$payload` becomes the value of `data`.
     *
     * @param mixed                $data
     * @param array<string,mixed>|null $meta
     */
    public static function json($data, int $status = 200, ?array $meta = null): self
    {
        return new self($status, $data, [], $meta);
    }

    /**
     * Created (201).
     *
     * @param mixed $data
     */
    public static function created($data): self
    {
        return new self(201, $data);
    }

    /**
     * No content (204).
     */
    public static function noContent(): self
    {
        return new self(204, null);
    }

    /**
     * Error response in the standard envelope.
     *
     * @param array<string,mixed> $details
     */
    public static function error(int $status, string $code, string $message, array $details = []): self
    {
        $error = [
            'code'    => $code,
            'message' => $message,
        ];
        if ($details !== []) {
            $error['details'] = $details;
        }

        return new self($status, ['error' => $error]);
    }

    /**
     * A paginated response.
     *
     * @param array<int,mixed> $items
     */
    public static function paginated(array $items, int $page, int $perPage, int $total, int $status = 200): self
    {
        return new self($status, $items, [], [
            'page'     => $page,
            'per_page' => $perPage,
            'total'    => $total,
        ]);
    }

    // -----------------------------------------------------------------------
    // Accessors (used by the Router / middleware)
    // -----------------------------------------------------------------------

    public function status(): int
    {
        return $this->status;
    }

    /** @return mixed */
    public function payload()
    {
        return $this->payload;
    }

    /**
     * @return array<string,string>
     */
    public function headers(): array
    {
        return $this->headers;
    }

    // -----------------------------------------------------------------------
    // Send
    // -----------------------------------------------------------------------

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);

            // Default JSON content type unless overridden.
            if (!isset($this->headers['Content-Type'])) {
                header('Content-Type: application/json; charset=utf-8');
            }

            foreach ($this->headers as $name => $value) {
                header($name . ': ' . $value);
            }
        }

        if ($this->status === 204) {
            return;
        }

        echo $this->encode($this->payload);
    }

    /**
     * Wrap payloads in the standard envelope unless the payload already
     * carries `error` or `data` at the top level.
     *
     * @param mixed $payload
     */
    private function encode($payload): string
    {
        if ($payload === null) {
            return '';
        }

        if (is_array($payload) && (array_key_exists('error', $payload) || array_key_exists('data', $payload))) {
            // Already enveloped.
            $envelope = $payload;
        } else {
            $envelope = ['data' => $payload];
            if ($this->meta !== null) {
                $envelope['meta'] = $this->meta;
            }
        }

        $json = json_encode(
            $envelope,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR
        );

        return $json === false ? '' : $json;
    }
}