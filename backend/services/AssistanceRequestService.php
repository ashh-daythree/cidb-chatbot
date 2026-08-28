<?php

declare(strict_types=1);

namespace Cidb\Backend\Services;

use Cidb\Backend\Config\DatabaseConnection;
use Cidb\Backend\Repositories\ChatbotAssistanceRequestRepository;
use Cidb\Backend\Utils\Exceptions\AppException;

final class AssistanceRequestService extends AbstractService
{
    public function __construct(
        DatabaseConnection $connection,
        private readonly ChatbotAssistanceRequestRepository $assistanceRequestRepository,
        private readonly AuditService $auditService
    ) {
        parent::__construct($connection);
    }

    public function submit(array $payload): array
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

            $assistanceRequest = $this->assistanceRequestRepository->insert($data);

            $this->auditService->record('assistance_request_submitted', 'Assistance request submitted.', [
                'session_id' => $sessionId,
                'assistance_request_id' => $assistanceRequest['id'] ?? null,
                'applicant_category' => $applicantCategory,
            ], 'info', $sessionId);

            return $assistanceRequest;
        });
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
