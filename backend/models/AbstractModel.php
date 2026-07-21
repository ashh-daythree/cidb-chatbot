<?php

declare(strict_types=1);

namespace Cidb\Backend\Models;

abstract class AbstractModel
{
    /**
     * @return non-empty-string
     */
    abstract public function tableName(): string;

    /**
     * @return non-empty-string
     */
    abstract public function primaryKey(): string;

    /**
     * @return array<int, string>
     */
    abstract public function fillable(): array;

    /**
     * @return array<string, array<string, mixed>>
     */
    abstract public function relationships(): array;

    public function usesTimestamps(): bool
    {
        return true;
    }

    /**
     * @return array{type: string, model: class-string<AbstractModel>, foreign_key: string, local_key: string}
     */
    protected function belongsTo(string $relatedModel, string $foreignKey, string $ownerKey = 'id'): array
    {
        return [
            'type' => 'belongsTo',
            'model' => $relatedModel,
            'foreign_key' => $foreignKey,
            'local_key' => $ownerKey,
        ];
    }

    /**
     * @return array{type: string, model: class-string<AbstractModel>, foreign_key: string, local_key: string}
     */
    protected function hasOne(string $relatedModel, string $foreignKey, string $localKey = 'id'): array
    {
        return [
            'type' => 'hasOne',
            'model' => $relatedModel,
            'foreign_key' => $foreignKey,
            'local_key' => $localKey,
        ];
    }

    /**
     * @return array{type: string, model: class-string<AbstractModel>, foreign_key: string, local_key: string}
     */
    protected function hasMany(string $relatedModel, string $foreignKey, string $localKey = 'id'): array
    {
        return [
            'type' => 'hasMany',
            'model' => $relatedModel,
            'foreign_key' => $foreignKey,
            'local_key' => $localKey,
        ];
    }
}

