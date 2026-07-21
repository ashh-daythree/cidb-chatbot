<?php

declare(strict_types=1);

namespace Cidb\Backend\Controllers;

use Cidb\Backend\Utils\Exceptions\AppException;
use Cidb\Backend\Utils\JsonResponse;

abstract class AbstractController
{
    /**
     * @return array<string, mixed>
     */
    protected function payload(array $request): array
    {
        $post = is_array($request['post'] ?? null) ? $request['post'] : [];
        if ($post !== []) {
            return $post;
        }

        $body = trim((string) ($request['body'] ?? ''));
        if ($body === '') {
            return [];
        }

        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            throw new AppException('Invalid JSON payload.', 400, 'INVALID_JSON');
        }

        return $decoded;
    }

    /**
     * @return array<string, mixed>
     */
    protected function routeParams(array $request): array
    {
        return is_array($request['route_params'] ?? null) ? $request['route_params'] : [];
    }

    protected function routeParam(array $request, string $name): string
    {
        $params = $this->routeParams($request);
        $value = trim((string) ($params[$name] ?? ''));
        if ($value === '') {
            throw new AppException(sprintf('Route parameter "%s" is required.', $name), 422, strtoupper($name) . '_REQUIRED');
        }

        return $value;
    }

    protected function requireString(array $payload, string $field, string $message, string $errorCode): string
    {
        $value = trim((string) ($payload[$field] ?? ''));
        if ($value === '') {
            throw new AppException($message, 422, $errorCode, [
                $field => $message,
            ]);
        }

        return $value;
    }

    protected function file(array $request, string $field): array
    {
        $files = is_array($request['files'] ?? null) ? $request['files'] : [];
        if (!array_key_exists($field, $files)) {
            throw new AppException(sprintf('Uploaded file "%s" is required.', $field), 422, 'FILE_REQUIRED', [
                $field => 'Uploaded file is required.',
            ]);
        }

        $file = $files[$field];
        if (!is_array($file)) {
            throw new AppException(sprintf('Uploaded file "%s" is invalid.', $field), 422, 'FILE_INVALID');
        }

        return $file;
    }

    protected function success(array $data = [], string $message = 'OK', int $statusCode = 200): array
    {
        return JsonResponse::success($data, $message, $statusCode);
    }
}
