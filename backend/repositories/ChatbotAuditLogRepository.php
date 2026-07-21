<?php

declare(strict_types=1);

namespace Cidb\Backend\Repositories;

final class ChatbotAuditLogRepository extends BaseRepository
{
    protected function tableName(): string
    {
        return 'chatbot_audit_logs';
    }

    public function findBySessionId(string $sessionId, int $limit = 100, int $offset = 0): array
    {
        return $this->findAll(['session_id' => $sessionId], $limit, $offset, 'created_at', 'DESC');
    }

    public function findByRequestId(string $requestId, int $limit = 100, int $offset = 0): array
    {
        return $this->findAll(['request_id' => $requestId], $limit, $offset, 'created_at', 'DESC');
    }

    public function findByCorrelationId(string $correlationId, int $limit = 100, int $offset = 0): array
    {
        return $this->findAll(['correlation_id' => $correlationId], $limit, $offset, 'created_at', 'DESC');
    }

    public function findBySeverity(string $severity, int $limit = 100, int $offset = 0): array
    {
        return $this->findAll(['severity' => $severity], $limit, $offset, 'created_at', 'DESC');
    }
}

