<?php

declare(strict_types=1);

namespace Cidb\Backend\Models;

final class ChatbotStatusHistory extends AbstractModel
{
    public function tableName(): string
    {
        return 'chatbot_status_history';
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
            'document_id',
            'status_scope',
            'from_status',
            'to_status',
            'changed_by_type',
            'changed_by_reference',
            'reason_code',
            'reason_message',
            'metadata',
            'created_at',
        ];
    }

    public function relationships(): array
    {
        return [
            'session' => $this->belongsTo(ChatbotSession::class, 'session_id'),
            'serviceRequest' => $this->belongsTo(ServiceRequest::class, 'request_id'),
            'uploadedDocument' => $this->belongsTo(UploadedDocument::class, 'document_id'),
        ];
    }
}

