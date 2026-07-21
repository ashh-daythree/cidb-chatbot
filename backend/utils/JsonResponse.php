<?php

declare(strict_types=1);

namespace Cidb\Backend\Utils;

final class JsonResponse
{
    public static function success(array $data = [], string $message = 'OK', int $statusCode = 200): array
    {
        return [
            'statusCode' => $statusCode,
            'payload' => [
                'success' => true,
                'message' => $message,
                'data' => $data,
            ],
        ];
    }

    public static function error(string $message, array $errors = [], int $statusCode = 500, ?string $errorCode = null): array
    {
        return [
            'statusCode' => $statusCode,
            'payload' => [
                'success' => false,
                'message' => $message,
                'error_code' => $errorCode,
                'errors' => $errors,
            ],
        ];
    }
}

