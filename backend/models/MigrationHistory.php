<?php

declare(strict_types=1);

namespace Cidb\Backend\Models;

final class MigrationHistory extends AbstractModel
{
    public function tableName(): string
    {
        return 'migration_history';
    }

    public function primaryKey(): string
    {
        return 'id';
    }

    public function fillable(): array
    {
        return [
            'migration_name',
            'batch_no',
            'checksum',
            'execution_time_ms',
            'applied_at',
        ];
    }

    public function relationships(): array
    {
        return [];
    }

    public function usesTimestamps(): bool
    {
        return false;
    }
}

