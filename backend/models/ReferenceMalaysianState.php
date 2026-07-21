<?php

declare(strict_types=1);

namespace Cidb\Backend\Models;

final class ReferenceMalaysianState extends AbstractModel
{
    public function tableName(): string
    {
        return 'reference_malaysian_states';
    }

    public function primaryKey(): string
    {
        return 'state_code';
    }

    public function fillable(): array
    {
        return [
            'state_name',
            'display_order',
            'is_active',
            'created_at',
            'updated_at',
        ];
    }

    public function relationships(): array
    {
        return [
            'applicants' => $this->hasMany(ChatbotApplicant::class, 'state_code', 'state_code'),
        ];
    }
}

