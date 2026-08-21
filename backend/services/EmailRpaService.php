<?php

declare(strict_types=1);

namespace Cidb\Backend\Services;

use Cidb\Backend\Config\EnvironmentLoader;
use Cidb\Backend\Utils\Exceptions\AppException;
use Cidb\Backend\Utils\JsonHelper;
use Cidb\Backend\Utils\Logger;
use Throwable;

final class EmailRpaService
{
    public function __construct(
        private readonly Logger $logger
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function maskForLog(array $payload): array
    {
        foreach ($payload as $key => $value) {
            if (!is_string($key)) {
                continue;
            }

            if (preg_match('/(identity|ic|passport|name|contact|location|ssm)/i', $key) === 1) {
                $payload[$key] = is_string($value) ? $this->maskValue($value) : $value;
                continue;
            }

            if (is_array($value)) {
                $payload[$key] = $this->maskForLog($value);
            }
        }

        return $payload;
    }

    private function maskValue(string $value): string
    {
        $length = strlen($value);
        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        return substr($value, 0, 2) . str_repeat('*', max(0, $length - 4)) . substr($value, -2);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{
     *     success: bool,
     *     status_code: int,
     *     raw_response_text: string,
     *     parsed_response: array<string, mixed>|null,
     *     duration_ms: int,
     *     error_message: ?string
     * }
     */
    public function trigger(array $payload): array
    {
        $endpoint = trim((string) EnvironmentLoader::get('RPA_EMAIL_ENDPOINT', ''));
        if ($endpoint === '') {
            throw new AppException('Email RPA endpoint is missing.', 500, 'EMAIL_RPA_ENDPOINT_MISSING');
        }

        $apiKey = trim((string) EnvironmentLoader::get('RPA_EMAIL_API_KEY', ''));
        if ($apiKey === '') {
            throw new AppException('Email RPA API key is missing.', 500, 'EMAIL_RPA_API_KEY_MISSING');
        }

        $timeoutMs = max(1000, (int) EnvironmentLoader::get('RPA_EMAIL_TIMEOUT_MS', 15000));
        $connectTimeoutMs = max(1000, (int) EnvironmentLoader::get('RPA_EMAIL_CONNECT_TIMEOUT_MS', 5000));
        $requestBody = JsonHelper::encode($payload);

        $this->logger->info('Triggering email RPA.', [
            'endpoint' => $endpoint,
            'payload' => $this->maskForLog($payload),
        ]);

        $startedAt = microtime(true);
        $statusCode = 0;
        $rawResponse = '';
        $errorMessage = null;
        $parsedResponse = null;

        try {
            if (!function_exists('curl_init')) {
                throw new AppException('cURL is not available.', 500, 'CURL_UNAVAILABLE');
            }

            $curl = curl_init($endpoint);
            if ($curl === false) {
                throw new AppException('Unable to initialize the email RPA request.', 500, 'CURL_INIT_FAILED');
            }

            curl_setopt_array($curl, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $requestBody,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Accept: application/json',
                    'X-API-Key: ' . $apiKey,
                ],
                CURLOPT_CONNECTTIMEOUT_MS => $connectTimeoutMs,
                CURLOPT_TIMEOUT_MS => $timeoutMs,
            ]);

            $response = curl_exec($curl);
            $statusCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);

            if ($response === false) {
                $errorMessage = curl_error($curl) ?: 'The email RPA request failed.';
                $rawResponse = '';
            } else {
                $rawResponse = (string) $response;
            }

            curl_close($curl);
        } catch (Throwable $throwable) {
            $errorMessage = $throwable->getMessage() !== '' ? $throwable->getMessage() : 'The email RPA request failed.';
            $rawResponse = $rawResponse !== '' ? $rawResponse : $errorMessage;
            $this->logger->error('Email RPA request failed.', [
                'endpoint' => $endpoint,
                'error' => $errorMessage,
            ]);

            $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

            return [
                'success' => false,
                'status_code' => $statusCode,
                'raw_response_text' => $rawResponse,
                'parsed_response' => null,
                'duration_ms' => $durationMs,
                'error_message' => $errorMessage,
            ];
        }

        if ($rawResponse !== '') {
            $decoded = json_decode($rawResponse, true);
            if (is_array($decoded)) {
                $parsedResponse = $decoded;
            }
        }

        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

        $this->logger->info('Email RPA response received.', [
            'endpoint' => $endpoint,
            'status_code' => $statusCode,
            'duration_ms' => $durationMs,
            'response' => $parsedResponse ?? $rawResponse,
        ]);

        return [
            'success' => $statusCode >= 200 && $statusCode < 300,
            'status_code' => $statusCode,
            'raw_response_text' => $rawResponse,
            'parsed_response' => $parsedResponse,
            'duration_ms' => $durationMs,
            'error_message' => $statusCode >= 200 && $statusCode < 300 ? null : ('Email RPA request failed with status ' . $statusCode . '.'),
        ];
    }
}
