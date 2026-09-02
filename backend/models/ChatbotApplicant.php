<?php

declare(strict_types=1);

namespace Cidb\Backend\Models;

final class ChatbotApplicant extends AbstractModel
{
    public function tableName(): string
    {
        return 'chatbot_applicants';
    }

    public function primaryKey(): string
    {
        return 'id';
    }

    public function fillable(): array
    {
        return [
            'session_id',
            'full_name_ciphertext',
            'full_name_hash',
            'identity_type',
            'identity_number_ciphertext',
            'identity_number_hash',
            'identity_number_last4',
            'state_code',
            'language_code',
            'verification_status',
            'is_draft',
            'created_at',
            'updated_at',
        ];
    }

    public function relationships(): array
    {
        return [
            'session' => $this->belongsTo(ChatbotSession::class, 'session_id'),
            'state' => $this->belongsTo(ReferenceMalaysianState::class, 'state_code', 'state_code'),
            'language' => $this->belongsTo(ReferenceLanguage::class, 'language_code', 'code'),
            'serviceRequests' => $this->hasMany(ServiceRequest::class, 'applicant_id'),
        ];
    }
}
