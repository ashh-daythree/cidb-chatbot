<?php

declare(strict_types=1);

namespace Cidb\Backend\Models;

final class FinalFailureEmailTrigger extends AbstractModel
{
    public function tableName(): string
    {
        return 'final_failure_email_triggers';
    }

    public function primaryKey(): string
    {
        return 'id';
    }

    public function fillable(): array
    {
        return [
            'session_id',
            'request_id',
            'failure_type',
            'service_type',
            'attempt_no',
            'status',
            'payload',
            'response_code',
            'response_message',
            'response_payload',
            'detected_at',
            'triggered_at',
            'completed_at',
            'created_at',
            'updated_at',
        ];
    }

    public function relationships(): array
    {
        return [
            'session' => $this->belongsTo(ChatbotSession::class, 'session_id'),
            'serviceRequest' => $this->belongsTo(ServiceRequest::class, 'request_id'),
        ];
    }
}
