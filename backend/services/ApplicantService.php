<?php

declare(strict_types=1);

namespace Cidb\Backend\Services;

use Cidb\Backend\Config\DatabaseConnection;
use Cidb\Backend\Repositories\ChatbotApplicantRepository;
use Cidb\Backend\Repositories\ChatbotSessionRepository;
use Cidb\Backend\Repositories\ReferenceLanguageRepository;
use Cidb\Backend\Repositories\ReferenceMalaysianStateRepository;
use Cidb\Backend\Validators\FullNameValidator;
use Cidb\Backend\Validators\IdentityValidator;
use Cidb\Backend\Utils\SensitiveDataCrypto;
use Cidb\Backend\Utils\Exceptions\AppException;

final class ApplicantService extends AbstractService
{
    public function __construct(
        DatabaseConnection $connection,
        private readonly ChatbotApplicantRepository $applicantRepository,
        private readonly ChatbotSessionRepository $sessionRepository,
        private readonly ReferenceLanguageRepository $languageRepository,
        private readonly ReferenceMalaysianStateRepository $stateRepository,
        private readonly FullNameValidator $fullNameValidator,
        private readonly IdentityValidator $identityValidator,
        private readonly AuditService $auditService
    ) {
        parent::__construct($connection);
    }

    public function finalizeFromSession(string $sessionId): array
    {
        return $this->transactional(function () use ($sessionId): array {
            $session = $this->sessionRepository->findById($sessionId);
            if ($session === null) {
                throw new AppException('Session not found.', 404, 'SESSION_NOT_FOUND');
            }

            $draft = is_array($session['draft_payload'] ?? null) ? $session['draft_payload'] : json_decode((string) ($session['draft_payload'] ?? '{}'), true);
            $draft = is_array($draft) ? $draft : [];

            $fullNameResult = $this->fullNameValidator->validate($draft['full_name'] ?? null);
            $identityResult = $this->identityValidator->validate($draft['identity_number'] ?? null);

            if (!$fullNameResult->isValid() || !$identityResult->isValid()) {
                throw new AppException('Applicant data is incomplete.', 422, 'APPLICANT_INCOMPLETE', [
                    'full_name' => $fullNameResult->errors(),
                    'identity_number' => $identityResult->errors(),
                ]);
            }

            $fullName = (string) ($fullNameResult->data()['full_name'] ?? $draft['full_name']);
            $identityNumber = (string) ($identityResult->data()['identity_number_compact'] ?? $identityResult->data()['identity_number'] ?? $draft['identity_number']);
            $stateCode = (string) ($draft['state_code'] ?? '');
            $languageCode = (string) ($draft['language_code'] ?? '');

            if ($stateCode === '' || $this->stateRepository->findActiveByCode($stateCode) === null) {
                throw new AppException('Invalid Malaysian state.', 422, 'STATE_INVALID');
            }

            if ($languageCode === '' || $this->languageRepository->findActiveByCode($languageCode) === null) {
                throw new AppException('Invalid language.', 422, 'LANGUAGE_INVALID');
            }

            $payload = $this->buildEncryptedApplicantPayload(
                $sessionId,
                $fullName,
                (string) ($identityResult->data()['identity_type'] ?? 'PASSPORT'),
                $identityNumber,
                $stateCode,
                $languageCode,
                false
            );

            $existing = $this->applicantRepository->findBySessionId($sessionId);
            $applicant = $existing === null
                ? $this->applicantRepository->insert($payload)
                : $this->applicantRepository->update((string) $existing['id'], $payload) ?? $existing;

            $this->auditService->record('applicant_finalized', 'Applicant profile finalized from session draft.', [
                'session_id' => $sessionId,
                'applicant_id' => $applicant['id'] ?? null,
            ], 'info', $sessionId);

            return $applicant ?? [];
        });
    }

    /**
     * Builds the database payload without persisting plaintext sensitive values.
     * The session draft remains the short-lived source for downstream submission data.
     *
     * @return array<string, mixed>
     */
    private function buildEncryptedApplicantPayload(
        string $sessionId,
        string $fullName,
        string $identityType,
        string $identityNumber,
        string $stateCode,
        string $languageCode,
        bool $includeCreatedAt
    ): array {
        $payload = [
            'session_id' => $sessionId,
            'full_name_ciphertext' => SensitiveDataCrypto::encrypt($fullName),
            'full_name_hash' => hash('sha256', $fullName),
            'identity_type' => $identityType,
            'identity_number_ciphertext' => SensitiveDataCrypto::encrypt($identityNumber),
            'identity_number_hash' => hash('sha256', strtoupper($identityNumber)),
            'identity_number_last4' => substr($identityNumber, -4),
            'state_code' => $stateCode,
            'language_code' => $languageCode,
            'verification_status' => 'pending',
            'is_draft' => false,
            'updated_at' => $this->now(),
        ];

        if ($includeCreatedAt) {
            $payload['created_at'] = $this->now();
        }

        return $payload;
    }

    /**
     * Updates the stored applicant identity values from the current session draft.
     *
     * @param array<string, mixed> $identityData
     */
    public function updateIdentityFromSession(string $sessionId, string $fullName, array $identityData): array
    {
        return $this->transactional(function () use ($sessionId, $fullName, $identityData): array {
            $session = $this->sessionRepository->findById($sessionId);
            if ($session === null) {
                throw new AppException('Session not found.', 404, 'SESSION_NOT_FOUND');
            }

            $validatedName = $this->fullNameValidator->validate($fullName);
            if (!$validatedName->isValid()) {
                throw new AppException('Applicant name validation failed.', 422, 'FULL_NAME_INVALID', $validatedName->errors());
            }

            $validatedIdentity = $this->identityValidator->validate($identityData);
            if (!$validatedIdentity->isValid()) {
                throw new AppException('Applicant identity validation failed.', 422, 'IDENTITY_INVALID', $validatedIdentity->errors());
            }

            $sessionDraft = is_array($session['draft_payload'] ?? null)
                ? $session['draft_payload']
                : json_decode((string) ($session['draft_payload'] ?? '{}'), true);
            $sessionDraft = is_array($sessionDraft) ? $sessionDraft : [];

            $normalizedName = (string) ($validatedName->data()['full_name'] ?? $fullName);
            $normalizedIdentityNumber = (string) ($validatedIdentity->data()['identity_number_compact'] ?? $validatedIdentity->data()['identity_number'] ?? ($identityData['identity_number'] ?? ''));
            $normalizedIdentityType = (string) ($validatedIdentity->data()['identity_type'] ?? ($identityData['identity_type'] ?? ''));
            $existing = $this->applicantRepository->findBySessionId($sessionId);

            if ($existing === null) {
                $stateCode = (string) ($sessionDraft['state_code'] ?? '');
                $languageCode = (string) ($sessionDraft['language_code'] ?? '');

                if ($stateCode === '' || $this->stateRepository->findActiveByCode($stateCode) === null) {
                    throw new AppException('Invalid Malaysian state.', 422, 'STATE_INVALID');
                }

                if ($languageCode === '' || $this->languageRepository->findActiveByCode($languageCode) === null) {
                    throw new AppException('Invalid language.', 422, 'LANGUAGE_INVALID');
                }

                $existing = $this->applicantRepository->insert($this->buildEncryptedApplicantPayload(
                    $sessionId,
                    $normalizedName,
                    $normalizedIdentityType !== '' ? $normalizedIdentityType : 'PASSPORT',
                    $normalizedIdentityNumber,
                    $stateCode,
                    $languageCode,
                    true
                ));
            } else {
                $existing = $this->applicantRepository->update((string) $existing['id'], [
                    'full_name_ciphertext' => SensitiveDataCrypto::encrypt($normalizedName),
                    'full_name_hash' => hash('sha256', $normalizedName),
                    'identity_type' => $normalizedIdentityType !== '' ? $normalizedIdentityType : ($existing['identity_type'] ?? 'PASSPORT'),
                    'identity_number_ciphertext' => SensitiveDataCrypto::encrypt($normalizedIdentityNumber),
                    'identity_number_hash' => hash('sha256', strtoupper($normalizedIdentityNumber)),
                    'identity_number_last4' => substr($normalizedIdentityNumber, -4),
                    'updated_at' => $this->now(),
                ]) ?? $existing;
            }

            $this->auditService->record('applicant_identity_updated', 'Applicant identity details updated from session draft.', [
                'session_id' => $sessionId,
                'applicant_id' => $existing['id'] ?? null,
            ], 'info', $sessionId);

            return $existing;
        });
    }

    public function findBySessionId(string $sessionId): ?array
    {
        return $this->applicantRepository->findBySessionId($sessionId);
    }
}
