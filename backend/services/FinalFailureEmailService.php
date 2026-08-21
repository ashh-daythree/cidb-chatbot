<?php

declare(strict_types=1);

namespace Cidb\Backend\Services;

use Cidb\Backend\Config\DatabaseConnection;
use Cidb\Backend\Repositories\FinalFailureEmailTriggerRepository;
use Cidb\Backend\Utils\Exceptions\AppException;
use Cidb\Backend\Utils\JsonHelper;
use Cidb\Backend\Utils\Logger;
use PDOException;
use Throwable;

final class FinalFailureEmailService extends AbstractService
{
    public function __construct(
        DatabaseConnection $connection,
        private readonly FinalFailureEmailTriggerRepository $triggerRepository,
        private readonly EmailRpaService $emailRpaService,
        private readonly Logger $logger
    ) {
        parent::__construct($connection);
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $trackingContext
     * @return array<string, mixed>
     */
    public function trigger(
        string $sessionId,
        ?string $requestId,
        string $failureType,
        string $serviceType,
        int $attemptNo,
        array $payload,
        array $trackingContext = []
    ): array {
        $existing = $this->triggerRepository->findBySessionAndFailureType($sessionId, $failureType);
        if (is_array($existing)) {
            $this->logger->debug('Final failure email trigger already recorded.', [
                'session_id' => $sessionId,
                'request_id' => $requestId,
                'failure_type' => $failureType,
                'status' => $existing['status'] ?? null,
            ]);

            return $existing;
        }

        $now = $this->now();
        try {
            $record = $this->triggerRepository->insert([
                'session_id' => $sessionId,
                'request_id' => $requestId,
                'failure_type' => $failureType,
                'service_type' => $serviceType,
                'attempt_no' => $attemptNo,
                'status' => 'triggering',
                'payload' => JsonHelper::encode($payload, true),
                'response_code' => null,
                'response_message' => null,
                'response_payload' => JsonHelper::encode([], true),
                'detected_at' => $now,
                'triggered_at' => $now,
                'completed_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } catch (PDOException $exception) {
            $this->logger->debug('Final failure email trigger insert conflicted.', [
                'session_id' => $sessionId,
                'request_id' => $requestId,
                'failure_type' => $failureType,
                'error' => $exception->getMessage(),
            ]);

            $existing = $this->triggerRepository->findBySessionAndFailureType($sessionId, $failureType);
            if (is_array($existing)) {
                return $existing;
            }

            throw new AppException('Unable to persist final failure email trigger.', 500, 'FINAL_FAILURE_TRIGGER_PERSIST_FAILED');
        }

        try {
            $emailResult = $this->emailRpaService->trigger($payload);
        } catch (Throwable $throwable) {
            $emailResult = [
                'success' => false,
                'status_code' => 0,
                'raw_response_text' => '',
                'parsed_response' => null,
                'duration_ms' => 0,
                'error_message' => $throwable->getMessage() !== '' ? $throwable->getMessage() : 'The email RPA request failed.',
            ];
        }

        $status = $emailResult['success'] ? 'triggered' : 'failed';
        $updated = $this->triggerRepository->update((string) ($record['id'] ?? ''), [
            'status' => $status,
            'response_code' => (string) ($emailResult['status_code'] ?? 0),
            'response_message' => (string) ($emailResult['error_message'] ?? ''),
            'response_payload' => JsonHelper::encode([
                'raw_response_text' => $emailResult['raw_response_text'] ?? '',
                'parsed_response' => $emailResult['parsed_response'] ?? null,
                'success' => $emailResult['success'] ?? false,
                'duration_ms' => $emailResult['duration_ms'] ?? null,
                'tracking_context' => $trackingContext,
            ], true),
            'completed_at' => $this->now(),
            'updated_at' => $this->now(),
        ]);

        return $updated ?? $record;
    }
}
