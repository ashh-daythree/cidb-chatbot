<?php

declare(strict_types=1);

namespace Cidb\Backend\Bootstrap;

use Cidb\Backend\Config\Configuration;
use Cidb\Backend\Config\DatabaseConnection;
use Cidb\Backend\Config\AppConfig;
use Cidb\Backend\Config\CimsConfig;
use Cidb\Backend\Config\DatabaseConfig;
use Cidb\Backend\Config\OcrConfig;
use Cidb\Backend\Config\LoggingConfig;
use Cidb\Backend\Config\StorageConfig;
use Cidb\Backend\Config\UploadConfig;
use Cidb\Backend\Migrations\MigrationManager;
use Cidb\Backend\Repositories\MigrationHistoryRepository;
use Cidb\Backend\Services\OcrVerificationService;
use Cidb\Backend\Storage\DocumentStorageInterface;
use Cidb\Backend\Storage\LocalDocumentStorage;
use Cidb\Backend\Utils\ErrorHandler;
use Cidb\Backend\Utils\Logger;

final class Bootstrap
{
    public static function create(string $basePath): Container
    {
        $container = new Container();

        $configuration = Configuration::fromEnvFile($basePath . DIRECTORY_SEPARATOR . '.env');
        $container->instance(Configuration::class, $configuration);
        $container->instance(AppConfig::class, $configuration->app());
        $container->instance(DatabaseConfig::class, $configuration->database());
        $container->instance(StorageConfig::class, $configuration->storage());
        $container->instance(LoggingConfig::class, $configuration->logging());
        $container->instance(CimsConfig::class, $configuration->cims());
        $container->instance(UploadConfig::class, UploadConfig::fromEnv());
        $container->instance(OcrConfig::class, OcrConfig::fromEnv());

        $container->set(DatabaseConnection::class, static fn (Container $container): DatabaseConnection => new DatabaseConnection(
            $container->get(Configuration::class)->database()
        ));

        $container->set(Logger::class, static fn (Container $container): Logger => new Logger(
            $container->get(Configuration::class)->logging()->path(),
            $container->get(Configuration::class)->logging()->level()
        ));

        $container->set(ErrorHandler::class, static fn (Container $container): ErrorHandler => new ErrorHandler(
            $container->get(Logger::class),
            $container->get(Configuration::class)->app()->debug()
        ));

        $container->set(MigrationHistoryRepository::class, static fn (Container $container): MigrationHistoryRepository => new MigrationHistoryRepository(
            $container->get(DatabaseConnection::class)
        ));

        $container->set(MigrationManager::class, static fn (Container $container): MigrationManager => new MigrationManager(
            $container->get(DatabaseConnection::class),
            $basePath . DIRECTORY_SEPARATOR . 'backend' . DIRECTORY_SEPARATOR . 'migrations'
        ));

        $container->set(DocumentStorageInterface::class, static fn (Container $container): DocumentStorageInterface => match ($container->get(UploadConfig::class)->storageDriver()) {
            'local' => new LocalDocumentStorage($container->get(StorageConfig::class)),
            default => new LocalDocumentStorage($container->get(StorageConfig::class)),
        });

        $container->set(OcrVerificationService::class, static fn (Container $container): OcrVerificationService => new OcrVerificationService(
            $container->get(DocumentStorageInterface::class),
            $container->get(OcrConfig::class),
            $container->get(Logger::class)
        ));

        return $container;
    }
}
