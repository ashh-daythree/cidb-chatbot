<?php

declare(strict_types=1);

namespace Cidb\Backend\Repositories;

final class ChatbotAssistanceRequestRepository extends BaseRepository
{
    protected function tableName(): string
    {
        return 'chatbot_assistance_requests';
    }

    public function findBySessionId(string $sessionId): array
    {
        return $this->findAll(['session_id' => $sessionId], 100, 0, 'created_at', 'DESC');
    }
}
