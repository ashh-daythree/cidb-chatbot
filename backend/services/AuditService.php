<?php

declare(strict_types=1);

namespace Cidb\Backend\Services;

use Cidb\Backend\Repositories\ChatbotAuditLogRepository;
use Cidb\Backend\Utils\JsonHelper;

final class AuditService extends AbstractService
{
    public function __construct(
        \Cidb\Backend\Config\DatabaseConnection $connection,
        private readonly ChatbotAuditLogRepository $auditLogRepository
    ) {
        parent::__construct($connection);
    }

    public function record(
        string $eventType,
        string $message,
        array $context = [],
        string $severity = 'info',
        ?string $sessionId = null,
        ?string $requestId = null,
        string $actorType = 'system',
        ?string $actorReference = null
    ): array {
        return $this->transactional(function () use ($eventType, $message, $context, $severity, $sessionId, $requestId, $actorType, $actorReference): array {
            $row = $this->auditLogRepository->insert([
                'correlation_id' => $context['correlation_id'] ?? $this->uuid(),
                'session_id' => $sessionId,
                'request_id' => $requestId,
                'event_type' => $eventType,
                'severity' => $severity,
                'actor_type' => $actorType,
                'actor_reference' => $actorReference,
                'message' => $message,
                'masked_payload' => JsonHelper::encode($this->maskContext($context), true),
                'created_at' => $this->now(),
            ]);

            return $row;
        });
    }

    public function recordValidationFailure(string $eventType, array $errors, ?string $sessionId = null, ?string $requestId = null): array
    {
        return $this->record(
            $eventType,
            'Validation failed.',
            ['errors' => $errors],
            'warning',
            $sessionId,
            $requestId,
            'system'
        );
    }

    public function recordSubmissionFailure(string $message, array $context = [], ?string $sessionId = null, ?string $requestId = null): array
    {
        return $this->record('submission_failure', $message, $context, 'error', $sessionId, $requestId, 'system');
    }

    private function maskContext(array $context): array
    {
        foreach ($context as $key => $value) {
            if (is_string($key) && preg_match('/(identity|ic|passport|name|signature|file|document)/i', $key) === 1) {
                $context[$key] = $this->maskValue($value);
                continue;
            }

            if (is_array($value)) {
                $context[$key] = $this->maskContext($value);
            }
        }

        return $context;
    }

    private function maskValue(mixed $value): mixed
    {
        if (is_string($value)) {
            $length = strlen($value);
            if ($length <= 4) {
                return str_repeat('*', $length);
            }

            return substr($value, 0, 2) . str_repeat('*', max(0, $length - 4)) . substr($value, -2);
        }

        if (is_array($value)) {
            return array_map(fn ($item) => $this->maskValue($item), $value);
        }

        return $value;
    }
}
