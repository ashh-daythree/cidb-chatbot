<?php

declare(strict_types=1);

namespace Cidb\Backend\Config;

final class LoggingConfig
{
    public function __construct(
        private readonly string $path,
        private readonly string $level
    ) {
    }

    public static function fromEnv(StorageConfig $storageConfig, AppConfig $appConfig): self
    {
        $defaultLevel = match ($appConfig->environment()) {
            'production' => 'warning',
            'testing' => 'error',
            default => 'debug',
        };

        return new self(
            $storageConfig->logPath(),
            (string) EnvironmentLoader::get('LOG_LEVEL', $defaultLevel)
        );
    }

    public function path(): string
    {
        return $this->path;
    }

    public function level(): string
    {
        return $this->level;
    }
}

