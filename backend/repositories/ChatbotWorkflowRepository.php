<?php

declare(strict_types=1);

namespace Cidb\Backend\Repositories;

final class ChatbotWorkflowRepository extends BaseRepository
{
    protected function tableName(): string
    {
        return 'chatbot_workflows';
    }

    public function findActiveByCode(string $workflowCode): ?array
    {
        return $this->findOneBy([
            'workflow_code' => $workflowCode,
            'is_active' => true,
        ]);
    }

    public function findActiveWorkflows(): array
    {
        return $this->findAll(['is_active' => true], 100, 0, 'workflow_code', 'ASC');
    }
}

