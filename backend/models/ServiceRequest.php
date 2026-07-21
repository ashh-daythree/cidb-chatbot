<?php

declare(strict_types=1);

namespace Cidb\Backend\Models;

final class ServiceRequest extends AbstractModel
{
    public function tableName(): string
    {
        return 'service_requests';
    }

    public function primaryKey(): string
    {
        return 'id';
    }

    public function fillable(): array
    {
        return [
            'request_number',
            'workflow_id',
            'session_id',
            'applicant_id',
            'request_type_code',
            'status',
            'submission_language_code',
            'submitted_at',
            'latest_cims_status',
            'final_outcome',
            'final_outcome_at',
            'closed_at',
            'created_at',
            'updated_at',
        ];
    }

    public function relationships(): array
    {
        return [
            'workflow' => $this->belongsTo(ChatbotWorkflow::class, 'workflow_id'),
            'session' => $this->belongsTo(ChatbotSession::class, 'session_id'),
            'applicant' => $this->belongsTo(ChatbotApplicant::class, 'applicant_id'),
            'requestType' => $this->belongsTo(ReferenceRequestType::class, 'request_type_code', 'request_type_code'),
            'language' => $this->belongsTo(ReferenceLanguage::class, 'submission_language_code', 'code'),
            'uploadedDocuments' => $this->hasMany(UploadedDocument::class, 'request_id'),
            'cimsResults' => $this->hasMany(CimsVerificationResult::class, 'request_id'),
            'statusHistory' => $this->hasMany(ChatbotStatusHistory::class, 'request_id'),
            'auditLogs' => $this->hasMany(ChatbotAuditLog::class, 'request_id'),
        ];
    }
}

