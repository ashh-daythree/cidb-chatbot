<?php

declare(strict_types=1);

namespace Cidb\Backend\Migrations;

use Cidb\Backend\Config\DatabaseConnection;
use Cidb\Backend\Repositories\MigrationHistoryRepository;
use FilesystemIterator;
use RuntimeException;
use SplFileInfo;

final class MigrationManager
{
    public function __construct(
        private readonly DatabaseConnection $connection,
        private readonly string $migrationPath
    ) {
    }

    public function runPending(): array
    {
        $historyRepository = new MigrationHistoryRepository($this->connection);
        $executor = new MigrationExecutor($this->connection, $historyRepository);
        $executor->ensureHistoryTable();

        $applied = [];
        $migrations = $this->discoverMigrationFiles();

        foreach ($migrations as $migrationFile) {
            $migration = $this->loadMigration($migrationFile);

            if ($executor->hasRun($migration->name())) {
                continue;
            }

            $executor->run($migration);
            $applied[] = $migration->name();
        }

        return $applied;
    }

    private function discoverMigrationFiles(): array
    {
        if (!is_dir($this->migrationPath)) {
            return [];
        }

        $files = [];

        foreach (new FilesystemIterator($this->migrationPath, FilesystemIterator::SKIP_DOTS) as $fileInfo) {
            if (!$fileInfo instanceof SplFileInfo || $fileInfo->getExtension() !== 'php') {
                continue;
            }

            if (preg_match('/^\d{8}_/', $fileInfo->getFilename()) !== 1) {
                continue;
            }

            $files[] = $fileInfo->getPathname();
        }

        sort($files, SORT_STRING);

        return $files;
    }

    private function loadMigration(string $migrationFile): MigrationInterface
    {
        $beforeClasses = get_declared_classes();
        require_once $migrationFile;
        $afterClasses = get_declared_classes();
        $newClasses = array_values(array_diff($afterClasses, $beforeClasses));

        foreach ($newClasses as $className) {
            if (is_subclass_of($className, MigrationInterface::class)) {
                /** @var MigrationInterface $migration */
                $migration = new $className();
                return $migration;
            }
        }

        throw new RuntimeException(sprintf('No migration class found in file: %s', $migrationFile));
    }
}

