<?php

declare(strict_types=1);

namespace Cidb\Backend\Services;

use Cidb\Backend\Config\DatabaseConnection;
use Cidb\Backend\Repositories\CimsVerificationResultRepository;
use Cidb\Backend\Repositories\ServiceRequestRepository;
use Cidb\Backend\Utils\Exceptions\AppException;
use Cidb\Backend\Utils\JsonHelper;

final class VerificationService extends AbstractService
{
    public function __construct(
        DatabaseConnection $connection,
        private readonly CimsVerificationResultRepository $cimsResultRepository,
        private readonly ServiceRequestRepository $requestRepository,
        private readonly AuditService $auditService,
        private readonly RpaBotService $rpaBotService
    ) {
        parent::__construct($connection);
    }

    public function verifyApplicant(array $context): array
    {
        return [
            'success' => true,
            'status' => 'verified',
            'message' => 'Development mock applicant verification passed.',
            'score' => 100,
            'context' => $context,
        ];
    }

    public function verifyCims(string $requestId, ?string $mockOutcome = null, array $context = []): array
    {
        return $this->transactional(function () use ($requestId, $mockOutcome, $context): array {
            $request = $this->requestRepository->findById($requestId);
            if ($request === null) {
                throw new AppException('Request not found.', 404, 'REQUEST_NOT_FOUND');
            }

            $botPayload = $this->buildBotPayload($requestId, $request, $context);
            $botResult = $this->rpaBotService->triggerTicketInsert($botPayload);
            $normalized = $this->normalizeBotResult($botResult);
            $payload = [
                'request_id' => $requestId,
                'attempt_no' => $this->nextAttemptNumber($requestId),
                'result_status' => $normalized['result_status'],
                'response_code' => $normalized['response_code'],
                'response_message' => $normalized['response_message'],
                'external_reference_no' => $normalized['external_reference_no'],
                'latency_ms' => $botResult['duration_ms'] ?? null,
                'rpa_response_text' => (string) ($botResult['raw_response_text'] ?? ''),
                'response_payload' => JsonHelper::encode($context, true),
                'verified_at' => $this->now(),
                'created_at' => $this->now(),
            ];

            $result = $this->cimsResultRepository->insert($payload);

            $this->auditService->record('cims_verification_completed', 'RPA bot verification completed.', [
                'request_id' => $requestId,
                'status' => $normalized['result_status'],
                'response_code' => $normalized['response_code'],
                'http_status' => $botResult['status_code'] ?? null,
            ], 'info', null, $requestId, 'integration');

            return array_merge($result, [
                'response_message' => $normalized['response_message'],
                'display_message' => $normalized['response_message'],
                'quick_replies' => $normalized['quick_replies'],
                'bot_response_text' => (string) ($botResult['raw_response_text'] ?? ''),
                'bot_response_body' => $botResult['parsed_response'] ?? null,
                'http_status' => $botResult['status_code'] ?? null,
                'request_payload' => $botPayload,
            ]);
        });
    }

    public function latestByRequestId(string $requestId): ?array
    {
        return $this->cimsResultRepository->findLatestByRequestId($requestId);
    }

    /**
     * @param array<string, mixed> $request
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function buildBotPayload(string $requestId, array $request, array $context): array
    {
        $session = is_array($context['session'] ?? null) ? $context['session'] : [];
        $documents = is_array($context['documents'] ?? null) ? $context['documents'] : [];
        $applicant = is_array($context['applicant'] ?? null) ? $context['applicant'] : [];
        $identificationNumber = $this->resolveIdentificationNumber($context, $applicant);

        return [
            'company' => 'CIDB',
            'scenario_key' => 'c_log',
            'channel' => 'Chatbot',
            'request_id' => $requestId,
            'request_number' => $request['request_number'] ?? null,
            'session_id' => $request['session_id'] ?? null,
            'workflow_id' => $request['workflow_id'] ?? null,
            'language_code' => $request['submission_language_code'] ?? ($session['language_code'] ?? null),
            'state_code' => $applicant['state_code'] ?? ($session['draft_payload']['state_code'] ?? null),
            'state_name' => $session['draft_payload']['state_name'] ?? ($context['state']['state'] ?? null),
            'full_name' => $context['full_name']['full_name'] ?? ($applicant['full_name'] ?? null),
            'identity_type' => $applicant['identity_type'] ?? ($context['identity_number']['identity_type'] ?? null),
            'identity_number' => $identificationNumber,
            'fields' => [
                'sIdentificationNumber' => $identificationNumber,
            ],
            'documents' => array_values(array_filter(array_map(static function (mixed $document): array {
                if (!is_array($document)) {
                    return [];
                }

                return [
                    'document_id' => $document['id'] ?? null,
                    'document_type_code' => $document['document_type_code'] ?? null,
                    'storage_path' => $document['storage_path'] ?? null,
                    'storage_file_name' => $document['storage_file_name'] ?? null,
                    'mime_type' => $document['mime_type'] ?? null,
                    'file_extension' => $document['file_extension'] ?? null,
                    'file_size_bytes' => $document['file_size_bytes'] ?? null,
                    'sha256_checksum' => $document['sha256_checksum'] ?? null,
                ];
            }, $documents), static fn (array $document): bool => $document !== [])),
            'submitted_at' => $request['submitted_at'] ?? null,
        ];
    }

    /**
     * @param array<string, mixed> $context
     * @param array<string, mixed> $applicant
     */
    private function resolveIdentificationNumber(array $context, array $applicant): ?string
    {
        $candidates = [];

        if (is_array($context['identity_number'] ?? null)) {
            $identity = $context['identity_number'];
            $candidates[] = $identity['identity_number_compact'] ?? null;
            $candidates[] = $identity['identity_number'] ?? null;
        }

        $candidates[] = $applicant['identity_number'] ?? null;

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }
        }

        return null;
    }

    /**
     * @param array{success?: bool, status_code?: int, raw_response_text?: string, parsed_response?: array<string, mixed>|null, error_message?: ?string} $botResult
     * @return array<string, mixed>
     */
    private function normalizeBotResult(array $botResult): array
    {
        $rawText = trim((string) ($botResult['raw_response_text'] ?? ''));
        $parsed = is_array($botResult['parsed_response'] ?? null) ? $botResult['parsed_response'] : null;

        $responseMessage = $this->extractResponseMessage($parsed, $rawText, (string) ($botResult['error_message'] ?? ''));
        $resultStatus = $this->extractResultStatus($parsed, $responseMessage, (int) ($botResult['status_code'] ?? 0), (bool) ($botResult['success'] ?? false));
        $quickReplies = $this->extractQuickReplies($parsed);

        return [
            'result_status' => $resultStatus,
            'response_code' => $this->extractResponseCode($parsed, (int) ($botResult['status_code'] ?? 0), $resultStatus),
            'response_message' => $responseMessage,
            'external_reference_no' => $this->extractExternalReference($parsed),
            'quick_replies' => $quickReplies,
        ];
    }

    /**
     * @param array<string, mixed>|null $parsed
     */
    private function extractResponseMessage(?array $parsed, string $rawText, string $errorMessage): string
    {
        $candidates = [];

        if ($parsed !== null) {
            $candidates[] = $parsed['response_message'] ?? null;
            $candidates[] = $parsed['message'] ?? null;
            $candidates[] = $parsed['reply_message'] ?? null;
            $candidates[] = $parsed['display_message'] ?? null;
            $candidates[] = $parsed['data']['response_message'] ?? null;
            $candidates[] = $parsed['data']['message'] ?? null;
        }

        $candidates[] = $rawText;
        $candidates[] = $errorMessage;

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }
        }

        return 'We could not complete the verification right now. Please try again later or contact our support team.';
    }

    /**
     * @param array<string, mixed>|null $parsed
     */
    private function extractResultStatus(?array $parsed, string $message, int $statusCode, bool $success): string
    {
        $allowed = ['deleted', 'linked', 'norecord', 'error'];
        $candidates = [];

        if ($parsed !== null) {
            $candidates[] = strtolower(trim((string) ($parsed['result_status'] ?? '')));
            $candidates[] = strtolower(trim((string) ($parsed['status'] ?? '')));
            $candidates[] = strtolower(trim((string) ($parsed['verification_status'] ?? '')));
            $candidates[] = strtolower(trim((string) ($parsed['outcome'] ?? '')));
            $candidates[] = strtolower(trim((string) ($parsed['data']['result_status'] ?? '')));
            $candidates[] = strtolower(trim((string) ($parsed['data']['status'] ?? '')));
        }

        foreach ($candidates as $candidate) {
            if (in_array($candidate, $allowed, true)) {
                return $candidate;
            }
        }

        $normalizedMessage = strtolower($message);
        if ($statusCode >= 200 && $statusCode < 300 && $success === true) {
            if (str_contains($normalizedMessage, 'linked to a cims module')) {
                return 'linked';
            }

            if (str_contains($normalizedMessage, 'unable to locate') || str_contains($normalizedMessage, 'no record') || str_contains($normalizedMessage, 'not found')) {
                return 'norecord';
            }

            if (str_contains($normalizedMessage, 'could not complete') || str_contains($normalizedMessage, 'failed') || str_contains($normalizedMessage, 'error')) {
                return 'error';
            }

            return 'deleted';
        }

        if (str_contains($normalizedMessage, 'linked to a cims module')) {
            return 'linked';
        }

        if (str_contains($normalizedMessage, 'unable to locate') || str_contains($normalizedMessage, 'no record') || str_contains($normalizedMessage, 'not found')) {
            return 'norecord';
        }

        return 'error';
    }

    /**
     * @param array<string, mixed>|null $parsed
     */
    private function extractResponseCode(?array $parsed, int $statusCode, string $resultStatus): string
    {
        if ($parsed !== null) {
            foreach (['response_code', 'code', 'status_code'] as $key) {
                $value = $parsed[$key] ?? ($parsed['data'][$key] ?? null);
                if (is_string($value) && trim($value) !== '') {
                    return trim($value);
                }
            }
        }

        return $statusCode > 0 ? 'HTTP_' . $statusCode : 'RPA_' . strtoupper($resultStatus);
    }

    /**
     * @param array<string, mixed>|null $parsed
     */
    private function extractExternalReference(?array $parsed): ?string
    {
        if ($parsed === null) {
            return null;
        }

        foreach (['external_reference_no', 'reference_no', 'ticket_no', 'ticket_number'] as $key) {
            $value = $parsed[$key] ?? ($parsed['data'][$key] ?? null);
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed>|null $parsed
     * @return array<int, string>
     */
    private function extractQuickReplies(?array $parsed): array
    {
        if ($parsed === null) {
            return [];
        }

        $options = $parsed['quick_replies'] ?? $parsed['options'] ?? $parsed['choices'] ?? $parsed['data']['quick_replies'] ?? null;
        if (!is_array($options)) {
            return [];
        }

        $result = [];
        foreach ($options as $option) {
            if (is_string($option) && trim($option) !== '') {
                $result[] = trim($option);
                continue;
            }

            if (is_array($option)) {
                $label = $option['label'] ?? $option['text'] ?? $option['message'] ?? $option['value'] ?? null;
                if (is_string($label) && trim($label) !== '') {
                    $result[] = trim($label);
                }
            }
        }

        return array_values(array_unique($result));
    }

    private function nextAttemptNumber(string $requestId): int
    {
        $latest = $this->cimsResultRepository->findLatestByRequestId($requestId);
        if ($latest === null || !isset($latest['attempt_no'])) {
            return 1;
        }

        return ((int) $latest['attempt_no']) + 1;
    }
}
