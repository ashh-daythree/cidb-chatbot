<?php

declare(strict_types=1);

namespace Cidb\Backend\Services;

use Cidb\Backend\Config\DatabaseConnection;
use Cidb\Backend\Validators\SubmissionReadinessValidator;
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

        $validation = $this->readinessValidator->validate($snapshot);
        if (!$validation->isValid()) {
            $this->auditService->recordValidationFailure('submission_validation_failed', $validation->errors(), $sessionId, null);
            throw new AppException('Submission validation failed.', 422, 'SUBMISSION_INVALID', $validation->errors());
        }

        return $this->transactional(function () use ($sessionId, $snapshot): array {
            $applicant = $this->applicantService->findBySessionId($sessionId);
            if ($applicant === null) {
                $applicant = $this->applicantService->finalizeFromSession($sessionId);
            }

            $request = $this->requestService->findBySessionId($sessionId);
            if ($request === null) {
                $request = $this->requestService->createFromSession($sessionId);
            }

            $documents = $this->documentService->findSessionDocuments($sessionId);
            $coverage = $this->documentService->resolveRequiredDocumentCoverage($documents);
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
                'session' => $this->sessionService->getById($sessionId),
                'applicant' => $applicant,
                'request' => $this->requestService->findBySessionId($sessionId),
                'documents' => $attachedDocuments,
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
            'documents' => [
                'front' => $indexedDocuments['IC_FRONT'] ?? null,
                'back' => $indexedDocuments['IC_BACK'] ?? null,
                'signature' => $indexedDocuments['SIGNATURE'] ?? null,
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
}
