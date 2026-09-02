<?php

declare(strict_types=1);

namespace Cidb\Backend\Services;

use Cidb\Backend\Config\DatabaseConnection;
use Cidb\Backend\Repositories\CimsVerificationResultRepository;
use Cidb\Backend\Repositories\ServiceRequestRepository;
use Cidb\Backend\Utils\Exceptions\AppException;
use Cidb\Backend\Utils\JsonHelper;
use Cidb\Backend\Utils\Logger;

final class VerificationService extends AbstractService
{
    /** The RPA bot expects the spoken-language name in sLanguage, not the ISO code. */
    private const RPA_LANGUAGE_WORDS = ['ms' => 'Malay', 'en' => 'English'];

    public function __construct(
        DatabaseConnection $connection,
        private readonly CimsVerificationResultRepository $cimsResultRepository,
        private readonly ServiceRequestRepository $requestRepository,
        private readonly AuditService $auditService,
        private readonly RpaBotService $rpaBotService,
        private readonly Logger $logger
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

    public function verifyCims(string $requestId, ?string $mockOutcome = null, array $context = [], int $sAttempt = 1): array
    {
        $traceId = $this->uuid();
        $this->logger->debug('RPA trace verifyCims start.', [
            'trace_id' => $traceId,
            'request_id' => $requestId,
            'timestamp' => $this->now(),
        ]);

        $request = $this->requestRepository->findById($requestId);
        if ($request === null) {
            throw new AppException('Request not found.', 404, 'REQUEST_NOT_FOUND');
        }

        $botPayload = $this->buildBotPayload($requestId, $request, $context, $sAttempt);
        $botResult = $this->rpaBotService->triggerTicketInsert($botPayload);
        $normalized = $this->normalizeBotResult($botResult);
        $isAcknowledgement = (bool) ($normalized['is_acknowledgement'] ?? false);
        $rawBotText = trim((string) ($botResult['raw_response_text'] ?? ''));

        $result = $this->transactional(function () use ($requestId, $context, $sAttempt, $normalized, $botResult, $traceId, $rawBotText): array {
            $displayMessage = trim((string) ($normalized['display_message'] ?? ''));
            $payload = [
                'request_id' => $requestId,
                'attempt_no' => $this->nextAttemptNumber($requestId),
                'result_status' => $normalized['result_status'],
                'retry_available' => (bool) ($normalized['retry_available'] ?? false),
                'response_code' => $normalized['response_code'],
                'response_message' => $normalized['response_message'],
                'external_reference_no' => $normalized['external_reference_no'],
                'latency_ms' => $botResult['duration_ms'] ?? null,
                'rpa_response_text' => $rawBotText !== '' ? $rawBotText : (string) ($normalized['rpa_response_text'] ?? ''),
                'display_message' => $displayMessage,
                'response_payload' => JsonHelper::encode([
                    'context' => array_merge($context, ['sAttempt' => $sAttempt]),
                    'bot_response' => $botResult['parsed_response'] ?? $botResult['raw_response_text'] ?? null,
                ], true),
                'verified_at' => $this->now(),
                'created_at' => $this->now(),
            ];

            $inserted = $this->cimsResultRepository->insert($payload);

            $this->logger->debug('RPA trace verification row inserted.', [
                'trace_id' => $traceId,
                'request_id' => $requestId,
                'verification_id' => $inserted['id'] ?? null,
                'inserted_at' => $inserted['created_at'] ?? null,
                'rpa_response_text_length' => strlen((string) ($inserted['rpa_response_text'] ?? '')),
                'rpa_response_text_is_empty' => trim((string) ($inserted['rpa_response_text'] ?? '')) === '',
            ]);

            return $inserted;
        });

        $verification = $result;

        $this->logger->debug('RPA trace verifyCims resolved.', [
            'trace_id' => $traceId,
            'request_id' => $requestId,
            'verification_id' => $verification['id'] ?? null,
            'result_status' => $verification['result_status'] ?? null,
            'rpa_response_text_length' => strlen((string) ($verification['rpa_response_text'] ?? '')),
            'rpa_response_text_is_empty' => trim((string) ($verification['rpa_response_text'] ?? '')) === '',
        ]);

        $this->auditService->record('cims_verification_completed', 'RPA bot verification completed.', [
            'request_id' => $requestId,
            'status' => $verification['result_status'] ?? $normalized['result_status'],
            'response_code' => $verification['response_code'] ?? $normalized['response_code'],
            'http_status' => $botResult['status_code'] ?? null,
            'acknowledged' => $isAcknowledgement,
        ], 'info', null, $requestId, 'integration');

        $rawBotText = trim((string) ($verification['rpa_response_text'] ?? ''));

        return array_merge($verification, [
            'response_message' => (string) ($verification['response_message'] ?? $normalized['response_message']),
            'display_message' => trim((string) ($verification['display_message'] ?? '')),
            'quick_replies' => $normalized['quick_replies'],
            'bot_response_text' => $rawBotText !== '' ? $rawBotText : (string) ($botResult['raw_response_text'] ?? ''),
            'bot_response_body' => $verification['response_payload'] ?? ($botResult['parsed_response'] ?? null),
            'http_status' => $botResult['status_code'] ?? null,
            'request_payload' => $botPayload,
            'is_pending' => ($verification['result_status'] ?? null) === 'pending',
        ]);
    }

    /**
     * Fires the RPA ticket-insert bot for a FAQ assistance enquiry and records the
     * acknowledgement as a `cims_verification_results` row (mirrors verifyCims, without
     * the cancellation-specific outcome handling). The RPA later writes the result back
     * into the same row; the chatbot polls `GET /submission/{id}` until then.
     *
     * @param array<string, mixed> $context topic_code, customer_name, id_number, phone, email, state, language_code
     * @return array<string, mixed>
     */
    public function verifyFaqAssistance(string $requestId, array $context, int $sAttempt = 1): array
    {
        $traceId = $this->uuid();
        $this->logger->debug('RPA trace verifyFaqAssistance start.', [
            'trace_id' => $traceId,
            'request_id' => $requestId,
            'timestamp' => $this->now(),
        ]);

        $request = $this->requestRepository->findById($requestId);
        if ($request === null) {
            throw new AppException('Request not found.', 404, 'REQUEST_NOT_FOUND');
        }

        $botPayload = $this->buildFaqAssistanceBotPayload($context, $sAttempt);
        $botResult = $this->rpaBotService->triggerTicketInsert($botPayload);
        $normalized = $this->normalizeBotResult($botResult);
        $rawBotText = trim((string) ($botResult['raw_response_text'] ?? ''));

        $verification = $this->transactional(function () use ($requestId, $context, $sAttempt, $normalized, $botResult, $traceId, $rawBotText): array {
            $payload = [
                'request_id' => $requestId,
                'attempt_no' => $this->nextAttemptNumber($requestId),
                'result_status' => $normalized['result_status'],
                'retry_available' => (bool) ($normalized['retry_available'] ?? false),
                'response_code' => $normalized['response_code'],
                'response_message' => $normalized['response_message'],
                'external_reference_no' => $normalized['external_reference_no'],
                'latency_ms' => $botResult['duration_ms'] ?? null,
                'rpa_response_text' => $rawBotText !== '' ? $rawBotText : (string) ($normalized['rpa_response_text'] ?? ''),
                'display_message' => trim((string) ($normalized['display_message'] ?? '')),
                'response_payload' => JsonHelper::encode([
                    'context' => array_merge($context, ['sAttempt' => $sAttempt]),
                    'bot_response' => $botResult['parsed_response'] ?? $botResult['raw_response_text'] ?? null,
                ], true),
                'verified_at' => $this->now(),
                'created_at' => $this->now(),
            ];

            $inserted = $this->cimsResultRepository->insert($payload);

            $this->logger->debug('RPA trace FAQ assistance verification row inserted.', [
                'trace_id' => $traceId,
                'request_id' => $requestId,
                'verification_id' => $inserted['id'] ?? null,
                'result_status' => $inserted['result_status'] ?? null,
            ]);

            return $inserted;
        });

        $this->auditService->record('faq_assistance_verification_completed', 'FAQ assistance RPA bot triggered.', [
            'request_id' => $requestId,
            'status' => $verification['result_status'] ?? $normalized['result_status'],
            'http_status' => $botResult['status_code'] ?? null,
        ], 'info', null, $requestId, 'integration');

        return array_merge($verification, [
            'response_message' => (string) ($verification['response_message'] ?? $normalized['response_message']),
            'display_message' => trim((string) ($verification['display_message'] ?? '')),
            'bot_response_text' => $rawBotText !== '' ? $rawBotText : (string) ($botResult['raw_response_text'] ?? ''),
            'http_status' => $botResult['status_code'] ?? null,
            'request_payload' => $botPayload,
            'is_pending' => ($verification['result_status'] ?? null) === 'pending',
        ]);
    }

    public function latestByRequestId(string $requestId): ?array
    {
        $verification = $this->cimsResultRepository->findLatestByRequestId($requestId);

        $this->logger->debug('RPA trace latestByRequestId lookup.', [
            'request_id' => $requestId,
            'verification_id' => $verification['id'] ?? null,
            'result_status' => $verification['result_status'] ?? null,
            'rpa_response_text_length' => strlen((string) ($verification['rpa_response_text'] ?? '')),
            'rpa_response_text_is_empty' => trim((string) ($verification['rpa_response_text'] ?? '')) === '',
            'display_message_length' => strlen((string) ($verification['display_message'] ?? '')),
            'display_message_is_empty' => trim((string) ($verification['display_message'] ?? '')) === '',
        ]);

        return $verification;
    }

    public function isUnclearDocumentRetryEligible(array $verification, string $serviceType): bool
    {
        $serviceType = mb_strtolower(trim($serviceType));
        if (!in_array($serviceType, ['individual', 'company'], true)) {
            return false;
        }

        $text = $this->verificationText($verification);
        if ($text === '') {
            return false;
        }

        return $this->matchesUnclearDocumentText($text, $serviceType);
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function verifyCompanyCancellation(string $requestId, array $context, int $sAttempt = 1): array
    {
        $traceId = $this->uuid();
        $this->logger->debug('RPA trace verifyCompanyCancellation start.', [
            'trace_id' => $traceId,
            'request_id' => $requestId,
            'timestamp' => $this->now(),
        ]);

        $request = $this->requestRepository->findById($requestId);
        if ($request === null) {
            throw new AppException('Request not found.', 404, 'REQUEST_NOT_FOUND');
        }

        $botPayload = $this->buildCompanyBotPayload($requestId, $request, $context, $sAttempt);
        $botResult = $this->rpaBotService->triggerCompanyCancellation($botPayload);
        $normalized = $this->normalizeCompanyBotResult($botResult);
        $isPending = (bool) ($normalized['is_pending'] ?? false);
        $rawBotText = trim((string) ($botResult['raw_response_text'] ?? ''));

        $result = $this->transactional(function () use ($requestId, $context, $sAttempt, $normalized, $botResult, $traceId, $rawBotText): array {
            $displayMessage = trim((string) ($normalized['display_message'] ?? ''));
            $payload = [
                'request_id' => $requestId,
                'attempt_no' => $this->nextAttemptNumber($requestId),
                'result_status' => $normalized['result_status'],
                'retry_available' => (bool) ($normalized['retry_available'] ?? false),
                'response_code' => $normalized['response_code'],
                'response_message' => $normalized['response_message'],
                'external_reference_no' => $normalized['external_reference_no'],
                'latency_ms' => $botResult['duration_ms'] ?? null,
                'rpa_response_text' => $rawBotText !== '' ? $rawBotText : (string) ($normalized['rpa_response_text'] ?? ''),
                'display_message' => $displayMessage,
                'response_payload' => JsonHelper::encode([
                    'context' => array_merge($context, ['sAttempt' => $sAttempt]),
                    'bot_response' => $botResult['parsed_response'] ?? $botResult['raw_response_text'] ?? null,
                ], true),
                'verified_at' => $this->now(),
                'created_at' => $this->now(),
            ];

            $inserted = $this->cimsResultRepository->insert($payload);

            $this->logger->debug('RPA trace company verification row inserted.', [
                'trace_id' => $traceId,
                'request_id' => $requestId,
                'verification_id' => $inserted['id'] ?? null,
                'inserted_at' => $inserted['created_at'] ?? null,
                'rpa_response_text_length' => strlen((string) ($inserted['rpa_response_text'] ?? '')),
                'rpa_response_text_is_empty' => trim((string) ($inserted['rpa_response_text'] ?? '')) === '',
            ]);

            return $inserted;
        });

        $this->logger->debug('RPA trace verifyCompanyCancellation resolved.', [
            'trace_id' => $traceId,
            'request_id' => $requestId,
            'status' => $result['result_status'] ?? null,
            'http_status' => $botResult['status_code'] ?? null,
        ]);

        $this->auditService->record('company_rpa_verification_completed', 'Company RPA bot verification completed.', [
            'request_id' => $requestId,
            'status' => $result['result_status'] ?? null,
            'http_status' => $botResult['status_code'] ?? null,
        ], 'info', null, $requestId, 'integration');

        return array_merge($result, [
            'response_message' => (string) ($result['response_message'] ?? $normalized['response_message']),
            'display_message' => trim((string) ($result['display_message'] ?? '')),
            'quick_replies' => [],
            'bot_response_text' => $rawBotText !== '' ? $rawBotText : (string) ($botResult['raw_response_text'] ?? ''),
            'bot_response_body' => $result['response_payload'] ?? ($botResult['parsed_response'] ?? null),
            'http_status' => $botResult['status_code'] ?? null,
            'request_payload' => $botPayload,
            'is_pending' => $isPending,
            'is_acknowledgement' => (bool) ($normalized['is_acknowledgement'] ?? false),
        ]);
    }

    /**
     * @param array<string, mixed> $snapshot
     * @param array<string, mixed> $ocrVerification
     * @return array<string, mixed>
     */
    public function triggerOcrFinalFailure(array $snapshot, array $ocrVerification, int $sAttempt = 0): array
    {
        $traceId = $this->uuid();
        $session = is_array($snapshot['session'] ?? null) ? $snapshot['session'] : [];
        $sessionId = (string) ($session['id'] ?? '');
        $serviceType = mb_strtolower(trim((string) ($snapshot['service']['service_type'] ?? 'individual')));

        $this->logger->debug('RPA trace triggerOcrFinalFailure start.', [
            'trace_id' => $traceId,
            'session_id' => $sessionId,
            'service_type' => $serviceType,
            's_attempt' => $sAttempt,
            'timestamp' => $this->now(),
        ]);

        $request = [
            'submission_language_code' => $session['language_code'] ?? ($snapshot['language']['language'] ?? null),
        ];

        if ($serviceType === 'company') {
            $botPayload = $this->buildCompanyBotPayload($sessionId, $request, [
                'session' => $session,
                'company' => is_array($snapshot['company'] ?? null) ? $snapshot['company'] : [],
                'mobile' => is_array($snapshot['mobile'] ?? null) ? $snapshot['mobile'] : [],
                'email' => is_array($snapshot['email'] ?? null) ? $snapshot['email'] : [],
                'state' => is_array($snapshot['state'] ?? null) ? $snapshot['state'] : [],
            ], $sAttempt);
        } else {
            $botPayload = $this->buildBotPayload($sessionId, $request, [
                'session' => $session,
                'full_name' => is_array($snapshot['full_name'] ?? null) ? $snapshot['full_name'] : [],
                'identity_number' => is_array($snapshot['identity_number'] ?? null) ? $snapshot['identity_number'] : [],
                'mobile' => is_array($snapshot['mobile'] ?? null) ? $snapshot['mobile'] : [],
                'email' => is_array($snapshot['email'] ?? null) ? $snapshot['email'] : [],
                'state' => is_array($snapshot['state'] ?? null) ? $snapshot['state'] : [],
                'applicant' => null,
            ], $sAttempt);
        }

        $botResult = $this->rpaBotService->triggerTicketInsert($botPayload);

        $this->auditService->record('ocr_final_failure_rpa_triggered', 'OCR final failure RPA triggered.', [
            'session_id' => $sessionId,
            'service_type' => $serviceType,
            's_attempt' => $sAttempt,
            'http_status' => $botResult['status_code'] ?? null,
        ], 'warning', $sessionId, null, 'integration');

        return array_merge($botResult, [
            'request_payload' => $botPayload,
            'service_type' => $serviceType,
            'ocr_verification' => $ocrVerification,
        ]);
    }

    /**
     * @param array<string, mixed> $request
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function buildBotPayload(string $requestId, array $request, array $context, int $sAttempt): array
    {
        $session = is_array($context['session'] ?? null) ? $context['session'] : [];
        $applicant = is_array($context['applicant'] ?? null) ? $context['applicant'] : [];
        $draft = is_array($session['draft_payload'] ?? null) ? $session['draft_payload'] : [];
        $identificationNumber = $this->resolveIdentificationNumber($context, $applicant);
        $botPayload = [
            'company' => 'CIDB',
            'scenario_key' => 'cidb_masterbot',
            'channel' => 'Chatbot',
            'fields' => [
                'sCustomerType' => 'Individual',
                'sCustomerName' => $this->resolveBotPayloadValue([
                    $context['full_name']['full_name'] ?? null,
                    $applicant['full_name'] ?? null,
                ]),
                'sIdentificationNumber' => $identificationNumber,
                'sContactNumber' => $this->resolveBotPayloadValue([
                    $draft['mobile'] ?? null,
                    $context['mobile']['mobile'] ?? null,
                ]),
                'sEmail' => $this->resolveBotPayloadValue([
                    $draft['email'] ?? null,
                    $context['email']['email'] ?? null,
                ]),
                'sLocationArea' => $this->resolveBotPayloadValue([
                    $draft['state_name'] ?? null,
                    $context['state']['state'] ?? null,
                ]),
                'sLanguage' => $this->resolveBotPayloadValue([
                    $request['submission_language_code'] ?? null,
                    $session['language_code'] ?? null,
                    $draft['language_code'] ?? null,
                ]),
                'sAttempt' => (string) $sAttempt,
            ],
        ];

        $this->assertBotPayloadReady($botPayload);

        return $botPayload;
    }

    /**
     * Builds the RPA ticket-insert payload for a FAQ assistance enquiry. Same envelope
     * as buildBotPayload(); sCustomerType carries the PPK/SPKK/STB renewal type and
     * sLanguage the spoken-language word. The `cidb_masterbot` scenario also requires
     * sCompanyName + sSSMNumber for this flow (the enquiry form is company-only).
     *
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function buildFaqAssistanceBotPayload(array $context, int $sAttempt): array
    {
        $botPayload = [
            'company' => 'CIDB',
            'scenario_key' => 'cidb_masterbot',
            'channel' => 'Chatbot',
            'fields' => [
                'sCustomerType' => strtoupper(trim((string) ($context['topic_code'] ?? ''))),
                'sCustomerName' => $this->resolveBotPayloadValue([$context['customer_name'] ?? null]),
                'sIdentificationNumber' => $this->resolveBotPayloadValue([$context['id_number'] ?? null]),
                'sCompanyName' => $this->resolveBotPayloadValue([$context['company_name'] ?? null]),
                'sSSMNumber' => $this->resolveBotPayloadValue([$context['company_registration_no'] ?? null]),
                'sContactNumber' => $this->resolveBotPayloadValue([$context['phone'] ?? null]),
                'sEmail' => $this->resolveBotPayloadValue([$context['email'] ?? null]),
                'sLocationArea' => $this->resolveBotPayloadValue([$context['state'] ?? null]),
                'sLanguage' => $this->rpaLanguageWord($context['language_code'] ?? null),
                'sAttempt' => (string) $sAttempt,
            ],
        ];

        $this->assertBotPayloadReady($botPayload);

        $missing = [];
        foreach (['sCompanyName', 'sSSMNumber'] as $field) {
            if (trim((string) ($botPayload['fields'][$field] ?? '')) === '') {
                $missing[] = $field;
            }
        }
        if ($missing !== []) {
            throw new AppException('RPA payload is missing required field(s).', 422, 'RPA_PAYLOAD_INVALID', [
                'missing_fields' => $missing,
            ]);
        }

        return $botPayload;
    }

    /**
     * Maps an ISO language code to the spoken-language word the RPA bot expects.
     * Falls back to the trimmed input so assertBotPayloadReady still passes.
     */
    private function rpaLanguageWord(?string $code): ?string
    {
        $normalized = strtolower(trim((string) $code));
        if ($normalized === '') {
            return null;
        }

        return self::RPA_LANGUAGE_WORDS[$normalized] ?? $normalized;
    }

    /**
     * @param array<string, mixed> $request
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function buildCompanyBotPayload(string $requestId, array $request, array $context, int $sAttempt): array
    {
        $session = is_array($context['session'] ?? null) ? $context['session'] : [];
        $draft = is_array($session['draft_payload'] ?? null) ? $session['draft_payload'] : [];
        $company = is_array($context['company'] ?? null) ? $context['company'] : [];

        $payload = [
            'company' => 'CIDB',
            'scenario_key' => 'cidb_masterbot',
            'channel' => 'Chatbot',
            'fields' => [
                'sCustomerType' => 'Company',
                'sContactNumber' => $this->resolveBotPayloadValue([
                    $draft['company_contact_number'] ?? null,
                    $draft['mobile'] ?? null,
                    $context['mobile']['mobile'] ?? null,
                ]),
                'sEmail' => $this->resolveBotPayloadValue([
                    $company['company_email'] ?? null,
                    $draft['company_email'] ?? null,
                    $context['email']['email'] ?? null,
                ]),
                'sLocationArea' => $this->resolveBotPayloadValue([
                    $draft['state_name'] ?? null,
                    $context['state']['state'] ?? null,
                ]),
                'sCompanyName' => $company['company_name'] ?? null,
                'sSSMNumber' => $company['ppk_number'] ?? null,
                'sIdentificationNumber' => $this->resolveBotPayloadValue([
                    $draft['director_identity_number_compact'] ?? null,
                    $draft['director_identity_number'] ?? null,
                ]),
                'sCustomerName' => $this->resolveBotPayloadValue([
                    $draft['director_full_name'] ?? null,
                ]),
                'sLanguage' => $this->resolveBotPayloadValue([
                    $request['submission_language_code'] ?? null,
                    $session['language_code'] ?? null,
                    $draft['language_code'] ?? null,
                ]),
                'sAttempt' => (string) $sAttempt,
            ],
        ];

        $this->assertCompanyBotPayloadReady($payload);

        return $payload;
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
     * @param array<string, mixed> $botPayload
     */
    private function assertBotPayloadReady(array $botPayload): void
    {
        $fields = is_array($botPayload['fields'] ?? null) ? $botPayload['fields'] : [];
        $missing = [];

        foreach ([
            'sCustomerType',
            'sCustomerName',
            'sIdentificationNumber',
            'sContactNumber',
            'sEmail',
            'sLocationArea',
            'sLanguage',
            'sAttempt',
        ] as $field) {
            $value = $fields[$field] ?? null;
            if ($field === 'sAttempt') {
                if (!is_int($value) && !(is_string($value) && trim($value) !== '' && ctype_digit(trim($value)))) {
                    $missing[] = $field;
                }
                continue;
            }

            if (!is_string($value) || trim($value) === '') {
                $missing[] = $field;
            }
        }

        if ($missing !== []) {
            throw new AppException(
                'RPA payload is missing required field(s).',
                422,
                'RPA_PAYLOAD_INVALID',
                [
                    'missing_fields' => $missing,
                ]
            );
        }
    }

    /**
     * @param array<string, mixed> $botPayload
     */
    private function assertCompanyBotPayloadReady(array $botPayload): void
    {
        $fields = is_array($botPayload['fields'] ?? null) ? $botPayload['fields'] : [];
        $missing = [];

        foreach ([
            'sCustomerType',
            'sCompanyName',
            'sSSMNumber',
            'sContactNumber',
            'sEmail',
            'sLocationArea',
            'sLanguage',
            'sAttempt',
        ] as $field) {
            $value = $fields[$field] ?? null;
            if ($field === 'sAttempt') {
                if (!is_int($value) && !(is_string($value) && trim($value) !== '' && ctype_digit(trim($value)))) {
                    $missing[] = $field;
                }
                continue;
            }

            if (!is_string($value) || trim($value) === '') {
                $missing[] = $field;
            }
        }

        if ($missing !== []) {
            throw new AppException(
                'Company RPA payload is missing required field(s).',
                422,
                'RPA_PAYLOAD_INVALID',
                [
                    'missing_fields' => $missing,
                ]
            );
        }
    }

    /**
     * @param array{success?: bool, status_code?: int, raw_response_text?: string, parsed_response?: array<string, mixed>|null, error_message?: ?string} $botResult
     * @return array<string, mixed>
     */
    private function normalizeBotResult(array $botResult): array
    {
        $rawText = trim((string) ($botResult['raw_response_text'] ?? ''));
        $parsed = is_array($botResult['parsed_response'] ?? null) ? $botResult['parsed_response'] : null;
        if ($parsed === null && $rawText !== '') {
            $decodedRawText = json_decode($rawText, true);
            if (is_array($decodedRawText)) {
                $parsed = $decodedRawText;
            }
        }
        $isAcknowledgement = $this->isAcknowledgementResponse($parsed);

        $responseMessage = $this->extractResponseMessage($parsed, $rawText, (string) ($botResult['error_message'] ?? ''));
        if ($isAcknowledgement && $responseMessage === '') {
            $responseMessage = $rawText !== '' ? $rawText : JsonHelper::encode($parsed ?? [], true);
        }

        $resultStatus = $this->extractResultStatus($parsed, $responseMessage, (int) ($botResult['status_code'] ?? 0), (bool) ($botResult['success'] ?? false));
        if ($isAcknowledgement) {
            $resultStatus = 'pending';
        }
        $quickReplies = $this->extractQuickReplies($parsed);

        return [
            'result_status' => $resultStatus,
            'retry_available' => $isAcknowledgement ? false : $this->extractRetryAvailable($parsed),
            'response_code' => $this->extractResponseCode($parsed, (int) ($botResult['status_code'] ?? 0), $resultStatus),
            'response_message' => $responseMessage,
            'external_reference_no' => $this->extractExternalReference($parsed),
            'quick_replies' => $quickReplies,
            'rpa_response_text' => $rawText !== '' ? $rawText : $responseMessage,
            'is_acknowledgement' => $isAcknowledgement,
        ];
    }

    /**
     * @param array{success?: bool, status_code?: int, raw_response_text?: string, parsed_response?: array<string, mixed>|null, error_message?: ?string} $botResult
     * @return array<string, mixed>
     */
    private function normalizeCompanyBotResult(array $botResult): array
    {
        $rawText = trim((string) ($botResult['raw_response_text'] ?? ''));
        $parsed = is_array($botResult['parsed_response'] ?? null) ? $botResult['parsed_response'] : null;
        if ($parsed === null && $rawText !== '') {
            $decodedRawText = json_decode($rawText, true);
            if (is_array($decodedRawText)) {
                $parsed = $decodedRawText;
            }
        }
        $isAcknowledgement = $this->isAcknowledgementResponse($parsed);
        $responseMessage = $this->extractCompanyResponseMessage($parsed, $rawText, (string) ($botResult['error_message'] ?? ''));
        $resultStatus = $this->extractCompanyResultStatus($parsed, $responseMessage, (int) ($botResult['status_code'] ?? 0), (bool) ($botResult['success'] ?? false));
        if ($isAcknowledgement) {
            $resultStatus = 'pending';
        }

        $displayMessage = $resultStatus === 'pending' ? '' : $responseMessage;

        return [
            'result_status' => $resultStatus,
            'retry_available' => $isAcknowledgement ? false : $this->extractRetryAvailable($parsed),
            'response_message' => $responseMessage,
            'display_message' => $displayMessage,
            'response_code' => $this->extractResponseCode($parsed, (int) ($botResult['status_code'] ?? 0), $resultStatus),
            'external_reference_no' => $this->extractExternalReference($parsed),
            'rpa_response_text' => $rawText !== '' ? $rawText : $responseMessage,
            'is_acknowledgement' => $isAcknowledgement,
            'is_pending' => $resultStatus === 'pending',
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

        return 'We are unable to complete the verification at this time. Please try again later or contact our support team.';
    }

    /**
     * @param array<string, mixed>|null $parsed
     */
    private function extractCompanyResponseMessage(?array $parsed, string $rawText, string $errorMessage): string
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

        return 'We are unable to complete the company cancellation at this time. Please try again later or contact our support team.';
    }

    /**
     * @param array<string, mixed>|null $parsed
     */
    private function isAcknowledgementResponse(?array $parsed): bool
    {
        if ($parsed === null) {
            return false;
        }

        $status = strtolower(trim((string) ($parsed['status'] ?? ($parsed['data']['status'] ?? ''))));
        $scheduleIdCandidates = [
            $parsed['schedule_id'] ?? null,
            $parsed['data']['schedule_id'] ?? null,
            $parsed['inserts'][0]['schedule_id'] ?? null,
            $parsed['data']['inserts'][0]['schedule_id'] ?? null,
        ];
        $scheduleId = '';
        foreach ($scheduleIdCandidates as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                $scheduleId = trim($candidate);
                break;
            }
        }

        return $status === 'inserted' && $scheduleId !== '';
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

            if ($this->matchesUnclearDocumentText($normalizedMessage, 'individual')) {
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

        if ($this->matchesUnclearDocumentText($normalizedMessage, 'individual')) {
            return 'error';
        }

        return 'error';
    }

    /**
     * @param array<string, mixed>|null $parsed
     */
    private function extractCompanyResultStatus(?array $parsed, string $message, int $statusCode, bool $success): string
    {
        $allowed = ['approved', 'rejected', 'manual_review', 'pending', 'failed'];
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
            if (str_contains($normalizedMessage, 'manual')) {
                return 'manual_review';
            }

            if (str_contains($normalizedMessage, 'reject') || str_contains($normalizedMessage, 'not eligible')) {
                return 'rejected';
            }

            if ($this->matchesUnclearDocumentText($normalizedMessage, 'company')) {
                return 'failed';
            }

            if (str_contains($normalizedMessage, 'pending') || str_contains($normalizedMessage, 'processing')) {
                return 'pending';
            }

            return 'pending';
        }

        if (str_contains($normalizedMessage, 'manual')) {
            return 'manual_review';
        }

        if (str_contains($normalizedMessage, 'reject') || str_contains($normalizedMessage, 'not eligible')) {
            return 'rejected';
        }

        if ($this->matchesUnclearDocumentText($normalizedMessage, 'company')) {
            return 'failed';
        }

        if (str_contains($normalizedMessage, 'pending') || str_contains($normalizedMessage, 'processing')) {
            return 'pending';
        }

        return 'failed';
    }

    /**
     * @param array<string, mixed>|null $parsed
     */
    private function extractRetryAvailable(?array $parsed): bool
    {
        $candidates = [];

        if ($parsed !== null) {
            $candidates[] = $parsed['retry_available'] ?? null;
            $candidates[] = $parsed['data']['retry_available'] ?? null;
        }

        foreach ($candidates as $candidate) {
            if (is_bool($candidate)) {
                if ($candidate === true) {
                    return true;
                }

                continue;
            }

            if (is_int($candidate)) {
                if ($candidate === 1) {
                    return true;
                }

                if ($candidate === 0) {
                    continue;
                }
            }

            if (is_string($candidate)) {
                $normalized = strtolower(trim($candidate));
                if (in_array($normalized, ['true', '1', 'yes', 'y'], true)) {
                    return true;
                }

                if (in_array($normalized, ['false', '0', 'no', 'n'], true)) {
                }
            }
        }

        return false;
    }

    private function verificationText(array $verification): string
    {
        $pieces = [];
        $this->collectVerificationText($verification['response_message'] ?? null, $pieces);
        $this->collectVerificationText($verification['display_message'] ?? null, $pieces);
        $this->collectVerificationText($verification['rpa_response_text'] ?? null, $pieces);
        $this->collectVerificationText($verification['bot_response_text'] ?? null, $pieces);
        $this->collectVerificationText($verification['message'] ?? null, $pieces);
        $this->collectVerificationText($verification['response_payload'] ?? null, $pieces);
        $this->collectVerificationText($verification['bot_response_body'] ?? null, $pieces);

        $pieces = array_values(array_filter(array_map(
            static fn (string $piece): string => trim(preg_replace('/\s+/', ' ', $piece) ?? ''),
            $pieces
        )));

        return trim(implode(' ', array_values(array_unique($pieces))));
    }

    /**
     * @param array<int, string> $pieces
     */
    private function collectVerificationText(mixed $value, array &$pieces): void
    {
        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed === '') {
                return;
            }

            $pieces[] = $trimmed;

            $decoded = json_decode($trimmed, true);
            if (is_array($decoded)) {
                $this->collectVerificationText($decoded, $pieces);
            }

            return;
        }

        if (!is_array($value)) {
            return;
        }

        foreach ($value as $nestedValue) {
            $this->collectVerificationText($nestedValue, $pieces);
        }
    }

    private function matchesUnclearDocumentText(string $text, string $serviceType): bool
    {
        $normalizedText = mb_strtolower(trim($text));
        if ($normalizedText === '') {
            return false;
        }

        if ($serviceType === 'individual' && $this->containsAnyText($normalizedText, [
            'missing signature',
            'signature missing',
            'no signature',
            'without signature',
            'tiada tandatangan',
            'tandatangan tiada',
        ])) {
            return true;
        }

        $unclearTerms = [
            'unclear',
            'blurry',
            'blurred',
            'blur ',
            ' blur',
            'unreadable',
            'illegible',
            'not clear',
            'not readable',
            'cannot be read',
            'cannot read',
            'poor quality',
            'low quality',
            'too dark',
            'shadow',
            'shadows',
            'cropped',
            'cropping',
            'cut off',
            'not crossed',
            'crossed',
            'does not meet',
            'doesnt meet',
            'does not meet the required standard',
            'not meet',
            'not meeting',
            'required standard',
            'tidak jelas',
            'kabur',
            'tidak boleh dibaca',
            'tidak dapat dibaca',
            'tidak memenuhi',
            'tidak menepati',
            'tidak dipalang',
            'belum dipalang',
        ];

        if (!$this->containsAnyText($normalizedText, $unclearTerms)) {
            return false;
        }

        $documentTerms = $serviceType === 'company'
            ? [
                'ic',
                'identity card',
                'mykad',
                'passport',
                'ssm',
                'ppk',
                'certificate',
                'document',
                'copy',
                'image',
                'photo',
                'upload',
                'uploaded',
                'director',
            ]
            : [
                'ic',
                'identity card',
                'mykad',
                'passport',
                'document',
                'copy',
                'image',
                'photo',
                'upload',
                'uploaded',
                'front',
                'back',
                'signature',
            ];

        return $this->containsAnyText($normalizedText, $documentTerms);
    }

    /**
     * @param array<int, string> $terms
     */
    private function containsAnyText(string $text, array $terms): bool
    {
        foreach ($terms as $term) {
            $needle = trim(mb_strtolower($term));
            if ($needle === '') {
                continue;
            }

            if (str_contains($needle, ' ') || str_contains($needle, '/') || str_contains($needle, '-')) {
                if (str_contains($text, $needle)) {
                    return true;
                }

                continue;
            }

            if (preg_match('/\b' . preg_quote($needle, '/') . '\b/u', $text) === 1) {
                return true;
            }
        }

        return false;
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
