<?php

declare(strict_types=1);

namespace Cidb\Backend\Repositories;

final class ReferenceLanguageRepository extends BaseRepository
{
    protected function tableName(): string
    {
        return 'reference_languages';
    }

    protected function primaryKey(): string
    {
        return 'code';
    }

    public function findActiveByCode(string $code): ?array
    {
        return $this->findOneBy([
            'code' => $code,
            'is_active' => true,
        ]);
    }

    public function findActiveLanguages(): array
    {
        return $this->findAll(['is_active' => true], 100, 0, 'code', 'ASC');
    }
}

