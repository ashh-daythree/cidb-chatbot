<?php

declare(strict_types=1);

namespace Cidb\Backend\Config;

final class UploadConfig
{
    public function __construct(
        private readonly string $storageDriver,
        private readonly int $maxFileSizeMb,
        private readonly int $signatureMaxFileSizeMb
    ) {
    }

    public static function fromEnv(): self
    {
        return new self(
            (string) EnvironmentLoader::get('UPLOAD_STORAGE_DRIVER', 'local'),
            max(1, (int) EnvironmentLoader::get('UPLOAD_MAX_FILE_SIZE_MB', 10)),
            max(1, (int) EnvironmentLoader::get('SIGNATURE_MAX_FILE_SIZE_MB', 5))
        );
    }

    public function storageDriver(): string
    {
        return $this->storageDriver;
    }

    public function maxFileSizeMb(): int
    {
        return $this->maxFileSizeMb;
    }

    public function signatureMaxFileSizeMb(): int
    {
        return $this->signatureMaxFileSizeMb;
    }

    public function maxFileSizeBytes(): int
    {
        return $this->maxFileSizeMb * 1024 * 1024;
    }

    public function signatureMaxFileSizeBytes(): int
    {
        return $this->signatureMaxFileSizeMb * 1024 * 1024;
    }
}
