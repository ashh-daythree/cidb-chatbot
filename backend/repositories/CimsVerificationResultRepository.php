<?php

declare(strict_types=1);

namespace Cidb\Backend\Repositories;

final class CimsVerificationResultRepository extends BaseRepository
{
    protected function tableName(): string
    {
        return 'cims_verification_results';
    }

    public function findByRequestId(string $requestId, int $limit = 100, int $offset = 0): array
    {
        return $this->findAll(['request_id' => $requestId], $limit, $offset, 'attempt_no', 'DESC');
    }

    public function findLatestByRequestId(string $requestId): ?array
    {
        return $this->findAll(['request_id' => $requestId], 1, 0, 'attempt_no', 'DESC')[0] ?? null;
    }

    public function findByResultStatus(string $resultStatus, int $limit = 100, int $offset = 0): array
    {
        return $this->findAll(['result_status' => $resultStatus], $limit, $offset, 'created_at', 'DESC');
    }
}

