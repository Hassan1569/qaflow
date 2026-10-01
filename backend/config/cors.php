<?php
declare(strict_types=1);

/**
 * QAFlow — CORS configuration.
 *
 * Reads the allowed origins from CORS_ORIGIN (comma-separated) and exposes
 * helpers used by CorsMiddleware. Origins are matched exactly; wildcards are
 * deliberately not supported.
 */

namespace QAFlow\Config;

final class Cors
{
    /**
     * @return array<int,string>
     */
    public static function allowedOrigins(): array
    {
        return Config::list('CORS_ORIGIN', ['http://localhost:5173']);
    }

    public static function isAllowed(string $origin): bool
    {
        if ($origin === '') {
            return false;
        }

        return in_array($origin, self::allowedOrigins(), true);
    }

    /**
     * @return array<int,string>
     */
    public static function allowedMethods(): array
    {
        return ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];
    }

    /**
     * @return array<int,string>
     */
    public static function allowedHeaders(): array
    {
        return [
            'Content-Type',
            'Authorization',
            'Accept',
            'Origin',
            'X-Requested-With',
            'X-Api-Token',
        ];
    }

    /**
     * @return array<int,string>
     */
    public static function exposedHeaders(): array
    {
        return ['Content-Length', 'Content-Type'];
    }

    public static function maxAge(): int
    {
        // 10 minutes.
        return 600;
    }
}