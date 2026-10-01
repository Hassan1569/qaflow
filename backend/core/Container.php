<?php
declare(strict_types=1);

/**
 * QAFlow — Dependency injection container.
 *
 * Minimal service locator with lazy singletons. Bindings are callables that
 * receive the container and return the resolved instance. The container also
 * supports `instance()` for pre-built objects (used for the current request
 * and the authenticated user).
 */

namespace QAFlow\Core;

use Closure;
use RuntimeException;

final class Container
{
    /** @var array<string,Closure> */
    private array $bindings = [];

    /** @var array<string,object> */
    private array $instances = [];

    /** @var array<string,bool> */
    private array $singletons = [];

    /** @var array<int,Closure> */
    private array $bootCallbacks = [];

    /**
     * Register a binding. The callable receives the container and returns the
     * instance. The binding is a singleton unless `$shared = false`.
     */
    public function bind(string $id, Closure $factory, bool $shared = true): void
    {
        $this->bindings[$id] = $factory;
        $this->singletons[$id] = $shared;
        unset($this->instances[$id]);
    }

    /**
     * Register a singleton binding (alias of bind with $shared = true).
     */
    public function singleton(string $id, Closure $factory): void
    {
        $this->bind($id, $factory, true);
    }

    /**
     * Register a pre-built instance. Always treated as a singleton.
     */
    public function instance(string $id, object $instance): void
    {
        $this->instances[$id] = $instance;
        $this->singletons[$id] = true;
    }

    public function has(string $id): bool
    {
        return isset($this->instances[$id]) || isset($this->bindings[$id]);
    }

    /**
     * Resolve an instance by id.
     */
    public function get(string $id): object
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        if (!isset($this->bindings[$id])) {
            throw new RuntimeException(sprintf('Container: no binding for "%s".', $id));
        }

        $instance = ($this->bindings[$id])($this);

        if (!is_object($instance)) {
            throw new RuntimeException(sprintf('Container: binding for "%s" did not return an object.', $id));
        }

        if (!empty($this->singletons[$id])) {
            $this->instances[$id] = $instance;
        }

        return $instance;
    }

    /**
     * Register a callback to run at boot time.
     */
    public function onBoot(Closure $callback): void
    {
        $this->bootCallbacks[] = $callback;
    }

    /**
     * Run all registered boot callbacks. Called once after all bindings are
     * registered.
     */
    public function boot(): void
    {
        foreach ($this->bootCallbacks as $callback) {
            $callback($this);
        }
        $this->bootCallbacks = [];
    }
}