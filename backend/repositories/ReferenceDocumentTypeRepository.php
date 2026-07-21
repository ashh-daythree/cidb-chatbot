<?php

declare(strict_types=1);

namespace Cidb\Backend\Repositories;

final class ReferenceDocumentTypeRepository extends BaseRepository
{
    protected function tableName(): string
    {
        return 'reference_document_types';
    }

    protected function primaryKey(): string
    {
        return 'document_type_code';
    }

    public function findActiveByCode(string $documentTypeCode): ?array
    {
        return $this->findOneBy([
            'document_type_code' => $documentTypeCode,
            'is_active' => true,
        ]);
    }

    public function findActiveDocumentTypes(): array
    {
        return $this->findAll(['is_active' => true], 100, 0, 'sort_order', 'ASC');
    }
}

