<?php
declare(strict_types=1);

/**
 * QAFlow — PDO connection factory.
 *
 * Returns a singleton PDO instance configured for the QAFlow schema.
 * All queries must use prepared statements; emulation is disabled so the
 * server does real prepared statements.
 */

namespace QAFlow\Config;

use PDO;
use PDOException;
use RuntimeException;

final class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $host    = Config::string('DB_HOST', '127.0.0.1');
        $port    = Config::int('DB_PORT', 3306);
        $name    = Config::string('DB_NAME', 'qaflow');
        $user    = Config::string('DB_USER', 'qaflow');
        $pass    = Config::string('DB_PASS', '');
        $charset = Config::string('DB_CHARSET', 'utf8mb4');

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $host,
            $port,
            $name,
            $charset
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_STRINGIFY_FETCHES  => false,
            PDO::ATTR_PERSISTENT         => false,
        ];

        try {
            self::$pdo = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            // Never leak credentials or DSN to the client.
            error_log('[QAFlow][db] connection failed: ' . $e->getMessage());
            throw new RuntimeException('Database connection failed.');
        }

        return self::$pdo;
    }

    /**
     * Begin a transaction.
     */
    public static function begin(): void
    {
        self::connection()->beginTransaction();
    }

    /**
     * Commit the current transaction.
     */
    public static function commit(): void
    {
        if (self::connection()->inTransaction()) {
            self::connection()->commit();
        }
    }

    /**
     * Roll back the current transaction.
     */
    public static function rollback(): void
    {
        if (self::connection()->inTransaction()) {
            self::connection()->rollBack();
        }
    }

    /**
     * Run a callable inside a transaction, rolling back on any exception.
     *
     * @template T
     * @param callable():T $fn
     * @return T
     */
    public static function transaction(callable $fn)
    {
        self::begin();
        try {
            $result = $fn();
            self::commit();
            return $result;
        } catch (\Throwable $e) {
            self::rollback();
            throw $e;
        }
    }
}