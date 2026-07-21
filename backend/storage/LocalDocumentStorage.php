<?php

declare(strict_types=1);

namespace Cidb\Backend\Storage;

use Cidb\Backend\Config\StorageConfig;
use RuntimeException;

final class LocalDocumentStorage implements DocumentStorageInterface
{
    public function __construct(
        private readonly StorageConfig $storageConfig
    ) {
    }

    public function storeFile(string $key, string $sourcePath): string
    {
        $targetPath = $this->absolutePath($key);
        $directory = dirname($targetPath);

        if (!is_dir($directory) && !@mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException(sprintf('Unable to create storage directory "%s".', $directory));
        }

        if (!is_file($sourcePath)) {
            throw new RuntimeException(sprintf('Source file "%s" is missing.', $sourcePath));
        }

        if (!@copy($sourcePath, $targetPath)) {
            throw new RuntimeException(sprintf('Unable to store file "%s".', $key));
        }

        return $key;
    }

    public function delete(string $key): bool
    {
        $path = $this->absolutePath($key);
        if (!is_file($path)) {
            return true;
        }

        return @unlink($path);
    }

    public function exists(string $key): bool
    {
        return is_file($this->absolutePath($key));
    }

    private function absolutePath(string $key): string
    {
        $normalizedKey = ltrim(str_replace(['\\', '..'], ['/', ''], $key), '/');

        return rtrim($this->storageConfig->storagePath(), DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, $normalizedKey);
    }
}
