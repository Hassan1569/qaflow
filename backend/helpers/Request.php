<?php
declare(strict_types=1);

/**
 * QAFlow — HTTP request wrapper.
 *
 * Encapsulates method, path, query, headers, JSON body, and route attributes.
 * Populated once by the Router and passed to middleware and controllers.
 */

namespace QAFlow\Helpers;

final class Request
{
    private string $method;
    private string $path;

    /** @var array<string,string> */
    private array $headers = [];

    /** @var array<string,mixed> */
    private array $query = [];

    /** @var array<string,mixed> */
    private array $body = [];

    /** @var array<string,mixed> */
    private array $attributes = [];

    /** @var array<string,mixed>|null */
    private ?array $user = null;

    public function __construct(string $method, string $path)
    {
        $this->method = strtoupper($method);
        $this->path   = $path;

        $this->headers = $this->collectHeaders();
        $this->query   = $_GET ?? [];
        $this->body    = $this->parseBody();
    }

    // -----------------------------------------------------------------------
    // Basics
    // -----------------------------------------------------------------------

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function isMethod(string $method): bool
    {
        return $this->method === strtoupper($method);
    }

    // -----------------------------------------------------------------------
    // Headers
    // -----------------------------------------------------------------------

    public function header(string $name): ?string
    {
        $key = strtolower($name);
        return $this->headers[$key] ?? null;
    }

    /**
     * @return array<string,string>
     */
    public function headers(): array
    {
        return $this->headers;
    }

    // -----------------------------------------------------------------------
    // Query, body, attributes
    // -----------------------------------------------------------------------

    /** @return mixed */
    public function query(string $key, $default = null)
    {
        return $this->query[$key] ?? $default;
    }

    /**
     * @return array<string,mixed>
     */
    public function queryParams(): array
    {
        return $this->query;
    }

    /**
     * The decoded JSON body. Returns [] when the body is empty or invalid.
     *
     * @return array<string,mixed>
     */
    public function json(): array
    {
        return $this->body;
    }

    /**
     * Get a single field from the JSON body with an optional default.
     *
     * @param mixed $default
     * @return mixed
     */
    public function input(string $key, $default = null)
    {
        return $this->body[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->body);
    }

    /**
     * Attach a route attribute (e.g. a route param or middleware value).
     *
     * @param mixed $value
     */
    public function setAttribute(string $key, $value): void
    {
        $this->attributes[$key] = $value;
    }

    /**
     * @return mixed
     */
    public function attribute(string $key, $default = null)
    {
        return $this->attributes[$key] ?? $default;
    }

    // -----------------------------------------------------------------------
    // Authenticated user
    // -----------------------------------------------------------------------

    /**
     * @param array<string,mixed>|null $user
     */
    public function setUser(?array $user): void
    {
        $this->user = $user;
    }

    /**
     * @return array<string,mixed>|null
     */
    public function user(): ?array
    {
        return $this->user;
    }

    public function userId(): ?int
    {
        return $this->user !== null ? (int) ($this->user['id'] ?? 0) : null;
    }

    public function userRole(): ?string
    {
        return $this->user !== null ? (string) ($this->user['role'] ?? '') : null;
    }

    // -----------------------------------------------------------------------
    // Server / IP
    // -----------------------------------------------------------------------

    /**
     * @param mixed $default
     * @return mixed
     */
    public function server(string $key, $default = null)
    {
        return $_SERVER[$key] ?? $default;
    }

    // -----------------------------------------------------------------------
    // Internals
    // -----------------------------------------------------------------------

    /**
     * @return array<string,string>
     */
    private function collectHeaders(): array
    {
        $headers = [];

        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$name] = (string) $value;
                continue;
            }
            if ($key === 'CONTENT_TYPE' || $key === 'CONTENT_LENGTH') {
                $name = strtolower(str_replace('_', '-', $key));
                $headers[$name] = (string) $value;
            }
        }

        return $headers;
    }

    /**
     * @return array<string,mixed>
     */
    private function parseBody(): array
    {
        $contentType = $this->header('Content-Type') ?? '';

        if (stripos($contentType, 'application/json') !== false) {
            $raw = file_get_contents('php://input');
            if ($raw === false || $raw === '') {
                return [];
            }
            $decoded = json_decode($raw, true);
            return is_array($decoded) ? $decoded : [];
        }

        // urlencoded and multipart bodies are available in $_POST.
        return is_array($_POST) ? $_POST : [];
    }
}