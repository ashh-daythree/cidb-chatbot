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
            return $this->createRequestInternal($sessionId, $requestTypeCode, true);
        });
    }

    public function createCompanyFromSession(string $sessionId): array
    {
        return $this->transactional(function () use ($sessionId): array {
            return $this->createRequestInternal($sessionId, 'COMPANY_EMAIL_ID_CANCELLATION', false);
        });
    }

    /**
     * Creates (or returns the existing) `service_requests` row for a FAQ assistance
     * enquiry. Unlike the cancellation creators there is no applicant record and no
     * company/individual service-type requirement; the row is submitted immediately.
     *
     * @return array<string, mixed>
     */
    public function createFaqAssistanceRequest(string $sessionId): array
    {
        return $this->transactional(function () use ($sessionId): array {
            $requestType = 'FAQ_ASSISTANCE_ENQUIRY';

            $existing = $this->requestRepository->findBySessionId($sessionId);
            if ($existing !== null) {
                if ((string) ($existing['request_type_code'] ?? '') === $requestType) {
                    return $existing;
                }

                throw new AppException(
                    'This chat session already has a service request.',
                    409,
                    'SESSION_HAS_REQUEST'
                );
            }

            $session = $this->sessionRepository->findById($sessionId);
            if ($session === null) {
                throw new AppException('Session not found.', 404, 'SESSION_NOT_FOUND');
            }

            $draft = $this->decodeDraft($session['draft_payload'] ?? []);

            $workflowId = (string) ($session['workflow_id'] ?? '');
            if ($workflowId === '' || $this->workflowRepository->findById($workflowId) === null) {
                throw new AppException('Workflow not found.', 422, 'WORKFLOW_INVALID');
            }

            if ($this->requestTypeRepository->findActiveByCode($requestType) === null) {
                throw new AppException('Request type is invalid.', 422, 'REQUEST_TYPE_INVALID');
            }

            $payload = [
                'request_number' => $this->generateRequestNumber(),
                'workflow_id' => $workflowId,
                'session_id' => $sessionId,
                'applicant_id' => null,
                'request_type_code' => $requestType,
                'status' => 'submitted',
                'submission_language_code' => (string) ($session['language_code'] ?? $draft['language_code'] ?? 'en'),
                'submitted_at' => $this->now(),
                'latest_cims_status' => 'pending',
                'final_outcome' => null,
                'final_outcome_at' => null,
                'closed_at' => null,
                'created_at' => $this->now(),
                'updated_at' => $this->now(),
            ];

            $request = $this->requestRepository->insert($payload);

            $this->auditService->record('request_created', 'FAQ assistance service request created.', [
                'session_id' => $sessionId,
                'request_id' => $request['id'] ?? null,
                'request_number' => $request['request_number'] ?? null,
                'request_type_code' => $requestType,
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

    public function claimCancellationRetry(string $requestId): ?array
    {
        return $this->transactional(function () use ($requestId): ?array {
            $pdo = $this->connection->pdo();
            $statement = $pdo->prepare(
                'UPDATE service_requests
                    SET status = :status,
                        updated_at = :updated_at
                  WHERE id = :id
                    AND status = :expected_status
              RETURNING *'
            );

            $statement->execute([
                'status' => 'under_review',
                'updated_at' => $this->now(),
                'id' => $requestId,
                'expected_status' => 'submitted',
            ]);

            $request = $statement->fetch(\PDO::FETCH_ASSOC);
            if ($request === false) {
                return null;
            }

            return $request;
        });
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

    public function markStatus(string $requestId, string $status): array
    {
        return $this->requestRepository->update($requestId, [
            'status' => $status,
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

    /**
     * @return array<string, mixed>
     */
    private function createRequestInternal(string $sessionId, ?string $requestTypeCode, bool $requireApplicant): array
    {
        $existing = $this->requestRepository->findBySessionId($sessionId);
        if ($existing !== null) {
            return $existing;
        }

        $session = $this->sessionRepository->findById($sessionId);
        if ($session === null) {
            throw new AppException('Session not found.', 404, 'SESSION_NOT_FOUND');
        }

        $draft = $this->decodeDraft($session['draft_payload'] ?? []);
        $serviceType = strtolower(trim((string) ($draft['service_type'] ?? 'individual')));

        if (!$requireApplicant && $serviceType !== 'company') {
            throw new AppException('Company request requires the company service type.', 422, 'REQUEST_TYPE_INVALID');
        }

        $requestType = $requestTypeCode ?? 'EMAIL_ID_CANCELLATION';
        if ($requestTypeCode === null && $serviceType === 'company') {
            $requestType = 'COMPANY_EMAIL_ID_CANCELLATION';
        }

        if ($requireApplicant) {
            $applicant = $this->applicantRepository->findBySessionId($sessionId);
            if ($applicant === null) {
                throw new AppException('Applicant record is missing.', 422, 'APPLICANT_MISSING');
            }

            $submissionLanguageCode = (string) ($applicant['language_code'] ?? $session['language_code'] ?? $draft['language_code'] ?? 'en');
            $applicantId = (string) $applicant['id'];
        } else {
            $applicant = null;
            $submissionLanguageCode = (string) ($session['language_code'] ?? $draft['language_code'] ?? 'en');
            $applicantId = null;
        }

        $workflowId = (string) ($session['workflow_id'] ?? '');
        if ($workflowId === '' || $this->workflowRepository->findById($workflowId) === null) {
            throw new AppException('Workflow not found.', 422, 'WORKFLOW_INVALID');
        }

        if ($this->requestTypeRepository->findActiveByCode($requestType) === null) {
            throw new AppException('Request type is invalid.', 422, 'REQUEST_TYPE_INVALID');
        }

        $payload = [
            'request_number' => $this->generateRequestNumber(),
            'workflow_id' => $workflowId,
            'session_id' => $sessionId,
            'applicant_id' => $applicantId,
            'request_type_code' => $requestType,
            'status' => 'draft',
            'submission_language_code' => $submissionLanguageCode,
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
            'request_type_code' => $requestType,
            'service_type' => $serviceType,
            'applicant_id' => $applicantId,
        ], 'info', $sessionId, $request['id'] ?? null);

        return $request;
    }

    /**
     * @param array<string, mixed> $draft
     * @return array<string, mixed>
     */
    private function decodeDraft(mixed $draft): array
    {
        if (is_array($draft)) {
            return $draft;
        }

        if (is_string($draft) && $draft !== '') {
            $decoded = json_decode($draft, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }
}
