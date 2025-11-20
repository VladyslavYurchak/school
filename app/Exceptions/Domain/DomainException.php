<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

abstract class DomainException extends \RuntimeException
{
    public function __construct(
        string $message = '',
        protected array $context = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }

    /** Унікальний код помилки (для фронту / логіки) */
    abstract public function code(): string;

    /** HTTP-статус */
    abstract public function status(): int;

    /** Додаткові дані */
    public function context(): array
    {
        return $this->context;
    }
}
