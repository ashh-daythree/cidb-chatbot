<?php

declare(strict_types=1);

namespace Cidb\Backend\Models;

final class ReferenceRequestType extends AbstractModel
{
    public function tableName(): string
    {
        return 'reference_request_types';
    }

    public function primaryKey(): string
    {
        return 'request_type_code';
    }

    public function fillable(): array
    {
        return [
            'label_en',
            'label_ms',
            'description',
            'is_active',
            'created_at',
            'updated_at',
        ];
    }

    public function relationships(): array
    {
        return [
            'serviceRequests' => $this->hasMany(ServiceRequest::class, 'request_type_code', 'request_type_code'),
        ];
    }
}

