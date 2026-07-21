<?php

declare(strict_types=1);

namespace Cidb\Backend\Repositories;

final class ReferenceMalaysianStateRepository extends BaseRepository
{
    protected function tableName(): string
    {
        return 'reference_malaysian_states';
    }

    protected function primaryKey(): string
    {
        return 'state_code';
    }

    public function findActiveByCode(string $stateCode): ?array
    {
        return $this->findOneBy([
            'state_code' => $stateCode,
            'is_active' => true,
        ]);
    }

    public function findActiveStates(): array
    {
        return $this->findAll(['is_active' => true], 100, 0, 'display_order', 'ASC');
    }
}

