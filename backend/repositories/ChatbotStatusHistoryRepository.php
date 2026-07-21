<?php

declare(strict_types=1);

namespace Cidb\Backend\Repositories;

final class ChatbotStatusHistoryRepository extends BaseRepository
{
    protected function tableName(): string
    {
        return 'chatbot_status_history';
    }

    public function findBySessionId(string $sessionId, int $limit = 100, int $offset = 0): array
    {
        return $this->findAll(['session_id' => $sessionId], $limit, $offset, 'created_at', 'DESC');
    }

    public function findByRequestId(string $requestId, int $limit = 100, int $offset = 0): array
    {
        return $this->findAll(['request_id' => $requestId], $limit, $offset, 'created_at', 'DESC');
    }

    public function findByDocumentId(string $documentId, int $limit = 100, int $offset = 0): array
    {
        return $this->findAll(['document_id' => $documentId], $limit, $offset, 'created_at', 'DESC');
    }
}

