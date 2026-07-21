<?php

declare(strict_types=1);

namespace Cidb\Backend\Config;

final class AppConfig
{
    public function __construct(
        private readonly string $name,
        private readonly string $environment,
        private readonly bool $debug,
        private readonly string $url
    ) {
    }

    public static function fromEnv(): self
    {
        return new self(
            (string) EnvironmentLoader::get('APP_NAME', 'CIDB Chatbot'),
            (string) EnvironmentLoader::get('APP_ENV', 'local'),
            filter_var(EnvironmentLoader::get('APP_DEBUG', false), FILTER_VALIDATE_BOOL),
            (string) EnvironmentLoader::get('APP_URL', 'http://localhost')
        );
    }

    public function name(): string
    {
        return $this->name;
    }

    public function environment(): string
    {
        return $this->environment;
    }

    public function debug(): bool
    {
        return $this->debug;
    }

    public function url(): string
    {
        return $this->url;
    }
}

