<?php
declare(strict_types=1);

/**
 * QAFlow — Authentication validator.
 *
 * Field-level shape checks for the login endpoint. Credential verification
 * itself lives in AuthService.
 */

namespace QAFlow\Validators;

use QAFlow\Core\AppException;

final class AuthValidator
{
    /**
     * @param array<string,mixed> $data
     */
    public static function validateLogin(array $data): void
    {
        $v = new Validator();

        $v->field('email')
            ->required($data['email'] ?? null)
            ->email($data['email'] ?? null);

        $v->field('password')
            ->required($data['password'] ?? null)
            ->string($data['password'] ?? null, 1, 255);

        if ($v->hasErrors()) {
            throw AppException::validation('Validation failed.', $v->errors());
        }
    }
}