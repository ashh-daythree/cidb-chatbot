<?php

declare(strict_types=1);

namespace Cidb\Backend\Services;

use Cidb\Backend\Config\DatabaseConnection;
use Cidb\Backend\Repositories\ChatbotAssistanceRequestRepository;
use Cidb\Backend\Repositories\ChatbotSessionRepository;
use Cidb\Backend\Utils\Exceptions\AppException;
use Cidb\Backend\Utils\Logger;
use Throwable;

final class AssistanceRequestService extends AbstractService
{
    /** Renewal types the RPA "cidb_masterbot" scenario accepts as sCustomerType. */
    private const TOPIC_CODES = ['PPK', 'SPKK', 'STB'];

    public function __construct(
        DatabaseConnection $connection,
        private readonly ChatbotAssistanceRequestRepository $assistanceRequestRepository,
        private readonly ChatbotSessionRepository $sessionRepository,
        private readonly RequestService $requestService,
        private readonly VerificationService $verificationService,
        private readonly AuditService $auditService,
        private readonly Logger $logger
    ) {
        parent::__construct($connection);
    }

    public function submit(array $payload): array
    {
        $sessionId = $this->requireField($payload, 'session_id', 'SESSION_ID_REQUIRED');
        $applicantCategory = strtolower($this->requireField($payload, 'applicant_category', 'APPLICANT_CATEGORY_REQUIRED'));

        if (!in_array($applicantCategory, ['individual', 'company'], true)) {
            throw new AppException('Applicant category must be individual or company.', 422, 'APPLICANT_CATEGORY_INVALID', [
                'applicant_category' => 'Applicant category must be individual or company.',
            ]);
        }

        $topicCode = strtoupper($this->requireField($payload, 'topic_code', 'TOPIC_CODE_REQUIRED'));
        if (!in_array($topicCode, self::TOPIC_CODES, true)) {
            throw new AppException('Topic must be PPK, SPKK or STB.', 422, 'TOPIC_CODE_INVALID', [
                'topic_code' => 'Topic must be PPK, SPKK or STB.',
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
            'cases_category' => $this->requireField($payload, 'cases_category', 'CASES_CATEGORY_REQUIRED'),
            'sub_category_1' => $this->requireField($payload, 'sub_category_1', 'SUB_CATEGORY_1_REQUIRED'),
            'sub_category_2' => $this->requireField($payload, 'sub_category_2', 'SUB_CATEGORY_2_REQUIRED'),
            'attachment_document_id' => trim((string) ($payload['attachment_document_id'] ?? '')) ?: null,
            'attachment_document_id_2' => trim((string) ($payload['attachment_document_id_2'] ?? '')) ?: null,
            'attachment_document_id_3' => trim((string) ($payload['attachment_document_id_3'] ?? '')) ?: null,
            'status' => 'new',
            'created_at' => $this->now(),
            'updated_at' => $this->now(),
        ];

        if ($applicantCategory === 'company') {
            $data['company_name'] = $this->requireField($payload, 'company_name', 'COMPANY_NAME_REQUIRED');
            $data['company_registration_no'] = $this->requireField($payload, 'company_registration_no', 'COMPANY_REGISTRATION_NO_REQUIRED');
        }

        [$assistanceRequest, $serviceRequest] = $this->transactional(function () use ($data, $sessionId, $topicCode): array {
            $assistanceRequest = $this->assistanceRequestRepository->insert($data);
            $serviceRequest = $this->requestService->createFaqAssistanceRequest($sessionId);

            $assistanceRequest = $this->assistanceRequestRepository->update((string) $assistanceRequest['id'], [
                'service_request_id' => $serviceRequest['id'] ?? null,
                'updated_at' => $this->now(),
            ]) ?? $assistanceRequest;

            $this->auditService->record('assistance_request_submitted', 'Assistance request submitted.', [
                'session_id' => $sessionId,
                'assistance_request_id' => $assistanceRequest['id'] ?? null,
                'service_request_id' => $serviceRequest['id'] ?? null,
                'applicant_category' => $data['applicant_category'],
                'topic_code' => $topicCode,
            ], 'info', $sessionId);

            return [$assistanceRequest, $serviceRequest];
        });

        $session = $this->sessionRepository->findById($sessionId);
        $languageCode = strtolower(trim((string) ($payload['language_code'] ?? $session['language_code'] ?? 'en')));

        // Fire the RPA bot. A failure here must not lose the enquiry the customer already
        // submitted — fall back to the static "we will email you" message.
        $verification = null;
        try {
            $verification = $this->verificationService->verifyFaqAssistance((string) $serviceRequest['id'], [
                'topic_code' => $topicCode,
                'customer_name' => $data['customer_name'],
                'id_number' => $data['id_number'],
                'phone' => $data['phone'],
                'email' => $data['email'],
                'state' => $data['state'],
                'language_code' => $languageCode,
            ], 1);
        } catch (Throwable $exception) {
            $this->logger->error('FAQ assistance RPA trigger failed.', [
                'assistance_request_id' => $assistanceRequest['id'] ?? null,
                'service_request_id' => $serviceRequest['id'] ?? null,
                'message' => $exception->getMessage(),
            ]);
            $this->auditService->record('faq_assistance_rpa_failed', 'FAQ assistance RPA trigger failed.', [
                'assistance_request_id' => $assistanceRequest['id'] ?? null,
                'service_request_id' => $serviceRequest['id'] ?? null,
                'error' => $exception->getMessage(),
            ], 'warning', $sessionId);
        }

        $isPending = is_array($verification) && (bool) ($verification['is_pending'] ?? false);

        return [
            'assistance_request' => $assistanceRequest,
            'request_number' => $serviceRequest['request_number'] ?? null,
            'verification' => $verification,
            'next_action' => $isPending ? 'poll' : 'done',
        ];
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
