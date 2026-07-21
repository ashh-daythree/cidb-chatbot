<?php

declare(strict_types=1);

namespace Cidb\Backend\Config;

final class Configuration
{
    public function __construct(
        private readonly AppConfig $app,
        private readonly DatabaseConfig $database,
        private readonly StorageConfig $storage,
        private readonly LoggingConfig $logging,
        private readonly CimsConfig $cims
    ) {
    }

    public static function fromEnvFile(string $envFilePath): self
    {
        EnvironmentLoader::load($envFilePath);

        $app = AppConfig::fromEnv();
        $database = DatabaseConfig::fromEnv();
        $storage = StorageConfig::fromEnv();
        $logging = LoggingConfig::fromEnv($storage, $app);
        $cims = CimsConfig::fromEnv();

        return new self($app, $database, $storage, $logging, $cims);
    }

    public function app(): AppConfig
    {
        return $this->app;
    }

    public function database(): DatabaseConfig
    {
        return $this->database;
    }

    public function storage(): StorageConfig
    {
        return $this->storage;
    }

    public function logging(): LoggingConfig
    {
        return $this->logging;
    }

    public function cims(): CimsConfig
    {
        return $this->cims;
    }
}

