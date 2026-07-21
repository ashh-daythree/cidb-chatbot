<?php

declare(strict_types=1);

namespace Cidb\Backend\Models;

final class ChatbotSession extends AbstractModel
{
    public function tableName(): string
    {
        return 'chatbot_sessions';
    }

    public function primaryKey(): string
    {
        return 'id';
    }

    public function fillable(): array
    {
        return [
            'workflow_id',
            'language_code',
            'status',
            'current_step',
            'draft_payload',
            'started_at',
            'last_activity_at',
            'completed_at',
            'expired_at',
            'created_at',
            'updated_at',
        ];
    }

    public function relationships(): array
    {
        return [
            'workflow' => $this->belongsTo(ChatbotWorkflow::class, 'workflow_id'),
            'language' => $this->belongsTo(ReferenceLanguage::class, 'language_code', 'code'),
            'applicant' => $this->hasOne(ChatbotApplicant::class, 'session_id'),
            'serviceRequest' => $this->hasOne(ServiceRequest::class, 'session_id'),
            'uploadedDocuments' => $this->hasMany(UploadedDocument::class, 'session_id'),
            'statusHistory' => $this->hasMany(ChatbotStatusHistory::class, 'session_id'),
            'auditLogs' => $this->hasMany(ChatbotAuditLog::class, 'session_id'),
        ];
    }
}

