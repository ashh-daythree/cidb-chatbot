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

            $payload = [
                'session_id' => $sessionId,
                'full_name' => $fullName,
                'identity_type' => (string) ($identityResult->data()['identity_type'] ?? 'PASSPORT'),
                'identity_number' => $identityNumber,
                'identity_number_last4' => substr($identityNumber, -4),
                'state_code' => $stateCode,
                'language_code' => $languageCode,
                'verification_status' => 'pending',
                'is_draft' => false,
                'created_at' => $this->now(),
                'updated_at' => $this->now(),
            ];

            $existing = $this->applicantRepository->findBySessionId($sessionId);
            $applicant = $existing === null
                ? $this->applicantRepository->insert($payload)
                : $this->applicantRepository->update((string) $existing['id'], $payload);

            $this->auditService->record('applicant_finalized', 'Applicant profile finalized from session draft.', [
                'session_id' => $sessionId,
                'applicant_id' => $applicant['id'] ?? null,
            ], 'info', $sessionId);

            return $applicant ?? [];
        });
    }

    public function findBySessionId(string $sessionId): ?array
    {
        return $this->applicantRepository->findBySessionId($sessionId);
    }
}
