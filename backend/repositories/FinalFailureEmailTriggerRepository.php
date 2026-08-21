<?php

declare(strict_types=1);

namespace Cidb\Backend\Repositories;

final class FinalFailureEmailTriggerRepository extends BaseRepository
{
    protected function tableName(): string
    {
        return 'final_failure_email_triggers';
    }

    public function findBySessionAndFailureType(string $sessionId, string $failureType): ?array
    {
        return $this->findOneBy([
            'session_id' => $sessionId,
            'failure_type' => $failureType,
        ]);
    }
}
