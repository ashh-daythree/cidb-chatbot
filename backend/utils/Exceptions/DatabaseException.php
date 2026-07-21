<?php

declare(strict_types=1);

namespace Cidb\Backend\Utils\Exceptions;

final class DatabaseException extends AppException
{
    public function __construct(string $message, array $errors = [], ?\Throwable $previous = null)
    {
        parent::__construct($message, 500, 'DATABASE_ERROR', $errors, $previous);
    }
}

