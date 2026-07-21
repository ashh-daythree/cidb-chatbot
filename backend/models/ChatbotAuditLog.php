<?php

declare(strict_types=1);

namespace Cidb\Backend\Models;

final class ChatbotAuditLog extends AbstractModel
{
    public function tableName(): string
    {
        return 'chatbot_audit_logs';
    }

    public function primaryKey(): string
    {
        return 'id';
    }

    public function fillable(): array
    {
        return [
            'correlation_id',
            'session_id',
            'request_id',
            'event_type',
            'severity',
            'actor_type',
            'actor_reference',
            'message',
            'masked_payload',
            'ip_hash',
            'user_agent_hash',
            'created_at',
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

