<?php

declare(strict_types=1);

namespace Cidb\Backend\Migrations;

use Cidb\Backend\Config\DatabaseConnection;
use Cidb\Backend\Repositories\MigrationHistoryRepository;
use PDO;
use RuntimeException;

final class MigrationExecutor
{
    public function __construct(
        private readonly DatabaseConnection $connection,
        private readonly MigrationHistoryRepository $historyRepository
    ) {
    }

    public function ensureHistoryTable(): void
    {
        $this->connection->pdo()->exec(
            'CREATE EXTENSION IF NOT EXISTS pgcrypto'
        );

        $this->connection->pdo()->exec(
            <<<SQL
            CREATE TABLE IF NOT EXISTS migration_history (
                id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
                migration_name varchar(255) NOT NULL UNIQUE,
                batch_no integer NOT NULL DEFAULT 1,
                checksum char(64),
                execution_time_ms integer NOT NULL DEFAULT 0,
                applied_at timestamptz NOT NULL DEFAULT now()
            )
            SQL
        );

        $this->connection->pdo()->exec(
            'CREATE INDEX IF NOT EXISTS idx_migration_history_batch_no ON migration_history (batch_no)'
        );
    }

    public function appliedMigrations(): array
    {
        return $this->historyRepository->findAll([], 10000, 0, 'applied_at', 'ASC');
    }

    public function hasRun(string $migrationName): bool
    {
        return $this->historyRepository->findOneBy(['migration_name' => $migrationName]) !== null;
    }

    public function run(MigrationInterface $migration): void
    {
        if ($this->hasRun($migration->name())) {
            return;
        }

        $pdo = $this->connection->pdo();
        $start = microtime(true);

        $this->connection->beginTransaction();

        try {
            $migration->up($pdo);

            $elapsedMs = (int) round((microtime(true) - $start) * 1000);
            $this->historyRepository->insert([
                'migration_name' => $migration->name(),
                'batch_no' => $this->nextBatchNumber(),
                'checksum' => null,
                'execution_time_ms' => $elapsedMs,
                'applied_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:sP'),
            ]);

            $this->connection->commit();
        } catch (\Throwable $throwable) {
            $this->connection->rollback();

            throw new RuntimeException(
                sprintf('Migration "%s" failed: %s', $migration->name(), $throwable->getMessage()),
                (int) $throwable->getCode(),
                $throwable
            );
        }
    }

    private function nextBatchNumber(): int
    {
        $statement = $this->connection->pdo()->query('SELECT COALESCE(MAX(batch_no), 0) + 1 AS next_batch FROM migration_history');
        $result = $statement->fetch(PDO::FETCH_ASSOC);

        return isset($result['next_batch']) ? (int) $result['next_batch'] : 1;
    }
}
