<?php

declare(strict_types=1);

namespace Cidb\Backend\Models;

final class ChatbotWorkflow extends AbstractModel
{
    public function tableName(): string
    {
        return 'chatbot_workflows';
    }

    public function primaryKey(): string
    {
        return 'id';
    }

    public function fillable(): array
    {
        return [
            'workflow_code',
            'workflow_name',
            'description',
            'version',
            'is_active',
            'created_at',
            'updated_at',
        ];
    }

    public function relationships(): array
    {
        return [
            'sessions' => $this->hasMany(ChatbotSession::class, 'workflow_id'),
            'serviceRequests' => $this->hasMany(ServiceRequest::class, 'workflow_id'),
        ];
    }
}

