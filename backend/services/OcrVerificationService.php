<?php

declare(strict_types=1);

namespace Cidb\Backend\Services;

use Cidb\Backend\Config\OcrConfig;
use Cidb\Backend\Storage\DocumentStorageInterface;
use Cidb\Backend\Utils\Exceptions\AppException;
use Cidb\Backend\Utils\Logger;
use Throwable;

final class OcrVerificationService
{
    public function __construct(
        private readonly DocumentStorageInterface $storageProvider,
        private readonly OcrConfig $ocrConfig,
        private readonly Logger $logger
    ) {
    }

    /**
     * @param array<string, mixed> $frontDocument
     * @param array<string, mixed> $backDocument
     * @param array<string, mixed> $context
     * @return array{
     *     success: bool,
     *     status_code: int,
     *     raw_response_text: string,
     *     parsed_response: array<string, mixed>|null,
     *     duration_ms: int,
     *     error_message: ?string
     * }
     */
    public function verifyMyKad(
        string $enteredName,
        string $enteredNric,
        array $frontDocument,
        array $backDocument,
        array $context = []
    ): array {
        return $this->sendRequest(
            [
                'document_type' => 'mykad',
                'entered_name' => $enteredName,
                'entered_nric' => $enteredNric,
            ],
            [
                'mykad_front' => $this->buildUploadFile($frontDocument, 'mykad_front'),
                'mykad_back' => $this->buildUploadFile($backDocument, 'mykad_back'),
            ],
            $context
        );
    }

    /**
     * @param array<string, mixed> $passportDocument
     * @param array<string, mixed> $context
     * @return array{
     *     success: bool,
     *     status_code: int,
     *     raw_response_text: string,
     *     parsed_response: array<string, mixed>|null,
     *     duration_ms: int,
     *     error_message: ?string
     * }
     */
    public function verifyPassport(
        string $enteredName,
        string $enteredPassportNumber,
        array $passportDocument,
        array $context = []
    ): array {
        return $this->sendRequest(
            [
                'document_type' => 'passport',
                'entered_name' => $enteredName,
                'entered_passport_number' => $enteredPassportNumber,
            ],
            [
                'passport_image' => $this->buildUploadFile($passportDocument, 'passport_image'),
            ],
            $context
        );
    }

    /**
     * @param array<string, mixed> $formFields
     * @param array<string, \CURLFile> $files
     * @param array<string, mixed> $context
     * @return array{
     *     success: bool,
     *     status_code: int,
     *     raw_response_text: string,
     *     parsed_response: array<string, mixed>|null,
     *     duration_ms: int,
     *     error_message: ?string
     * }
     */
    private function sendRequest(array $formFields, array $files, array $context = []): array
    {
        if (!function_exists('curl_init')) {
            throw new AppException('cURL is not available.', 500, 'CURL_UNAVAILABLE');
        }

        $endpoint = $this->ocrConfig->verifyUrl();
        $timeoutMs = $this->ocrConfig->timeoutMilliseconds();
        $connectTimeoutMs = $this->ocrConfig->connectTimeoutMilliseconds();
        $attempts = max(1, $this->ocrConfig->retryCount() + 1);
        $requestId = trim((string) ($context['request_id'] ?? ''));

        $this->logger->info('Triggering OCR verification API.', [
            'endpoint' => $endpoint,
            'request_id' => $requestId !== '' ? $requestId : null,
            'document_type' => $formFields['document_type'] ?? null,
            'attempts' => $attempts,
        ]);

        $startedAt = microtime(true);
        $lastResult = [
            'success' => false,
            'status_code' => 0,
            'raw_response_text' => '',
            'parsed_response' => null,
            'duration_ms' => 0,
            'error_message' => null,
        ];

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            $statusCode = 0;
            $rawResponse = '';
            $parsedResponse = null;
            $errorMessage = null;

            try {
                $curl = curl_init($endpoint);
                if ($curl === false) {
                    throw new AppException('Unable to initialize the OCR request.', 500, 'CURL_INIT_FAILED');
                }

                $headers = ['Accept: application/json'];
                if ($this->ocrConfig->apiKey() !== '') {
                    $headers[] = 'X-API-Key: ' . $this->ocrConfig->apiKey();
                }
                if ($requestId !== '') {
                    $headers[] = 'X-Request-Id: ' . $requestId;
                }

                curl_setopt_array($curl, [
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => array_merge($formFields, $files),
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER => $headers,
                    CURLOPT_CONNECTTIMEOUT_MS => $connectTimeoutMs,
                    CURLOPT_TIMEOUT_MS => $timeoutMs,
                ]);

                $response = curl_exec($curl);
                $statusCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);

                if ($response === false) {
                    $errorMessage = curl_error($curl) ?: 'The OCR request failed.';
                } else {
                    $rawResponse = (string) $response;
                    $decoded = json_decode($rawResponse, true);
                    if (is_array($decoded)) {
                        $parsedResponse = $decoded;
                    }

                    $errorMessage = $statusCode >= 400
                        ? $this->extractResponseMessage($parsedResponse, $statusCode)
                        : null;
                }

                curl_close($curl);
            } catch (Throwable $throwable) {
                $errorMessage = $throwable->getMessage() !== '' ? $throwable->getMessage() : 'The OCR request failed.';
                $rawResponse = $rawResponse !== '' ? $rawResponse : $errorMessage;
                $this->logger->error('OCR verification request failed.', [
                    'endpoint' => $endpoint,
                    'request_id' => $requestId !== '' ? $requestId : null,
                    'error' => $errorMessage,
                ]);

                $lastResult = [
                    'success' => false,
                    'status_code' => $statusCode,
                    'raw_response_text' => $rawResponse,
                    'parsed_response' => $parsedResponse,
                    'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                    'error_message' => $errorMessage,
                ];

                if ($attempt < $attempts) {
                    usleep(250000);
                    continue;
                }

                return $lastResult;
            }

            $lastResult = [
                'success' => $statusCode >= 200 && $statusCode < 300,
                'status_code' => $statusCode,
                'raw_response_text' => $rawResponse,
                'parsed_response' => $parsedResponse,
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                'error_message' => $statusCode >= 200 && $statusCode < 300
                    ? null
                    : ($errorMessage ?: ('OCR request failed with status ' . $statusCode . '.')),
            ];

            if ($lastResult['success'] === true || !$this->shouldRetry($lastResult, $attempt, $attempts)) {
                $this->logger->info('OCR verification API response received.', [
                    'endpoint' => $endpoint,
                    'request_id' => $requestId !== '' ? $requestId : null,
                    'status_code' => $statusCode,
                    'duration_ms' => $lastResult['duration_ms'],
                ]);

                return $lastResult;
            }

            usleep(250000);
        }

        return $lastResult;
    }

    /**
     * @param array<string, mixed> $document
     * @return \CURLFile
     */
    private function buildUploadFile(array $document, string $fieldName): \CURLFile
    {
        $storagePath = trim((string) ($document['storage_path'] ?? ''));
        if ($storagePath === '') {
            throw new AppException(sprintf('Storage path is missing for %s.', $fieldName), 422, 'OCR_STORAGE_PATH_MISSING');
        }

        $absolutePath = $this->storageProvider->resolvePath($storagePath);
        if (!is_file($absolutePath)) {
            throw new AppException(sprintf('Stored file is missing for %s.', $fieldName), 422, 'OCR_STORAGE_FILE_MISSING');
        }

        $mimeType = trim((string) ($document['mime_type'] ?? 'application/octet-stream'));
        $postName = trim((string) ($document['storage_file_name'] ?? ''));
        if ($postName === '') {
            $postName = basename($absolutePath);
        }

        return new \CURLFile($absolutePath, $mimeType !== '' ? $mimeType : 'application/octet-stream', $postName);
    }

    /**
     * @param array<string, mixed>|null $parsedResponse
     */
    private function extractResponseMessage(?array $parsedResponse, int $statusCode): string
    {
        if (!is_array($parsedResponse)) {
            return $statusCode > 0 ? 'OCR request failed with status ' . $statusCode . '.' : 'The OCR request failed.';
        }

        foreach (['detail', 'message', 'error'] as $key) {
            $value = $parsedResponse[$key] ?? null;
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        if (isset($parsedResponse['detail']) && is_array($parsedResponse['detail'])) {
            $detail = json_encode($parsedResponse['detail'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if (is_string($detail) && $detail !== '') {
                return $detail;
            }
        }

        return $statusCode > 0 ? 'OCR request failed with status ' . $statusCode . '.' : 'The OCR request failed.';
    }

    /**
     * @param array<string, mixed> $result
     */
    private function shouldRetry(array $result, int $attempt, int $attempts): bool
    {
        if ($attempt >= $attempts) {
            return false;
        }

        $statusCode = (int) ($result['status_code'] ?? 0);
        $errorMessage = trim((string) ($result['error_message'] ?? ''));

        return $statusCode >= 500 || ($statusCode === 0 && $errorMessage !== '');
    }
}
