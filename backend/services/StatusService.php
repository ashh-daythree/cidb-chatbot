<?php

declare(strict_types=1);

namespace Cidb\Backend\Services;

use Cidb\Backend\Repositories\ChatbotStatusHistoryRepository;
use Cidb\Backend\Repositories\ChatbotSessionRepository;
use Cidb\Backend\Repositories\CimsVerificationResultRepository;
use Cidb\Backend\Repositories\ServiceRequestRepository;
use Cidb\Backend\Repositories\UploadedDocumentRepository;
use Cidb\Backend\Utils\JsonHelper;
use Cidb\Backend\Utils\Exceptions\AppException;

final class StatusService extends AbstractService
{
    public function __construct(
        \Cidb\Backend\Config\DatabaseConnection $connection,
        private readonly ChatbotSessionRepository $sessionRepository,
        private readonly ServiceRequestRepository $requestRepository,
        private readonly UploadedDocumentRepository $documentRepository,
        private readonly ChatbotStatusHistoryRepository $statusHistoryRepository,
        private readonly AuditService $auditService,
        private readonly ?CimsVerificationResultRepository $cimsResultRepository = null
    ) {
        parent::__construct($connection);
    }

    public function transitionSession(string $sessionId, string $toStatus, ?string $fromStatus = null, array $metadata = [], ?string $reasonCode = null, ?string $reasonMessage = null): array
    {
        return $this->transactional(function () use ($sessionId, $toStatus, $fromStatus, $metadata, $reasonCode, $reasonMessage): array {
            $session = $this->sessionRepository->findById($sessionId);
            if ($session === null) {
                throw new AppException('Session not found.', 404, 'SESSION_NOT_FOUND');
            }

            $previousStatus = $fromStatus ?? ($session['status'] ?? null);
            $updated = $this->sessionRepository->update($sessionId, [
                'status' => $toStatus,
                'last_activity_at' => $this->now(),
                'updated_at' => $this->now(),
            ]);

            $history = $this->statusHistoryRepository->insert([
                'session_id' => $sessionId,
                'status_scope' => 'session',
                'from_status' => $previousStatus,
                'to_status' => $toStatus,
                'changed_by_type' => 'system',
                'changed_by_reference' => null,
                'reason_code' => $reasonCode,
                'reason_message' => $reasonMessage,
                'metadata' => JsonHelper::encode($metadata, true),
                'created_at' => $this->now(),
            ]);

            $this->auditService->record('session_status_transition', 'Session status changed.', [
                'session_id' => $sessionId,
                'from_status' => $previousStatus,
                'to_status' => $toStatus,
            ], 'info', $sessionId);

            return [
                'session' => $updated ?? $session,
                'history' => $history,
            ];
        });
    }

    public function transitionSessionStep(string $sessionId, string $toStep, ?string $fromStep = null, array $metadata = [], ?string $reasonCode = null, ?string $reasonMessage = null): array
    {
        return $this->transactional(function () use ($sessionId, $toStep, $fromStep, $metadata, $reasonCode, $reasonMessage): array {
            $session = $this->sessionRepository->findById($sessionId);
            if ($session === null) {
                throw new AppException('Session not found.', 404, 'SESSION_NOT_FOUND');
            }

            $previousStep = $fromStep ?? ($session['current_step'] ?? null);
            $updated = $this->sessionRepository->update($sessionId, [
                'current_step' => $toStep,
                'last_activity_at' => $this->now(),
                'updated_at' => $this->now(),
            ]);

            $history = $this->statusHistoryRepository->insert([
                'session_id' => $sessionId,
                'status_scope' => 'session',
                'from_status' => $previousStep,
                'to_status' => $toStep,
                'changed_by_type' => 'system',
                'changed_by_reference' => null,
                'reason_code' => $reasonCode,
                'reason_message' => $reasonMessage,
                'metadata' => JsonHelper::encode($metadata, true),
                'created_at' => $this->now(),
            ]);

            return [
                'session' => $updated ?? $session,
                'history' => $history,
            ];
        });
    }

    public function transitionRequest(string $requestId, string $toStatus, ?string $fromStatus = null, array $metadata = [], ?string $reasonCode = null, ?string $reasonMessage = null): array
    {
        return $this->transactional(function () use ($requestId, $toStatus, $fromStatus, $metadata, $reasonCode, $reasonMessage): array {
            $request = $this->requestRepository->findById($requestId);
            if ($request === null) {
                throw new AppException('Request not found.', 404, 'REQUEST_NOT_FOUND');
            }

            $previousStatus = $fromStatus ?? ($request['status'] ?? null);
            $updated = $this->requestRepository->update($requestId, [
                'status' => $toStatus,
                'updated_at' => $this->now(),
            ]);

            $history = $this->statusHistoryRepository->insert([
                'request_id' => $requestId,
                'status_scope' => 'request',
                'from_status' => $previousStatus,
                'to_status' => $toStatus,
                'changed_by_type' => 'system',
                'changed_by_reference' => null,
                'reason_code' => $reasonCode,
                'reason_message' => $reasonMessage,
                'metadata' => JsonHelper::encode($metadata, true),
                'created_at' => $this->now(),
            ]);

            return [
                'request' => $updated ?? $request,
                'history' => $history,
            ];
        });
    }

    public function transitionDocument(string $documentId, string $toStatus, ?string $fromStatus = null, array $metadata = [], ?string $reasonCode = null, ?string $reasonMessage = null): array
    {
        return $this->transactional(function () use ($documentId, $toStatus, $fromStatus, $metadata, $reasonCode, $reasonMessage): array {
            $document = $this->documentRepository->findById($documentId);
            if ($document === null) {
                throw new AppException('Document not found.', 404, 'DOCUMENT_NOT_FOUND');
            }

            $previousStatus = $fromStatus ?? ($document['upload_status'] ?? null);
            $updated = $this->documentRepository->update($documentId, [
                'upload_status' => $toStatus,
                'updated_at' => $this->now(),
            ]);

            $history = $this->statusHistoryRepository->insert([
                'document_id' => $documentId,
                'status_scope' => 'document',
                'from_status' => $previousStatus,
                'to_status' => $toStatus,
                'changed_by_type' => 'system',
                'changed_by_reference' => null,
                'reason_code' => $reasonCode,
                'reason_message' => $reasonMessage,
                'metadata' => JsonHelper::encode($metadata, true),
                'created_at' => $this->now(),
            ]);

            return [
                'document' => $updated ?? $document,
                'history' => $history,
            ];
        });
    }

    public function updateCimsStatus(string $requestId, string $status, array $metadata = []): array
    {
        if ($this->cimsResultRepository === null) {
            throw new AppException('CIMS result repository is not configured.', 500, 'CIMS_REPOSITORY_MISSING');
        }

        return $this->transactional(function () use ($requestId, $status, $metadata): array {
            $request = $this->requestRepository->findById($requestId);
            if ($request === null) {
                throw new AppException('Request not found.', 404, 'REQUEST_NOT_FOUND');
            }

            $updated = $this->requestRepository->update($requestId, [
                'latest_cims_status' => $status,
                'updated_at' => $this->now(),
            ]);

            $history = $this->statusHistoryRepository->insert([
                'request_id' => $requestId,
                'status_scope' => 'cims',
                'from_status' => $request['latest_cims_status'] ?? null,
                'to_status' => $status,
                'changed_by_type' => 'integration',
                'changed_by_reference' => null,
                'reason_code' => null,
                'reason_message' => null,
                'metadata' => JsonHelper::encode($metadata, true),
                'created_at' => $this->now(),
            ]);

            return [
                'request' => $updated ?? $request,
                'history' => $history,
            ];
        });
    }
}
