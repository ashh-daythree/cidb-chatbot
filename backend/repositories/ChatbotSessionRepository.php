<?php

declare(strict_types=1);

namespace Cidb\Backend\Repositories;

final class ChatbotSessionRepository extends BaseRepository
{
    protected function tableName(): string
    {
        return 'chatbot_sessions';
    }

    public function findByWorkflowId(string $workflowId, int $limit = 100, int $offset = 0): array
    {
        return $this->findAll(['workflow_id' => $workflowId], $limit, $offset, 'last_activity_at', 'DESC');
    }

    public function findByLanguageCode(string $languageCode, int $limit = 100, int $offset = 0): array
    {
        return $this->findAll(['language_code' => $languageCode], $limit, $offset, 'created_at', 'DESC');
    }

    public function findByStatus(string $status, int $limit = 100, int $offset = 0): array
    {
        return $this->findAll(['status' => $status], $limit, $offset, 'last_activity_at', 'DESC');
    }

    public function findRecentByWorkflowId(string $workflowId, int $limit = 25): array
    {
        return $this->findAll(['workflow_id' => $workflowId], $limit, 0, 'last_activity_at', 'DESC');
    }
}

