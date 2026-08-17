<?php

declare(strict_types=1);

namespace Cidb\Backend\Config;

final class OcrConfig
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $verifyEndpoint,
        private readonly int $connectTimeoutMilliseconds,
        private readonly int $timeoutMilliseconds,
        private readonly int $retryCount,
        private readonly string $apiKey
    ) {
    }

    public static function fromEnv(): self
    {
        return new self(
            rtrim((string) EnvironmentLoader::get('OCR_SERVICE_BASE_URL', 'http://127.0.0.1:8000'), '/'),
            '/' . ltrim((string) EnvironmentLoader::get('OCR_SERVICE_VERIFY_ENDPOINT', '/api/verify-document'), '/'),
            max(1000, (int) EnvironmentLoader::get('OCR_SERVICE_CONNECT_TIMEOUT_MS', 5000)),
            max(1000, (int) EnvironmentLoader::get('OCR_SERVICE_TIMEOUT_MS', 120000)),
            max(0, (int) EnvironmentLoader::get('OCR_SERVICE_RETRY_COUNT', 0)),
            trim((string) EnvironmentLoader::get('OCR_SERVICE_API_KEY', ''))
        );
    }

    public function baseUrl(): string
    {
        return $this->baseUrl;
    }

    public function verifyEndpoint(): string
    {
        return $this->verifyEndpoint;
    }

    public function verifyUrl(): string
    {
        return rtrim($this->baseUrl, '/') . $this->verifyEndpoint;
    }

    public function connectTimeoutMilliseconds(): int
    {
        return $this->connectTimeoutMilliseconds;
    }

    public function timeoutMilliseconds(): int
    {
        return $this->timeoutMilliseconds;
    }

    public function retryCount(): int
    {
        return $this->retryCount;
    }

    public function apiKey(): string
    {
        return $this->apiKey;
    }
}
