<?php

declare(strict_types=1);

namespace Cidb\Backend\Repositories;

final class ServiceRequestRepository extends BaseRepository
{
    protected function tableName(): string
    {
        return 'service_requests';
    }

    public function findBySessionId(string $sessionId): ?array
    {
        return $this->findOneBy(['session_id' => $sessionId]);
    }

    public function findByRequestNumber(string $requestNumber): ?array
    {
        return $this->findOneBy(['request_number' => $requestNumber]);
    }

    public function findByApplicantId(string $applicantId, int $limit = 100, int $offset = 0): array
    {
        return $this->findAll(['applicant_id' => $applicantId], $limit, $offset, 'created_at', 'DESC');
    }

    public function findByStatus(string $status, int $limit = 100, int $offset = 0): array
    {
        return $this->findAll(['status' => $status], $limit, $offset, 'created_at', 'DESC');
    }

    public function findByCimsStatus(string $latestCimsStatus, int $limit = 100, int $offset = 0): array
    {
        return $this->findAll(['latest_cims_status' => $latestCimsStatus], $limit, $offset, 'created_at', 'DESC');
    }

    public function searchByRequestNumber(string $term, int $limit = 100, int $offset = 0): array
    {
        return $this->search(['request_number'], $term, [], $limit, $offset, 'created_at', 'DESC');
    }
}

