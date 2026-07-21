<?php

declare(strict_types=1);

namespace Cidb\Backend\Utils\Exceptions;

use RuntimeException;

class AppException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly int $statusCode = 500,
        private readonly string $errorCode = 'APP_ERROR',
        private readonly array $errors = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $statusCode, $previous);
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }

    public function errors(): array
    {
        return $this->errors;
    }
}

