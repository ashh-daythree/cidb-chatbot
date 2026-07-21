<?php

declare(strict_types=1);

namespace Cidb\Backend\Config;

final class StorageConfig
{
    public function __construct(
        private readonly string $storagePath,
        private readonly string $logPath
    ) {
    }

    public static function fromEnv(): self
    {
        return new self(
            (string) EnvironmentLoader::get('STORAGE_PATH', 'C:/CIDB-RUNTIME/storage'),
            (string) EnvironmentLoader::get('LOG_PATH', 'C:/CIDB-RUNTIME/logs')
        );
    }

    public function storagePath(): string
    {
        return $this->storagePath;
    }

    public function logPath(): string
    {
        return $this->logPath;
    }
}
