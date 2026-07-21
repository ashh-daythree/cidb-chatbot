<?php

declare(strict_types=1);

namespace Cidb\Backend\Services;

use Cidb\Backend\Config\DatabaseConnection;
use Cidb\Backend\Repositories\ChatbotApplicantRepository;
use Cidb\Backend\Repositories\ChatbotSessionRepository;
use Cidb\Backend\Repositories\ChatbotWorkflowRepository;
use Cidb\Backend\Repositories\ReferenceRequestTypeRepository;
use Cidb\Backend\Repositories\ServiceRequestRepository;
use Cidb\Backend\Utils\Exceptions\AppException;

final class RequestService extends AbstractService
{
    public function __construct(
        DatabaseConnection $connection,
        private readonly ServiceRequestRepository $requestRepository,
        private readonly ChatbotSessionRepository $sessionRepository,
        private readonly ChatbotApplicantRepository $applicantRepository,
        private readonly ChatbotWorkflowRepository $workflowRepository,
        private readonly ReferenceRequestTypeRepository $requestTypeRepository,
        private readonly AuditService $auditService
    ) {
        parent::__construct($connection);
    }

    public function generateRequestNumber(): string
    {
        return 'REQ-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(8)), 0, 12));
    }

    public function createFromSession(string $sessionId, ?string $requestTypeCode = null): array
    {
        return $this->transactional(function () use ($sessionId, $requestTypeCode): array {
            $existing = $this->requestRepository->findBySessionId($sessionId);
            if ($existing !== null) {
                return $existing;
            }

            $session = $this->sessionRepository->findById($sessionId);
            if ($session === null) {
                throw new AppException('Session not found.', 404, 'SESSION_NOT_FOUND');
            }

            $applicant = $this->applicantRepository->findBySessionId($sessionId);
            if ($applicant === null) {
                throw new AppException('Applicant record is missing.', 422, 'APPLICANT_MISSING');
            }

            $workflowId = (string) ($session['workflow_id'] ?? '');
            if ($workflowId === '' || $this->workflowRepository->findById($workflowId) === null) {
                throw new AppException('Workflow not found.', 422, 'WORKFLOW_INVALID');
            }

            $requestType = $requestTypeCode ?? 'EMAIL_ID_CANCELLATION';
            if ($this->requestTypeRepository->findActiveByCode($requestType) === null) {
                throw new AppException('Request type is invalid.', 422, 'REQUEST_TYPE_INVALID');
            }

            $payload = [
                'request_number' => $this->generateRequestNumber(),
                'workflow_id' => $workflowId,
                'session_id' => $sessionId,
                'applicant_id' => (string) $applicant['id'],
                'request_type_code' => $requestType,
                'status' => 'draft',
                'submission_language_code' => (string) ($applicant['language_code'] ?? $session['language_code'] ?? 'en'),
                'submitted_at' => null,
                'latest_cims_status' => 'pending',
                'final_outcome' => null,
                'final_outcome_at' => null,
                'closed_at' => null,
                'created_at' => $this->now(),
                'updated_at' => $this->now(),
            ];

            $request = $this->requestRepository->insert($payload);

            $this->auditService->record('request_created', 'Service request created.', [
                'session_id' => $sessionId,
                'request_id' => $request['id'] ?? null,
                'request_number' => $request['request_number'] ?? null,
            ], 'info', $sessionId, $request['id'] ?? null);

            return $request;
        });
    }

    public function markSubmitted(string $requestId): array
    {
        return $this->transactional(function () use ($requestId): array {
            $request = $this->requestRepository->findById($requestId);
            if ($request === null) {
                throw new AppException('Request not found.', 404, 'REQUEST_NOT_FOUND');
            }

            return $this->requestRepository->update($requestId, [
                'status' => 'submitted',
                'submitted_at' => $request['submitted_at'] ?? $this->now(),
                'updated_at' => $this->now(),
            ]) ?? $request;
        });
    }

    public function markUnderReview(string $requestId): array
    {
        return $this->requestRepository->update($requestId, [
            'status' => 'under_review',
            'updated_at' => $this->now(),
        ]) ?? [];
    }

    public function markFinalOutcome(string $requestId, string $status, ?string $outcome): array
    {
        return $this->requestRepository->update($requestId, [
            'status' => $status,
            'final_outcome' => $outcome,
            'final_outcome_at' => $this->now(),
            'closed_at' => $this->now(),
            'updated_at' => $this->now(),
        ]) ?? [];
    }

    public function findBySessionId(string $sessionId): ?array
    {
        return $this->requestRepository->findBySessionId($sessionId);
    }

    public function findByRequestNumber(string $requestNumber): ?array
    {
        return $this->requestRepository->findByRequestNumber($requestNumber);
    }
}
