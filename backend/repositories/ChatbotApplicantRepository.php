<?php

declare(strict_types=1);

namespace Cidb\Backend\Repositories;

final class ChatbotApplicantRepository extends BaseRepository
{
    protected function tableName(): string
    {
        return 'chatbot_applicants';
    }

    public function findBySessionId(string $sessionId): ?array
    {
        return $this->findOneBy(['session_id' => $sessionId]);
    }

    public function findByIdentityNumber(string $identityNumber): ?array
    {
        return $this->findOneBy(['identity_number' => $identityNumber]);
    }

    public function findByStateCode(string $stateCode, int $limit = 100, int $offset = 0): array
    {
        return $this->findAll(['state_code' => $stateCode], $limit, $offset, 'created_at', 'DESC');
    }
}
