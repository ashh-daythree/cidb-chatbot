<?php

declare(strict_types=1);

namespace Cidb\Backend\Config;

final class DatabaseConfig
{
    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly string $database,
        private readonly string $username,
        private readonly string $password,
        private readonly int $timeoutSeconds
    ) {
    }

    public static function fromEnv(): self
    {
        return new self(
            (string) EnvironmentLoader::get('DB_HOST', '127.0.0.1'),
            (int) EnvironmentLoader::get('DB_PORT', 5432),
            (string) EnvironmentLoader::get('DB_DATABASE', ''),
            (string) EnvironmentLoader::get('DB_USERNAME', ''),
            (string) EnvironmentLoader::get('DB_PASSWORD', ''),
            max(1, (int) EnvironmentLoader::get('DB_TIMEOUT', 5))
        );
    }

    public function host(): string
    {
        return $this->host;
    }

    public function port(): int
    {
        return $this->port;
    }

    public function database(): string
    {
        return $this->database;
    }

    public function username(): string
    {
        return $this->username;
    }

    public function password(): string
    {
        return $this->password;
    }

    public function timeoutSeconds(): int
    {
        return $this->timeoutSeconds;
    }
}

