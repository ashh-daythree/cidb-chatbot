<?php

declare(strict_types=1);

namespace Cidb\Backend\Models;

final class ReferenceLanguage extends AbstractModel
{
    public function tableName(): string
    {
        return 'reference_languages';
    }

    public function primaryKey(): string
    {
        return 'code';
    }

    public function fillable(): array
    {
        return [
            'language_name',
            'locale_tag',
            'is_active',
            'created_at',
            'updated_at',
        ];
    }

    public function relationships(): array
    {
        return [
            'sessions' => $this->hasMany(ChatbotSession::class, 'language_code', 'code'),
            'applicants' => $this->hasMany(ChatbotApplicant::class, 'language_code', 'code'),
            'serviceRequests' => $this->hasMany(ServiceRequest::class, 'submission_language_code', 'code'),
        ];
    }
}

