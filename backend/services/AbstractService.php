<?php

declare(strict_types=1);

namespace Cidb\Backend\Services;

use Cidb\Backend\Config\DatabaseConnection;
use Cidb\Backend\Utils\Exceptions\AppException;
use Throwable;

abstract class AbstractService
{
    public function __construct(protected readonly DatabaseConnection $connection)
    {
    }

    /**
     * @template T
     *
     * @param callable():T $callback
     * @return T
     */
    protected function transactional(callable $callback): mixed
    {
        $pdo = $this->connection->pdo();
        $startedTransactionHere = false;

        if (!$pdo->inTransaction()) {
            $pdo->beginTransaction();
            $startedTransactionHere = true;
        }

        try {
            $result = $callback();

            if ($startedTransactionHere) {
                $pdo->commit();
            }

            return $result;
        } catch (Throwable $throwable) {
            if ($startedTransactionHere && $pdo->inTransaction()) {
                $pdo->rollBack();
            }

            if ($throwable instanceof AppException) {
                throw $throwable;
            }

            throw new AppException(
                $throwable->getMessage() !== '' ? $throwable->getMessage() : 'Service operation failed.',
                500,
                'SERVICE_ERROR',
                [],
                $throwable
            );
        }
    }

    protected function now(): string
    {
        return (new \DateTimeImmutable())->format('Y-m-d H:i:sP');
    }

    protected function uuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }

    /**
     * @param array<int, mixed> $candidates
     */
    protected function resolveBotPayloadValue(array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if (!is_string($candidate)) {
                continue;
            }

            $value = trim($candidate);
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }
}
