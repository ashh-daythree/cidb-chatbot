<?php

declare(strict_types=1);

namespace Cidb\Backend\Services;

use Cidb\Backend\Config\DatabaseConnection;
use Cidb\Backend\Repositories\ChatbotAssistanceRequestRepository;
use Cidb\Backend\Utils\Exceptions\AppException;
use Cidb\Backend\Utils\Logger;
use Throwable;

final class AssistanceRequestService extends AbstractService
{
    public function __construct(
        DatabaseConnection $connection,
        private readonly ChatbotAssistanceRequestRepository $assistanceRequestRepository,
        private readonly AuditService $auditService,
        private readonly AssistanceRpaService $assistanceRpaService,
        private readonly Logger $logger
    ) {
        parent::__construct($connection);
    }

    public function submit(array $payload): array
    {
        $assistanceRequest = $this->persist($payload);

        // Trigger the RPA case-logging outside the DB transaction so an external HTTP call
        // never holds a transaction open, and an RPA failure never loses the saved enquiry.
        $languageCode = strtolower(trim((string) ($payload['language_code'] ?? ''))) ?: 'en';
        $enquiry = array_merge($assistanceRequest, ['language_code' => $languageCode]);

        try {
            $assistanceRequest = $this->assistanceRpaService->logEnquiry($enquiry, 1);
        } catch (Throwable $throwable) {
            $this->logger->error('Assistance request RPA logging failed after submit.', [
                'assistance_request_id' => $assistanceRequest['id'] ?? null,
                'error' => $throwable->getMessage(),
            ]);
            $assistanceRequest['rpa_status'] = $assistanceRequest['rpa_status'] ?? 'failed';
        }

        $assistanceRequest['next_action'] = ($assistanceRequest['rpa_status'] ?? '') === 'pending' ? 'poll' : 'done';

        return $assistanceRequest;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function persist(array $payload): array
    {
        return $this->transactional(function () use ($payload): array {
            $sessionId = $this->requireField($payload, 'session_id', 'SESSION_ID_REQUIRED');
            $applicantCategory = strtolower($this->requireField($payload, 'applicant_category', 'APPLICANT_CATEGORY_REQUIRED'));

            if (!in_array($applicantCategory, ['individual', 'company'], true)) {
                throw new AppException('Applicant category must be individual or company.', 422, 'APPLICANT_CATEGORY_INVALID', [
                    'applicant_category' => 'Applicant category must be individual or company.',
                ]);
            }

            $data = [
                'session_id' => $sessionId,
                'state' => $this->requireField($payload, 'state', 'STATE_REQUIRED'),
                'customer_name' => $this->requireField($payload, 'customer_name', 'CUSTOMER_NAME_REQUIRED'),
                'applicant_category' => $applicantCategory,
                'phone' => $this->requireField($payload, 'phone', 'PHONE_REQUIRED'),
                'email' => $this->requireField($payload, 'email', 'EMAIL_REQUIRED'),
                'enquiry_title' => $this->requireField($payload, 'enquiry_title', 'ENQUIRY_TITLE_REQUIRED'),
                'enquiry_description' => $this->requireField($payload, 'enquiry_description', 'ENQUIRY_DESCRIPTION_REQUIRED'),
                'id_number' => $this->requireField($payload, 'id_number', 'ID_NUMBER_REQUIRED'),
                'company_name' => null,
                'company_registration_no' => null,
                'attachment_document_id' => trim((string) ($payload['attachment_document_id'] ?? '')) ?: null,
                'language_code' => strtolower(trim((string) ($payload['language_code'] ?? ''))) ?: 'en',
                'status' => 'new',
                'created_at' => $this->now(),
                'updated_at' => $this->now(),
            ];

            if ($applicantCategory === 'company') {
                $data['company_name'] = $this->requireField($payload, 'company_name', 'COMPANY_NAME_REQUIRED');
                $data['company_registration_no'] = $this->requireField($payload, 'company_registration_no', 'COMPANY_REGISTRATION_NO_REQUIRED');
            }

            $assistanceRequest = $this->assistanceRequestRepository->insert($data);

            $this->auditService->record('assistance_request_submitted', 'Assistance request submitted.', [
                'session_id' => $sessionId,
                'assistance_request_id' => $assistanceRequest['id'] ?? null,
                'applicant_category' => $applicantCategory,
            ], 'info', $sessionId);

            return $assistanceRequest;
        });
    }

    /**
     * Returns the enquiry with a resolved next_action for the frontend poll loop.
     *
     * @return array<string, mixed>
     */
    public function status(string $assistanceRequestId): array
    {
        $enquiry = $this->assistanceRequestRepository->findById($assistanceRequestId);
        if ($enquiry === null) {
            throw new AppException('Assistance request not found.', 404, 'ASSISTANCE_REQUEST_NOT_FOUND');
        }

        $enquiry['next_action'] = ($enquiry['rpa_status'] ?? '') === 'pending' ? 'poll' : 'done';

        return $enquiry;
    }

    /**
     * Re-triggers the RPA case-logging for an enquiry whose previous attempt failed.
     *
     * @return array<string, mixed>
     */
    public function retry(string $assistanceRequestId): array
    {
        $enquiry = $this->assistanceRequestRepository->findById($assistanceRequestId);
        if ($enquiry === null) {
            throw new AppException('Assistance request not found.', 404, 'ASSISTANCE_REQUEST_NOT_FOUND');
        }

        $rpaStatus = (string) ($enquiry['rpa_status'] ?? '');
        if ($rpaStatus === 'logged') {
            $enquiry['next_action'] = 'done';

            return $enquiry;
        }

        if ($rpaStatus === 'pending') {
            throw new AppException('The RPA case-logging is still in progress.', 409, 'ASSISTANCE_RPA_IN_PROGRESS');
        }

        $languageCode = strtolower(trim((string) ($enquiry['language_code'] ?? ''))) ?: 'en';
        $nextAttempt = ((int) ($enquiry['rpa_attempt_no'] ?? 0)) + 1;
        $updated = $this->assistanceRpaService->logEnquiry(
            array_merge($enquiry, ['language_code' => $languageCode]),
            $nextAttempt
        );

        $updated['next_action'] = ($updated['rpa_status'] ?? '') === 'pending' ? 'poll' : 'done';

        return $updated;
    }

    private function requireField(array $payload, string $field, string $errorCode): string
    {
        $value = trim((string) ($payload[$field] ?? ''));
        if ($value === '') {
            throw new AppException(sprintf('Field "%s" is required.', $field), 422, $errorCode, [
                $field => 'This field is required.',
            ]);
        }

        return $value;
    }
}
