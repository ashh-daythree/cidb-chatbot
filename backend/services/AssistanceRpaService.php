<?php

declare(strict_types=1);

namespace Cidb\Backend\Services;

use Cidb\Backend\Config\DatabaseConnection;
use Cidb\Backend\Config\EnvironmentLoader;
use Cidb\Backend\Repositories\ChatbotAssistanceRequestRepository;
use Cidb\Backend\Utils\Exceptions\AppException;
use Cidb\Backend\Utils\JsonHelper;
use Cidb\Backend\Utils\Logger;
use Throwable;

/**
 * Logs a FAQ assistance-form enquiry as a case through the RPA ticket-insert bot and
 * records the outcome on the chatbot_assistance_requests row.
 *
 * The RPA acknowledges synchronously ({"status":"inserted", "inserts":[{"schedule_id": ...}]}).
 * Once the case is actually created, the RPA team writes the final values
 * (case_reference_no / rpa_status / rpa_display_message) straight into the row, and the
 * chatbot polls GET /assistance/{id} until they appear.
 */
final class AssistanceRpaService extends AbstractService
{
    public function __construct(
        DatabaseConnection $connection,
        private readonly ChatbotAssistanceRequestRepository $assistanceRequestRepository,
        private readonly RpaBotService $rpaBotService,
        private readonly AuditService $auditService,
        private readonly Logger $logger
    ) {
        parent::__construct($connection);
    }

    /**
     * @param array<string, mixed> $enquiry A chatbot_assistance_requests row.
     * @return array<string, mixed> The updated row.
     */
    public function logEnquiry(array $enquiry, int $attemptNo = 1): array
    {
        $enquiryId = (string) ($enquiry['id'] ?? '');
        if ($enquiryId === '') {
            throw new AppException('Assistance request id is required.', 422, 'ASSISTANCE_ID_REQUIRED');
        }

        $this->assistanceRequestRepository->update($enquiryId, [
            'rpa_status' => 'pending',
            'rpa_attempt_no' => $attemptNo,
            'rpa_triggered_at' => $this->now(),
            'updated_at' => $this->now(),
        ]);

        try {
            $payload = $this->buildEnquiryPayload($enquiry, $attemptNo);
            $this->assertEnquiryPayloadReady($payload);
        } catch (Throwable $throwable) {
            $this->logger->error('Enquiry RPA payload build failed.', [
                'assistance_request_id' => $enquiryId,
                'error' => $throwable->getMessage(),
            ]);
            $failed = $this->assistanceRequestRepository->update($enquiryId, [
                'rpa_status' => 'failed',
                'rpa_response_message' => $throwable->getMessage(),
                'rpa_completed_at' => $this->now(),
                'updated_at' => $this->now(),
            ]) ?? $enquiry;
            $this->auditService->record('assistance_request_rpa_failed', 'Assistance request RPA payload was incomplete.', [
                'assistance_request_id' => $enquiryId,
                'session_id' => $enquiry['session_id'] ?? null,
                'attempt_no' => $attemptNo,
                'error' => $throwable->getMessage(),
            ], 'warning', (string) ($enquiry['session_id'] ?? '') ?: null);

            return $failed;
        }

        try {
            $botResult = $this->rpaBotService->triggerEnquiryLog($payload);
        } catch (Throwable $throwable) {
            $this->logger->error('Enquiry RPA trigger threw.', [
                'assistance_request_id' => $enquiryId,
                'error' => $throwable->getMessage(),
            ]);
            $botResult = [
                'success' => false,
                'status_code' => 0,
                'raw_response_text' => $throwable->getMessage(),
                'parsed_response' => null,
                'duration_ms' => 0,
                'error_message' => $throwable->getMessage(),
            ];
        }

        $normalized = $this->normalizeEnquiryResult($botResult);

        $update = [
            'rpa_status' => $normalized['rpa_status'],
            'rpa_schedule_id' => $normalized['schedule_id'],
            'rpa_response_code' => $normalized['response_code'],
            'rpa_response_message' => $normalized['response_message'],
            'rpa_response_payload' => JsonHelper::encode([
                'request' => $payload,
                'response' => $botResult['parsed_response'] ?? $botResult['raw_response_text'] ?? null,
            ], true),
            'updated_at' => $this->now(),
        ];

        if ($normalized['case_reference_no'] !== null) {
            $update['case_reference_no'] = $normalized['case_reference_no'];
        }

        if ($normalized['rpa_status'] === 'logged') {
            $update['rpa_completed_at'] = $this->now();
            if ($normalized['response_message'] !== null && $normalized['response_message'] !== '') {
                $update['rpa_display_message'] = $normalized['response_message'];
            }
        }

        if ($normalized['rpa_status'] === 'failed') {
            $update['rpa_completed_at'] = $this->now();
        }

        $updated = $this->assistanceRequestRepository->update($enquiryId, $update) ?? $enquiry;

        $event = $normalized['rpa_status'] === 'failed'
            ? 'assistance_request_rpa_failed'
            : 'assistance_request_rpa_logged';
        $this->auditService->record($event, 'Assistance request RPA case-logging attempt completed.', [
            'assistance_request_id' => $enquiryId,
            'session_id' => $enquiry['session_id'] ?? null,
            'rpa_status' => $normalized['rpa_status'],
            'attempt_no' => $attemptNo,
            'http_status' => $botResult['status_code'] ?? null,
            'schedule_id' => $normalized['schedule_id'],
            'case_reference_no' => $normalized['case_reference_no'],
        ], $normalized['rpa_status'] === 'failed' ? 'warning' : 'info', (string) ($enquiry['session_id'] ?? '') ?: null);

        return $updated;
    }

    /**
     * @param array<string, mixed> $enquiry
     * @return array<string, mixed>
     */
    private function buildEnquiryPayload(array $enquiry, int $attemptNo): array
    {
        $isCompany = strtolower((string) ($enquiry['applicant_category'] ?? '')) === 'company';

        $fields = [
            'sCustomerType' => $isCompany ? 'Company' : 'Individual',
            'sCustomerName' => $this->resolveBotPayloadValue([$enquiry['customer_name'] ?? null]),
            'sIdentificationNumber' => $this->resolveBotPayloadValue([$enquiry['id_number'] ?? null]),
            'sContactNumber' => $this->resolveBotPayloadValue([$enquiry['phone'] ?? null]),
            'sEmail' => $this->resolveBotPayloadValue([$enquiry['email'] ?? null]),
            'sLocationArea' => $this->resolveBotPayloadValue([$enquiry['state'] ?? null]),
            'sLanguage' => $this->resolveBotPayloadValue([$enquiry['language_code'] ?? null]) ?? 'en',
            'sChannel' => 'Chatbot',
            'sEnquiryTitle' => $this->resolveBotPayloadValue([$enquiry['enquiry_title'] ?? null]),
            'sEnquiryDescription' => $this->resolveBotPayloadValue([$enquiry['enquiry_description'] ?? null]),
            'sAttempt' => (string) $attemptNo,
        ];

        if ($isCompany) {
            $fields['sCompanyName'] = $this->resolveBotPayloadValue([$enquiry['company_name'] ?? null]);
            $fields['sSSMNumber'] = $this->resolveBotPayloadValue([$enquiry['company_registration_no'] ?? null]);
        }

        $attachmentId = $this->resolveBotPayloadValue([$enquiry['attachment_document_id'] ?? null]);
        if ($attachmentId !== null) {
            $fields['sAttachmentDocumentId'] = $attachmentId;
        }

        return [
            'company' => 'CIDB',
            'scenario_key' => trim((string) EnvironmentLoader::get('RPA_ENQUIRY_SCENARIO_KEY', 'cidb_masterbot')),
            'channel' => 'Chatbot',
            'fields' => $fields,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function assertEnquiryPayloadReady(array $payload): void
    {
        $fields = is_array($payload['fields'] ?? null) ? $payload['fields'] : [];
        $missing = [];

        foreach ([
            'sCustomerType',
            'sCustomerName',
            'sContactNumber',
            'sEmail',
            'sLocationArea',
            'sEnquiryTitle',
            'sEnquiryDescription',
        ] as $key) {
            if (trim((string) ($fields[$key] ?? '')) === '') {
                $missing[] = $key;
            }
        }

        if ($missing !== []) {
            throw new AppException('Enquiry RPA payload is incomplete.', 422, 'ENQUIRY_RPA_PAYLOAD_INVALID', [
                'missing_fields' => $missing,
            ]);
        }
    }

    /**
     * @param array{success?: bool, status_code?: int, raw_response_text?: string, parsed_response?: array<string, mixed>|null, error_message?: ?string} $botResult
     * @return array{rpa_status: string, schedule_id: ?string, case_reference_no: ?string, response_code: ?int, response_message: ?string}
     */
    private function normalizeEnquiryResult(array $botResult): array
    {
        $parsed = is_array($botResult['parsed_response'] ?? null) ? $botResult['parsed_response'] : null;
        $rawText = trim((string) ($botResult['raw_response_text'] ?? ''));
        $statusCode = (int) ($botResult['status_code'] ?? 0);
        $success = (bool) ($botResult['success'] ?? false);

        $scheduleId = $this->extractScheduleId($parsed);
        $caseReference = $this->extractCaseReference($parsed);
        $responseMessage = $this->extractMessage($parsed, $rawText, (string) ($botResult['error_message'] ?? ''));

        $status = 'failed';
        if ($caseReference !== null) {
            $status = 'logged';
        } elseif ($success && $statusCode >= 200 && $statusCode < 300 && $this->isAcknowledgement($parsed, $scheduleId)) {
            $status = 'pending';
        }

        return [
            'rpa_status' => $status,
            'schedule_id' => $scheduleId,
            'case_reference_no' => $caseReference,
            'response_code' => $statusCode > 0 ? $statusCode : null,
            'response_message' => $responseMessage !== '' ? $responseMessage : null,
        ];
    }

    /**
     * @param array<string, mixed>|null $parsed
     */
    private function isAcknowledgement(?array $parsed, ?string $scheduleId): bool
    {
        if ($parsed === null) {
            return false;
        }

        $status = strtolower(trim((string) ($parsed['status'] ?? '')));

        return $status === 'inserted' && $scheduleId !== null;
    }

    /**
     * @param array<string, mixed>|null $parsed
     */
    private function extractScheduleId(?array $parsed): ?string
    {
        if ($parsed === null) {
            return null;
        }

        $candidates = [
            $parsed['schedule_id'] ?? null,
            $parsed['inserts'][0]['schedule_id'] ?? null,
            $parsed['data']['schedule_id'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed>|null $parsed
     */
    private function extractCaseReference(?array $parsed): ?string
    {
        if ($parsed === null) {
            return null;
        }

        foreach (['external_reference_no', 'reference_no', 'case_reference_no', 'case_id', 'ticket_no', 'ticket_number'] as $key) {
            $candidates = [
                $parsed[$key] ?? null,
                $parsed['data'][$key] ?? null,
                $parsed['inserts'][0][$key] ?? null,
            ];

            foreach ($candidates as $candidate) {
                if (is_string($candidate) && trim($candidate) !== '') {
                    return trim($candidate);
                }

                if (is_int($candidate)) {
                    return (string) $candidate;
                }
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed>|null $parsed
     */
    private function extractMessage(?array $parsed, string $rawText, string $errorMessage): string
    {
        $candidates = [];

        if ($parsed !== null) {
            $candidates[] = $parsed['response_message'] ?? null;
            $candidates[] = $parsed['message'] ?? null;
            $candidates[] = $parsed['display_message'] ?? null;
            $candidates[] = $parsed['data']['message'] ?? null;
        }

        $candidates[] = $errorMessage;

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }
        }

        return '';
    }
}
