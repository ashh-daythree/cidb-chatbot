<?php

declare(strict_types=1);

namespace Cidb\Backend\Repositories;

final class MigrationHistoryRepository extends BaseRepository
{
    protected function tableName(): string
    {
        return 'migration_history';
    }

    public function findByMigrationName(string $migrationName): ?array
    {
        return $this->findOneBy(['migration_name' => $migrationName]);
    }

    public function findAppliedMigrations(int $limit = 1000, int $offset = 0): array
    {
        return $this->findAll([], $limit, $offset, 'applied_at', 'ASC');
    }
}
