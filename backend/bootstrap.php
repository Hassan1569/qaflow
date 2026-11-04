<?php
declare(strict_types=1);

/**
 * QAFlow — Bootstrap.
 *
 * Loads the environment, configures the PSR-4 autoloader (with a Composer
 * fallback for local installs), and requires the small set of classes that
 * every request needs before routing begins.
 */

// ---------------------------------------------------------------------------
// Backend root (the directory that contains this file).
// ---------------------------------------------------------------------------
$backendRoot = __DIR__;

// ---------------------------------------------------------------------------
// Autoloader.
//   - If Composer's autoloader is present (vendor/autoload.php), use it.
//   - Otherwise, fall back to a small PSR-4 loader for the QAFlow namespace.
// ---------------------------------------------------------------------------
$composerAutoload = $backendRoot . '/vendor/autoload.php';
if (is_file($composerAutoload)) {
    require $composerAutoload;
} else {
    spl_autoload_register(static function (string $class) use ($backendRoot): void {
        $prefix = 'QAFlow\\';
        if (!str_starts_with($class, $prefix)) {
            return;
        }

        $relative = substr($class, strlen($prefix));
        $relative = str_replace('\\', DIRECTORY_SEPARATOR, $relative);

        // Try each known source root in order.
        $roots = [
            $backendRoot,                       // top-level QAFlow\* classes
            $backendRoot . '/core',
            $backendRoot . '/helpers',
            $backendRoot . '/middleware',
            $backendRoot . '/controllers',
            $backendRoot . '/services',
            $backendRoot . '/repositories',
            $backendRoot . '/models',
            $backendRoot . '/validators',
            $backendRoot . '/config',
            $backendRoot . '/routes',
        ];

        foreach ($roots as $root) {
            $file = $root . DIRECTORY_SEPARATOR . $relative . '.php';
            if (is_file($file)) {
                require $file;
                return;
            }
        }

        // Fall back to the default layout: QAFlow\Sub\Class -> <root>/Sub/Class.php
        $file = $backendRoot . DIRECTORY_SEPARATOR . $relative . '.php';
        if (is_file($file)) {
            require $file;
        }
    });
}

// ---------------------------------------------------------------------------
// Load environment into Config.
// ---------------------------------------------------------------------------
require $backendRoot . '/config/config.php';
\QAFlow\Config\Config::load($backendRoot);