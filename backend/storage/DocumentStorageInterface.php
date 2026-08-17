<?php

declare(strict_types=1);

namespace Cidb\Backend\Storage;

interface DocumentStorageInterface
{
    public function storeFile(string $key, string $sourcePath): string;

    public function resolvePath(string $key): string;

    public function delete(string $key): bool;

    public function exists(string $key): bool;
}
