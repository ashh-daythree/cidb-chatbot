<?php

declare(strict_types=1);

namespace Cidb\Backend\Services;

use Cidb\Backend\Config\DatabaseConnection;
use Cidb\Backend\Repositories\DocumentVerificationRepository;
use Cidb\Backend\Validators\SubmissionReadinessValidator;
use Cidb\Backend\Utils\JsonHelper;
use Cidb\Backend\Utils\Exceptions\AppException;

final class SubmissionService extends AbstractService
{
    public function __construct(
        DatabaseConnection $connection,
        private readonly SessionService $sessionService,
        private readonly ApplicantService $applicantService,
        private readonly RequestService $requestService,
        private readonly DocumentService $documentService,
        private readonly VerificationService $verificationService,
        private readonly OcrVerificationService $ocrVerificationService,
        private readonly DocumentVerificationRepository $documentVerificationRepository,
        private readonly StatusService $statusService,
        private readonly SubmissionReadinessValidator $readinessValidator,
        private readonly AuditService $auditService
    ) {
        parent::__construct($connection);
    }

    public function submit(array $payload): array
    {
        $sessionId = $this->extractSessionId($payload);
        $snapshot = $this->buildSubmissionSnapshot($sessionId);
        $documents = $this->documentService->findSessionDocuments($sessionId);
        $serviceType = $this->resolveServiceType($snapshot);

        if ($serviceType === 'company') {
            $validation = $this->validateCompanyReadiness($snapshot);
        } else {
            $validation = $this->readinessValidator->validate($snapshot);
        }

        if (!$validation->isValid()) {
            $this->auditService->recordValidationFailure('submission_validation_failed', $validation->errors(), $sessionId, null);
            throw new AppException('Submission validation failed.', 422, 'SUBMISSION_INVALID', $validation->errors());
        }

        $ocrVerification = $serviceType === 'company'
            ? $this->verifyCompanyOcrDocuments($sessionId, $snapshot, $documents)
            : $this->verifyOcrDocuments($sessionId, $snapshot, $documents);

        if ($serviceType === 'company') {
            return $this->transactional(function () use ($sessionId, $snapshot, $documents, $ocrVerification): array {
                $ocrRecord = $this->persistOcrVerification($ocrVerification, $documents, null, $sessionId);

                if (($ocrVerification['should_continue'] ?? false) !== true) {
                    return [
                        'message' => (string) ($ocrVerification['message'] ?? 'OCR verification requires reupload.'),
                        'next_action' => 'reupload',
                        'session' => $this->sessionService->getById($sessionId),
                        'applicant' => $this->applicantService->findBySessionId($sessionId),
                        'request_number' => null,
                        'request' => null,
                        'submission' => [
                            'session_id' => $sessionId,
                            'submission_status' => 'ocr_blocked',
                        ],
                        'documents' => $documents,
                        'ocr_verification' => array_merge($ocrVerification, [
                            'verification_id' => $ocrRecord['id'] ?? null,
                            'record' => $ocrRecord,
                        ]),
                        'verification' => null,
                    ];
                }

                $request = $this->requestService->findBySessionId($sessionId);
                if ($request === null) {
                    $request = $this->requestService->createCompanyFromSession($sessionId);
                }

                $documents = $this->documentService->findSessionDocuments($sessionId);
                $coverage = $this->documentService->resolveRequiredDocumentCoverage(
                    $documents,
                    'company',
                    (string) ($snapshot['director']['identity_number']['identity_type'] ?? '')
                );
                if ($coverage['missing'] !== []) {
                    throw new AppException('Required documents are missing.', 422, 'DOCUMENTS_MISSING', [
                        'missing' => $coverage['missing'],
                    ]);
                }

                $attachedDocuments = $this->documentService->attachSessionDocumentsToRequest($sessionId, (string) $request['id']);

                $this->sessionService->markSubmitted($sessionId);
                $this->requestService->markSubmitted((string) $request['id']);

                $verification = $this->verificationService->verifyCompanyCancellation((string) $request['id'], [
                    'session' => $snapshot['session'],
                    'language' => $snapshot['language'],
                    'state' => $snapshot['state'],
                    'company' => $snapshot['company'],
                    'director' => $snapshot['director'],
                    'documents' => $snapshot['documents'],
                    'reason' => $snapshot['reason'],
                    'applicant' => null,
                    'session_id' => $sessionId,
                    'request_number' => $request['request_number'] ?? null,
                ]);

                $draft = $this->decodeDraft($snapshot['session']['draft_payload'] ?? []);
                $draft['company_rpa_result'] = $verification;
                $this->sessionService->updateDraft($sessionId, $draft);

                $requestStatus = $this->mapCompanyRequestStatus((string) ($verification['result_status'] ?? 'failed'));
                $this->requestService->markStatus((string) $request['id'], $requestStatus);

                if (in_array($requestStatus, ['failed', 'rejected'], true)) {
                    $this->statusService->transitionSession($sessionId, 'failed', null, [
                        'request_id' => $request['id'] ?? null,
                        'verification' => $verification['result_status'] ?? null,
                    ], 'company_rpa_failed', 'Company RPA returned a failure status.');
                } else {
                    $this->sessionService->markCompleted($sessionId);
                }

                return [
                    'message' => (string) ($verification['display_message'] ?? $verification['response_message'] ?? 'Company cancellation request submitted.'),
                    'next_action' => 'done',
                    'session' => $this->sessionService->getById($sessionId),
                    'applicant' => null,
                    'request_number' => $request['request_number'] ?? null,
                    'request' => $this->requestService->findBySessionId($sessionId),
                    'documents' => $attachedDocuments,
                    'ocr_verification' => array_merge($ocrVerification, [
                        'verification_id' => $ocrRecord['id'] ?? null,
                        'record' => $ocrRecord,
                    ]),
                    'verification' => $verification,
                ];
            });
        }

        return $this->transactional(function () use ($sessionId, $snapshot, $documents, $ocrVerification): array {
            $ocrRecord = $this->persistOcrVerification($ocrVerification, $documents, null, $sessionId);

            if (($ocrVerification['should_continue'] ?? false) !== true) {
                return [
                    'message' => (string) ($ocrVerification['message'] ?? 'OCR verification requires reupload.'),
                    'next_action' => 'reupload',
                    'session' => $this->sessionService->getById($sessionId),
                    'applicant' => null,
                    'request_number' => null,
                    'request' => null,
                    'submission' => [
                        'session_id' => $sessionId,
                        'submission_status' => 'ocr_blocked',
                    ],
                    'documents' => $documents,
                    'ocr_verification' => array_merge($ocrVerification, [
                        'verification_id' => $ocrRecord['id'] ?? null,
                        'record' => $ocrRecord,
                    ]),
                    'verification' => null,
                ];
            }

            $applicant = $this->applicantService->findBySessionId($sessionId);
            if ($applicant === null) {
                $applicant = $this->applicantService->finalizeFromSession($sessionId);
            }

            $request = $this->requestService->findBySessionId($sessionId);
            if ($request === null) {
                $request = $this->requestService->createFromSession($sessionId);
            }

            $documents = $this->documentService->findSessionDocuments($sessionId);
            $identityType = is_array($snapshot['identity_number'] ?? null)
                ? (string) ($snapshot['identity_number']['identity_type'] ?? '')
                : '';
            $coverage = $this->documentService->resolveRequiredDocumentCoverage($documents, 'individual', $identityType);
            if ($coverage['missing'] !== []) {
                throw new AppException('Required documents are missing.', 422, 'DOCUMENTS_MISSING', [
                    'missing' => $coverage['missing'],
                ]);
            }

            $attachedDocuments = $this->documentService->attachSessionDocumentsToRequest($sessionId, (string) $request['id']);

            $this->sessionService->markSubmitted($sessionId);
            $this->requestService->markSubmitted((string) $request['id']);

            $cimsMockOutcome = is_array($snapshot['cims'] ?? null) ? ($snapshot['cims']['mock_outcome'] ?? null) : null;
            $verification = $this->verificationService->verifyCims((string) $request['id'], is_string($cimsMockOutcome) ? $cimsMockOutcome : null, [
                'session' => $snapshot['session'],
                'language' => $snapshot['language'],
                'state' => $snapshot['state'],
                'full_name' => $snapshot['full_name'],
                'identity_number' => $snapshot['identity_number'],
                'documents' => [
                    'front' => $snapshot['documents']['front'] ?? null,
                    'back' => $snapshot['documents']['back'] ?? null,
                    'signature' => $snapshot['documents']['signature'] ?? null,
                ],
                'applicant' => $applicant,
                'session_id' => $sessionId,
                'request_number' => $request['request_number'] ?? null,
            ]);

            $outcome = (string) ($verification['result_status'] ?? 'error');
            if ($outcome === 'pending') {
                $this->auditService->record('submission_pending', 'Submission is waiting for the final RPA bot response.', [
                    'session_id' => $sessionId,
                    'request_id' => $request['id'] ?? null,
                    'verification_id' => $verification['id'] ?? null,
                ], 'info', $sessionId, $request['id'] ?? null);

                return [
                    'next_action' => 'poll',
                    'session' => $this->sessionService->getById($sessionId),
                    'applicant' => $applicant,
                    'request_number' => $request['request_number'] ?? null,
                    'request' => $this->requestService->findBySessionId($sessionId),
                    'documents' => $attachedDocuments,
                    'ocr_verification' => array_merge($ocrVerification, [
                        'verification_id' => $ocrRecord['id'] ?? null,
                        'record' => $ocrRecord,
                    ]),
                    'verification' => $verification,
                ];
            }

            $this->statusService->updateCimsStatus((string) $request['id'], $outcome, [
                'verification_id' => $verification['id'] ?? null,
            ]);

            $finalStatus = match ($outcome) {
                'deleted' => 'approved',
                'linked' => 'manual_review',
                'norecord' => 'rejected',
                'error' => 'failed',
                default => 'failed',
            };

            $this->requestService->markFinalOutcome(
                (string) $request['id'],
                $finalStatus,
                in_array($outcome, ['deleted', 'linked', 'norecord'], true) ? $outcome : null
            );

            if ($finalStatus === 'failed') {
                $this->statusService->transitionSession($sessionId, 'failed', null, [
                    'request_id' => $request['id'] ?? null,
                    'verification_id' => $verification['id'] ?? null,
                ], 'verification_error', 'CIMS verification returned an error.');
            } else {
                $this->sessionService->markCompleted($sessionId);
            }

            if ($finalStatus === 'failed') {
                $this->auditService->recordSubmissionFailure('Submission completed with verification error.', [
                    'session_id' => $sessionId,
                    'request_id' => $request['id'] ?? null,
                    'outcome' => $outcome,
                ], $sessionId, $request['id'] ?? null);
            } else {
                $this->auditService->record('submission_completed', 'Submission completed successfully.', [
                    'session_id' => $sessionId,
                    'request_id' => $request['id'] ?? null,
                    'outcome' => $outcome,
                ], 'info', $sessionId, $request['id'] ?? null);
            }

            return [
                'message' => 'Submission completed.',
                'next_action' => 'done',
                'session' => $this->sessionService->getById($sessionId),
                'applicant' => $applicant,
                'request_number' => $request['request_number'] ?? null,
                'request' => $this->requestService->findBySessionId($sessionId),
                'documents' => $attachedDocuments,
                'ocr_verification' => array_merge($ocrVerification, [
                    'verification_id' => $ocrRecord['id'] ?? null,
                    'record' => $ocrRecord,
                ]),
                'verification' => $verification,
            ];
        });
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function buildSubmissionSnapshot(string $sessionId): array
    {
        if ($sessionId === '') {
            throw new AppException('Session ID is required.', 422, 'SESSION_ID_REQUIRED');
        }

        $session = $this->sessionService->getById($sessionId);
        if ($session === null) {
            throw new AppException('Session not found.', 404, 'SESSION_NOT_FOUND');
        }

        $draft = $this->decodeDraft($session['draft_payload'] ?? []);
        $documents = $this->documentService->findSessionDocuments($sessionId);
        $indexedDocuments = $this->indexDocumentsByType($documents);

        return [
            'session' => $this->normalizeSessionForValidation($session, $draft),
            'service' => [
                'service_type' => $draft['service_type'] ?? 'individual',
                'service_label' => $draft['service_label'] ?? null,
            ],
            'language' => [
                'language' => $session['language_code'] ?? ($draft['language_code'] ?? ''),
            ],
            'state' => [
                'state' => $draft['state_name'] ?? ($draft['state_code'] ?? ''),
            ],
            'full_name' => [
                'full_name' => $draft['full_name'] ?? '',
            ],
            'identity_number' => [
                'identity_number' => $draft['identity_number_compact'] ?? ($draft['identity_number'] ?? ''),
                'identity_type' => $draft['identity_type'] ?? null,
            ],
            'company' => [
                'ppk_number' => $draft['company_ppk_number'] ?? '',
                'company_name' => $draft['company_name'] ?? '',
                'company_email' => $draft['company_email'] ?? '',
                'category' => $draft['category'] ?? ($draft['company_category'] ?? ''),
                'company_category' => $draft['category'] ?? ($draft['company_category'] ?? ''),
            ],
            'director' => [
                'full_name' => $draft['director_full_name'] ?? '',
                'identity_number' => $draft['director_identity_number_compact'] ?? ($draft['director_identity_number'] ?? ''),
                'identity_type' => $draft['director_identity_type'] ?? null,
            ],
            'reason' => [
                'company_cancellation_reason' => $draft['company_cancellation_reason'] ?? '',
            ],
            'mobile' => [
                'mobile' => $draft['mobile'] ?? '',
            ],
            'email' => [
                'email' => $draft['email'] ?? '',
            ],
            'documents' => [
                'front' => $indexedDocuments['IC_FRONT'] ?? null,
                'back' => $indexedDocuments['IC_BACK'] ?? null,
                'signature' => $indexedDocuments['SIGNATURE'] ?? null,
                'company_certificate' => $indexedDocuments['SSM_PPK_CERTIFICATE'] ?? null,
            ],
            'cims' => is_array($draft['cims'] ?? null) ? $draft['cims'] : [],
        ];
    }

    /**
     * @param array<string, mixed> $session
     * @param array<string, mixed> $draft
     * @return array<string, mixed>
     */
    private function normalizeSessionForValidation(array $session, array $draft): array
    {
        $session['draft_payload'] = $draft;

        return $session;
    }

    /**
     * @param array<int, array<string, mixed>> $documents
     * @return array<string, array<string, mixed>>
     */
    private function indexDocumentsByType(array $documents): array
    {
        $indexed = [];

        foreach ($documents as $document) {
            $typeCode = (string) ($document['document_type_code'] ?? '');
            if ($typeCode === '') {
                continue;
            }

            if (!isset($indexed[$typeCode]) || strtotime((string) ($document['created_at'] ?? '')) >= strtotime((string) ($indexed[$typeCode]['created_at'] ?? ''))) {
                $indexed[$typeCode] = $document;
            }
        }

        return $indexed;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function extractSessionId(array $payload): string
    {
        $sessionId = '';

        if (is_array($payload['session'] ?? null)) {
            $sessionId = (string) ($payload['session']['id'] ?? '');
        } elseif (isset($payload['session_id'])) {
            $sessionId = (string) $payload['session_id'];
        }

        return trim($sessionId);
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

    /**
     * @param array<string, mixed> $snapshot
     * @param array<int, array<string, mixed>> $documents
     * @return array<string, mixed>
     */
    private function verifyOcrDocuments(string $sessionId, array $snapshot, array $documents): array
    {
        $identityType = mb_strtoupper(trim((string) ($snapshot['identity_number']['identity_type'] ?? '')));
        $fullName = trim((string) ($snapshot['full_name']['full_name'] ?? ''));

        $frontDocument = $this->documentForType($documents, 'IC_FRONT');
        $backDocument = $this->documentForType($documents, 'IC_BACK');
        $passportDocument = $frontDocument;

        if ($identityType === 'PASSPORT') {
            if ($passportDocument === null) {
                throw new AppException('Passport document is missing.', 422, 'OCR_DOCUMENT_MISSING');
            }

            $rawResult = $this->ocrVerificationService->verifyPassport(
                $fullName,
                (string) ($snapshot['identity_number']['identity_number'] ?? ''),
                $passportDocument,
                [
                    'session_id' => $sessionId,
                    'identity_type' => $identityType,
                    'document_type' => 'passport',
                ]
            );

            return $this->normalizeOcrResult($rawResult, 'passport', [$passportDocument]);
        }

        if ($frontDocument === null) {
            throw new AppException('IC front document is missing.', 422, 'OCR_DOCUMENT_MISSING');
        }

        if ($backDocument === null) {
            throw new AppException('IC back document is missing.', 422, 'OCR_DOCUMENT_MISSING');
        }

        $rawResult = $this->ocrVerificationService->verifyMyKad(
            $fullName,
            (string) ($snapshot['identity_number']['identity_number'] ?? ''),
            $frontDocument,
            $backDocument,
            [
                'session_id' => $sessionId,
                'identity_type' => $identityType,
                'document_type' => 'mykad',
            ]
        );

        return $this->normalizeOcrResult($rawResult, 'mykad', [$frontDocument, $backDocument]);
    }

    /**
     * @param array<string, mixed> $snapshot
     * @param array<int, array<string, mixed>> $documents
     */
    private function verifyCompanyOcrDocuments(string $sessionId, array $snapshot, array $documents): array
    {
        $directorName = trim((string) ($snapshot['director']['full_name'] ?? ''));
        $identityType = mb_strtoupper(trim((string) ($snapshot['director']['identity_type'] ?? '')));
        $identityNumber = trim((string) ($snapshot['director']['identity_number'] ?? ''));

        $frontDocument = $this->documentForType($documents, 'IC_FRONT');
        $backDocument = $this->documentForType($documents, 'IC_BACK');

        if ($frontDocument === null) {
            throw new AppException('Director IC front document is missing.', 422, 'OCR_DOCUMENT_MISSING');
        }

        if ($backDocument === null) {
            throw new AppException('Director IC back document is missing.', 422, 'OCR_DOCUMENT_MISSING');
        }

        if ($identityType === 'PASSPORT') {
            $rawResult = $this->ocrVerificationService->verifyPassport(
                $directorName,
                $identityNumber,
                $frontDocument,
                [
                    'session_id' => $sessionId,
                    'identity_type' => $identityType,
                    'document_type' => 'company_passport',
                ]
            );

            return $this->normalizeOcrResult($rawResult, 'company_passport', [$frontDocument]);
        }

        $rawResult = $this->ocrVerificationService->verifyMyKad(
            $directorName,
            $identityNumber,
            $frontDocument,
            $backDocument,
            [
                'session_id' => $sessionId,
                'identity_type' => $identityType !== '' ? $identityType : 'MYKAD',
                'document_type' => 'company_mykad',
            ]
        );

        return $this->normalizeOcrResult($rawResult, 'company_mykad', [$frontDocument, $backDocument]);
    }

    private function validateCompanyReadiness(array $snapshot): \Cidb\Backend\Validators\ValidationResult
    {
        $result = \Cidb\Backend\Validators\ValidationResult::success();

        $serviceType = $this->resolveServiceType($snapshot);
        if ($serviceType !== 'company') {
            $result->addError('service.service_type', 'service_type_invalid', 'Company submission requires the company service type.');
        }

        $languageValidator = new \Cidb\Backend\Validators\LanguageValidator();
        $fullNameValidator = new \Cidb\Backend\Validators\FullNameValidator();
        $identityValidator = new \Cidb\Backend\Validators\IdentityValidator();
        $emailValidator = new \Cidb\Backend\Validators\EmailAddressValidator();

        $languageResult = $languageValidator->validate($snapshot['language'] ?? []);
        $directorNameResult = $fullNameValidator->validate($snapshot['director']['full_name'] ?? null);
        $directorIdentityResult = $identityValidator->validate([
            'identity_type' => $snapshot['director']['identity_type'] ?? null,
            'identity_number' => $snapshot['director']['identity_number'] ?? null,
        ]);
        $companyEmailResult = $emailValidator->validate($snapshot['company']['company_email'] ?? null);

        foreach ([$languageResult, $directorNameResult, $directorIdentityResult, $companyEmailResult] as $validationResult) {
            $result->merge($validationResult);
        }

        if (trim((string) ($snapshot['company']['ppk_number'] ?? '')) === '') {
            $result->addError('company.ppk_number', 'ppk_required', 'PPK / SSM number is required.');
        }

        if (trim((string) ($snapshot['company']['company_name'] ?? '')) === '') {
            $result->addError('company.company_name', 'company_name_required', 'Company name is required.');
        }

        if (trim((string) ($snapshot['company']['company_email'] ?? '')) === '') {
            $result->addError('company.company_email', 'company_email_required', 'Company email address is required.');
        }

        if (trim((string) ($snapshot['company']['company_category'] ?? '')) === '') {
            $result->addError('company.company_category', 'company_category_required', 'Company category is required.');
        }

        if (trim((string) ($snapshot['director']['full_name'] ?? '')) === '') {
            $result->addError('director.full_name', 'director_name_required', 'Director full name is required.');
        }

        if (trim((string) ($snapshot['director']['identity_number'] ?? '')) === '') {
            $result->addError('director.identity_number', 'director_identity_required', 'Director IC number is required.');
        }

        if (trim((string) ($snapshot['reason']['company_cancellation_reason'] ?? '')) === '') {
            $result->addError('reason.company_cancellation_reason', 'cancellation_reason_required', 'Reason for company cancellation is required.');
        }

        $documents = $snapshot['documents'] ?? [];
        if (!is_array($documents) || !isset($documents['front'], $documents['back'], $documents['company_certificate'])) {
            $result->addError('documents', 'documents_required', 'Required company documents are missing.');
        } else {
            if ($documents['front'] === null) {
                $result->addError('documents.front', 'front_document_required', 'Director IC front document is required.');
            }
            if ($documents['back'] === null) {
                $result->addError('documents.back', 'back_document_required', 'Director IC back document is required.');
            }
            if ($documents['company_certificate'] === null) {
                $result->addError('documents.company_certificate', 'company_certificate_required', 'SSM / PPK certificate is required.');
            }
        }

        return $result;
    }

    private function resolveServiceType(array $snapshot): string
    {
        $value = $snapshot['service']['service_type'] ?? 'individual';
        $normalized = mb_strtolower(trim((string) $value));

        return $normalized === 'company' ? 'company' : 'individual';
    }

    private function mapCompanyRequestStatus(string $resultStatus): string
    {
        return match ($resultStatus) {
            'approved' => 'approved',
            'rejected' => 'rejected',
            'manual_review' => 'manual_review',
            'pending' => 'under_review',
            default => 'failed',
        };
    }

    /**
     * @param array<int, array<string, mixed>> $documents
     */
    private function documentForType(array $documents, string $documentTypeCode): ?array
    {
        foreach ($documents as $document) {
            if ((string) ($document['document_type_code'] ?? '') === $documentTypeCode) {
                return $document;
            }
        }

        return null;
    }

    /**
     * @param array{
     *     success?: bool,
     *     status_code?: int,
     *     raw_response_text?: string,
     *     parsed_response?: array<string, mixed>|null,
     *     duration_ms?: int,
     *     error_message?: ?string
     * } $rawResult
     * @param array<int, array<string, mixed>> $documents
     * @return array<string, mixed>
     */
    private function normalizeOcrResult(array $rawResult, string $documentType, array $documents): array
    {
        $parsed = is_array($rawResult['parsed_response'] ?? null) ? $rawResult['parsed_response'] : [];
        $status = strtoupper(trim((string) ($parsed['status'] ?? '')));
        $verified = (bool) ($parsed['verified'] ?? false);
        $message = trim((string) ($parsed['message'] ?? ($rawResult['error_message'] ?? '')));
        if ($message === '') {
            $message = $status !== '' ? $status : 'OCR verification completed.';
        }

        $normalized = [
            'success' => (bool) ($rawResult['success'] ?? false),
            'status_code' => (int) ($rawResult['status_code'] ?? 0),
            'duration_ms' => (int) ($rawResult['duration_ms'] ?? 0),
            'document_type' => $documentType,
            'verified' => $verified,
            'status' => $status !== '' ? $status : ($verified ? 'VERIFIED' : 'OCR_FAILED'),
            'message' => $message,
            'should_continue' => $verified === true && $status === 'VERIFIED',
            'images_quality' => is_array($parsed['images_quality'] ?? null) ? $parsed['images_quality'] : [],
            'documents' => is_array($parsed['documents'] ?? null) ? $parsed['documents'] : [],
            'comparison' => is_array($parsed['comparison'] ?? null) ? $parsed['comparison'] : null,
            'ocr_average_confidence' => isset($parsed['ocr_average_confidence']) ? (float) $parsed['ocr_average_confidence'] : null,
            'extracted_document_number_masked' => isset($parsed['extracted_document_number_masked']) ? (string) $parsed['extracted_document_number_masked'] : null,
            'document_ids' => array_values(array_filter(array_map(
                static fn (array $document): ?string => isset($document['id']) ? (string) $document['id'] : null,
                $documents
            ))),
        ];

        if ($normalized['success'] !== true && ($normalized['status_code'] === 0 || $normalized['status_code'] >= 500)) {
            throw new AppException(
                $normalized['message'] !== '' ? $normalized['message'] : 'OCR verification service is temporarily unavailable.',
                503,
                'OCR_SERVICE_UNAVAILABLE',
                [
                    'ocr' => $normalized,
                ]
            );
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $ocrVerification
     * @param array<int, array<string, mixed>> $attachedDocuments
     * @return array<string, mixed>
     */
    private function persistOcrVerification(array $ocrVerification, array $attachedDocuments, ?string $requestId, string $sessionId): array
    {
        $primaryDocument = $this->documentForType($attachedDocuments, 'IC_FRONT') ?? ($attachedDocuments[0] ?? null);
        if (!is_array($primaryDocument) || $primaryDocument === [] || !isset($primaryDocument['id'])) {
            throw new AppException('OCR verification could not be attached to a document.', 500, 'OCR_DOCUMENT_ATTACHMENT_FAILED');
        }

        $status = (string) ($ocrVerification['status'] ?? '');
        $verificationStatus = $status === 'MANUAL_REVIEW'
            ? 'warning'
            : (($ocrVerification['verified'] ?? false) === true || $status === 'VERIFIED' ? 'passed' : 'failed');

        $score = isset($ocrVerification['ocr_average_confidence']) && is_numeric($ocrVerification['ocr_average_confidence'])
            ? round(((float) $ocrVerification['ocr_average_confidence']) * 100, 2)
            : null;

        $details = [
            'document_type' => $ocrVerification['document_type'] ?? null,
            'ocr_status' => $status !== '' ? $status : null,
            'verified' => (bool) ($ocrVerification['verified'] ?? false),
            'should_continue' => (bool) ($ocrVerification['should_continue'] ?? false),
            'message' => $ocrVerification['message'] ?? null,
            'images_quality' => $ocrVerification['images_quality'] ?? [],
            'documents' => $ocrVerification['documents'] ?? [],
            'comparison' => $ocrVerification['comparison'] ?? null,
            'ocr_average_confidence' => $ocrVerification['ocr_average_confidence'] ?? null,
            'extracted_document_number_masked' => $ocrVerification['extracted_document_number_masked'] ?? null,
            'document_ids' => $ocrVerification['document_ids'] ?? [],
            'request_id' => $requestId,
            'session_id' => $sessionId,
        ];

        $record = $this->documentVerificationRepository->insert([
            'uploaded_document_id' => (string) $primaryDocument['id'],
            'verification_type' => 'ocr_quality',
            'verifier' => 'ai',
            'status' => $verificationStatus,
            'score' => $score,
            'reason_code' => $status !== '' ? strtolower($status) : null,
            'reason_message' => $ocrVerification['message'] ?? null,
            'details' => JsonHelper::encode($details, true),
            'verified_at' => $this->now(),
            'created_at' => $this->now(),
        ]);

        $this->auditService->record('document_ocr_completed', 'OCR verification completed for uploaded documents.', [
            'session_id' => $sessionId,
            'request_id' => $requestId,
            'document_type' => $ocrVerification['document_type'] ?? null,
            'ocr_status' => $status,
            'should_continue' => (bool) ($ocrVerification['should_continue'] ?? false),
            'verification_id' => $record['id'] ?? null,
        ], $verificationStatus === 'warning' ? 'warning' : 'info', $sessionId, $requestId);

        return $record;
    }
}
