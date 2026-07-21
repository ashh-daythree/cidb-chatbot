<?php

declare(strict_types=1);

namespace Cidb\Backend\Repositories;

final class ReferenceRequestTypeRepository extends BaseRepository
{
    protected function tableName(): string
    {
        return 'reference_request_types';
    }

    protected function primaryKey(): string
    {
        return 'request_type_code';
    }

    public function findActiveByCode(string $requestTypeCode): ?array
    {
        return $this->findOneBy([
            'request_type_code' => $requestTypeCode,
            'is_active' => true,
        ]);
    }

    public function findActiveRequestTypes(): array
    {
        return $this->findAll(['is_active' => true], 100, 0, 'request_type_code', 'ASC');
    }
}

