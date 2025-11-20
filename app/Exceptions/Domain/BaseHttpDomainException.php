<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

/**
 * Спрощує створення доменних ексепшенів:
 * достатньо визначити лише const CODE та const STATUS.
 */
abstract class BaseHttpDomainException extends DomainException
{
    protected const CODE   = 'domain_error';
    protected const STATUS = 400;

    public function code(): string
    {
        return static::CODE;
    }

    public function status(): int
    {
        return static::STATUS;
    }
}
