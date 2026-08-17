<?php

declare(strict_types=1);

namespace Cidb\Backend\Utils;

use Cidb\Backend\Utils\Exceptions\AppException;
use Throwable;

final class ErrorHandler
{
    public function __construct(
        private readonly Logger $logger,
        private readonly bool $debug = false
    ) {
    }

    public function register(): void
    {
        set_exception_handler([$this, 'handleThrowable']);
        set_error_handler([$this, 'handleError']);
    }

    public function handleError(int $severity, string $message, string $file, int $line): bool
    {
        throw new \ErrorException($message, 0, $severity, $file, $line);
    }

    public function handleThrowable(Throwable $throwable): void
    {
        $statusCode = 500;
        $errorCode = 'SERVER_ERROR';
        $errors = [];

        if ($throwable instanceof AppException) {
            $statusCode = $throwable->statusCode();
            $errorCode = $throwable->errorCode();
            $errors = $throwable->errors();
        }

        $this->logger->error($throwable->getMessage(), [
            'exception' => get_class($throwable),
            'file' => $throwable->getFile(),
            'line' => $throwable->getLine(),
        ]);

        $payload = JsonResponse::error(
            $this->debug ? $throwable->getMessage() : 'An unexpected error occurred.',
            $errors,
            $statusCode,
            $errorCode
        );

        if (PHP_SAPI !== 'cli') {
            http_response_code($payload['statusCode']);
            header('Content-Type: application/json; charset=utf-8');
        }

        echo JsonHelper::encode($payload['payload']);
    }
}
