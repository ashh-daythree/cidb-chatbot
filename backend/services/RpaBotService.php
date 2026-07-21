<?php

declare(strict_types=1);

namespace Cidb\Backend\Services;

use Cidb\Backend\Config\EnvironmentLoader;
use Cidb\Backend\Utils\Exceptions\AppException;
use Cidb\Backend\Utils\JsonHelper;
use Cidb\Backend\Utils\Logger;
use Throwable;

final class RpaBotService
{
    private const DEFAULT_ENDPOINT = 'http://103.241.150.147:8081/api/external/ticket-insert';

    public function __construct(
        private readonly Logger $logger
    ) {
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
    public function triggerTicketInsert(array $payload): array
    {
        $endpoint = trim((string) EnvironmentLoader::get('RPA_BOT_ENDPOINT', self::DEFAULT_ENDPOINT));
        $apiKey = $this->resolveApiKey();
        $timeoutMs = max(1000, (int) EnvironmentLoader::get('RPA_BOT_TIMEOUT_MS', 15000));
        $connectTimeoutMs = max(1000, (int) EnvironmentLoader::get('RPA_BOT_CONNECT_TIMEOUT_MS', 5000));
        $requestBody = JsonHelper::encode($payload);

        $this->logger->info('Triggering RPA bot API.', [
            'endpoint' => $endpoint,
            'has_api_key' => $apiKey !== '',
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
                throw new AppException('Unable to initialize the RPA bot request.', 500, 'CURL_INIT_FAILED');
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
                $errorMessage = curl_error($curl) ?: 'The RPA bot request failed.';
                $rawResponse = '';
            } else {
                $rawResponse = (string) $response;
            }

            curl_close($curl);
        } catch (Throwable $throwable) {
            $errorMessage = $throwable->getMessage() !== '' ? $throwable->getMessage() : 'The RPA bot request failed.';
            $rawResponse = $rawResponse !== '' ? $rawResponse : $errorMessage;
            $this->logger->error('RPA bot API request failed.', [
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

        $this->logger->info('RPA bot API response received.', [
            'endpoint' => $endpoint,
            'status_code' => $statusCode,
            'duration_ms' => $durationMs,
            'response' => $this->summarizeResponse($rawResponse, $parsedResponse),
        ]);

        return [
            'success' => $statusCode >= 200 && $statusCode < 300,
            'status_code' => $statusCode,
            'raw_response_text' => $rawResponse,
            'parsed_response' => $parsedResponse,
            'duration_ms' => $durationMs,
            'error_message' => $statusCode >= 200 && $statusCode < 300 ? null : ('RPA bot request failed with status ' . $statusCode . '.'),
        ];
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

            if (preg_match('/(identity|ic|passport|name|signature)/i', $key) === 1) {
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

    private function summarizeResponse(string $rawResponse, ?array $parsedResponse): array
    {
        if ($parsedResponse !== null) {
            return $this->maskForLog($parsedResponse);
        }

        return [
            'raw_response_text' => $this->truncate($rawResponse, 1000),
        ];
    }

    private function truncate(string $value, int $limit): string
    {
        if (strlen($value) <= $limit) {
            return $value;
        }

        return substr($value, 0, $limit) . '...';
    }

    private function resolveApiKey(): string
    {
        $apiKey = trim((string) EnvironmentLoader::get('RPA_BOT_API_KEY', ''));
        if ($apiKey === '') {
            throw new AppException(
                'RPA bot API key is missing.',
                500,
                'RPA_API_KEY_MISSING'
            );
        }

        return $apiKey;
    }
}
