<?php

declare(strict_types=1);

namespace Cidb\Backend\Config;

final class CimsConfig
{
    public function __construct(
        private readonly bool $enabled,
        private readonly string $baseUrl,
        private readonly string $verifyEndpoint,
        private readonly int $timeoutMilliseconds,
        private readonly string $mockMode,
        private readonly string $mockOutcome,
        private readonly string $apiKey,
        private readonly string $clientId,
        private readonly string $clientSecret
    ) {
    }

    public static function fromEnv(): self
    {
        return new self(
            filter_var(EnvironmentLoader::get('CIMS_ENABLED', false), FILTER_VALIDATE_BOOL),
            (string) EnvironmentLoader::get('CIMS_BASE_URL', ''),
            (string) EnvironmentLoader::get('CIMS_VERIFY_ENDPOINT', ''),
            max(1, (int) EnvironmentLoader::get('CIMS_TIMEOUT_MS', 15000)),
            strtolower((string) EnvironmentLoader::get('CIMS_MOCK_MODE', 'random')),
            (string) EnvironmentLoader::get('CIMS_MOCK_OUTCOME', 'deleted'),
            (string) EnvironmentLoader::get('CIMS_API_KEY', ''),
            (string) EnvironmentLoader::get('CIMS_CLIENT_ID', ''),
            (string) EnvironmentLoader::get('CIMS_CLIENT_SECRET', '')
        );
    }

    public function enabled(): bool
    {
        return $this->enabled;
    }

    public function baseUrl(): string
    {
        return $this->baseUrl;
    }

    public function verifyEndpoint(): string
    {
        return $this->verifyEndpoint;
    }

    public function timeoutMilliseconds(): int
    {
        return $this->timeoutMilliseconds;
    }

    public function mockMode(): string
    {
        return $this->mockMode;
    }

    public function mockOutcome(): string
    {
        return $this->mockOutcome;
    }

    public function apiKey(): string
    {
        return $this->apiKey;
    }

    public function clientId(): string
    {
        return $this->clientId;
    }

    public function clientSecret(): string
    {
        return $this->clientSecret;
    }
}
