<?php

declare(strict_types=1);

namespace Cidb\Backend\Utils\Exceptions;

final class ConfigurationException extends AppException
{
    public function __construct(string $message, array $errors = [], ?\Throwable $previous = null)
    {
        parent::__construct($message, 500, 'CONFIGURATION_ERROR', $errors, $previous);
    }
}

