<?php
declare(strict_types=1);

/**
 * QAFlow — Static container accessor.
 *
 * A very small bridge that lets declarative files (route tables, config
 * callbacks, boot hooks) reach the application container without receiving
 * it as a parameter. The container is set once in public/index.php.
 *
 * This is deliberately narrow: the only supported operations are set() and
 * get(). Application code should inject the container through constructors
 * instead of calling into this class.
 */

namespace QAFlow\Core;

use RuntimeException;

final class ContainerAccess
{
    private static ?Container $container = null;

    public static function set(Container $container): void
    {
        self::$container = $container;
    }

    public static function get(): Container
    {
        if (self::$container === null) {
            throw new RuntimeException('Container has not been set.');
        }

        return self::$container;
    }
}