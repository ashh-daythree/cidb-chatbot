<?php

declare(strict_types=1);

namespace Cidb\Backend\Services;

use Cidb\Backend\Config\DatabaseConnection;
use Cidb\Backend\Repositories\ChatbotSessionRepository;
use Cidb\Backend\Repositories\ChatbotWorkflowRepository;
use Cidb\Backend\Repositories\ReferenceLanguageRepository;
use Cidb\Backend\Utils\JsonHelper;
use Cidb\Backend\Validators\FullNameValidator;
use Cidb\Backend\Validators\IdentityValidator;
use Cidb\Backend\Validators\LanguageValidator;
use Cidb\Backend\Validators\MalaysianStateValidator;
use Cidb\Backend\Validators\SessionValidator;
use Cidb\Backend\Utils\Exceptions\AppException;

final class SessionService extends AbstractService
{
    public function __construct(
        DatabaseConnection $connection,
        private readonly ChatbotSessionRepository $sessionRepository,
        private readonly ChatbotWorkflowRepository $workflowRepository,
        private readonly ReferenceLanguageRepository $languageRepository,
        private readonly SessionValidator $sessionValidator,
        private readonly LanguageValidator $languageValidator,
        private readonly MalaysianStateValidator $stateValidator,
        private readonly FullNameValidator $fullNameValidator,
        private readonly IdentityValidator $identityValidator,
        private readonly ApplicantService $applicantService,
        private readonly StatusService $statusService,
        private readonly AuditService $auditService
    ) {
        parent::__construct($connection);
    }

    public function start(string $workflowCode): array
    {
        return $this->transactional(function () use ($workflowCode): array {
            $workflow = $this->workflowRepository->findActiveByCode($workflowCode);
            if ($workflow === null) {
                throw new AppException('Workflow is not available.', 422, 'WORKFLOW_INVALID');
            }

            $session = $this->sessionRepository->insert([
                'workflow_id' => $workflow['id'],
                'language_code' => null,
                'status' => 'awaiting_language',
                'current_step' => 'ask_lang',
                'draft_payload' => $this->encodeDraft([]),
                'started_at' => $this->now(),
                'last_activity_at' => $this->now(),
                'completed_at' => null,
                'expired_at' => null,
                'created_at' => $this->now(),
                'updated_at' => $this->now(),
            ]);

            $this->auditService->record('session_started', 'Chatbot session started.', [
                'session_id' => $session['id'] ?? null,
                'workflow_code' => $workflowCode,
            ], 'info', $session['id'] ?? null);

            return $session;
        });
    }

    public function getById(string $sessionId): ?array
    {
        return $this->sessionRepository->findById($sessionId);
    }

    public function saveLanguage(string $sessionId, mixed $languageInput): array
    {
        return $this->transactional(function () use ($sessionId, $languageInput): array {
            $session = $this->requireSession($sessionId);
            $this->assertStep($session, 'ask_lang', 'ask_state');

            $validated = $this->languageValidator->validate($languageInput);
            if (!$validated->isValid()) {
                throw new AppException('Language validation failed.', 422, 'LANGUAGE_INVALID', $validated->errors());
            }

            $languageCode = (string) ($validated->data()['language_code'] ?? 'en');
            if ($this->languageRepository->findActiveByCode($languageCode) === null) {
                throw new AppException('Language is not available.', 422, 'LANGUAGE_INVALID');
            }

            $draft = $this->decodeDraft($session['draft_payload'] ?? []);
            $draft['language_code'] = $languageCode;
            $draft['language_name'] = $validated->data()['language_name'] ?? null;

            $updated = $this->sessionRepository->update($sessionId, [
                'language_code' => $languageCode,
                'status' => 'awaiting_state',
                'current_step' => 'ask_state',
                'draft_payload' => $this->encodeDraft($draft),
                'last_activity_at' => $this->now(),
                'updated_at' => $this->now(),
            ]);

            $this->auditService->record('session_language_saved', 'Language saved.', [
                'session_id' => $sessionId,
                'language_code' => $languageCode,
            ], 'info', $sessionId);

            return $updated ?? $session;
        });
    }

    public function saveState(string $sessionId, mixed $stateInput): array
    {
        return $this->transactional(function () use ($sessionId, $stateInput): array {
            $session = $this->requireSession($sessionId);
            $this->assertStep($session, 'ask_state', 'ask_name');

            $validated = $this->stateValidator->validate($stateInput);
            if (!$validated->isValid()) {
                throw new AppException('State validation failed.', 422, 'STATE_INVALID', $validated->errors());
            }

            $draft = $this->decodeDraft($session['draft_payload'] ?? []);
            $draft['state_code'] = $validated->data()['state_code'] ?? null;
            $draft['state_name'] = $validated->data()['state_name'] ?? null;

            $updated = $this->sessionRepository->update($sessionId, [
                'status' => 'awaiting_name',
                'current_step' => 'ask_name',
                'draft_payload' => $this->encodeDraft($draft),
                'last_activity_at' => $this->now(),
                'updated_at' => $this->now(),
            ]);

            $this->auditService->record('session_state_saved', 'State saved.', [
                'session_id' => $sessionId,
                'state_code' => $draft['state_code'] ?? null,
            ], 'info', $sessionId);

            return $updated ?? $session;
        });
    }

    public function saveName(string $sessionId, mixed $nameInput): array
    {
        return $this->transactional(function () use ($sessionId, $nameInput): array {
            $session = $this->requireSession($sessionId);
            $this->assertStep($session, 'ask_name', 'ask_ic');

            $validated = $this->fullNameValidator->validate($nameInput);
            if (!$validated->isValid()) {
                throw new AppException('Name validation failed.', 422, 'FULL_NAME_INVALID', $validated->errors());
            }

            $draft = $this->decodeDraft($session['draft_payload'] ?? []);
            $draft['full_name'] = $validated->data()['full_name'] ?? null;

            $updated = $this->sessionRepository->update($sessionId, [
                'status' => 'awaiting_identity',
                'current_step' => 'ask_ic',
                'draft_payload' => $this->encodeDraft($draft),
                'last_activity_at' => $this->now(),
                'updated_at' => $this->now(),
            ]);

            $this->auditService->record('session_name_saved', 'Name saved.', [
                'session_id' => $sessionId,
            ], 'info', $sessionId);

            return $updated ?? $session;
        });
    }

    public function saveIdentity(string $sessionId, mixed $identityInput): array
    {
        return $this->transactional(function () use ($sessionId, $identityInput): array {
            $session = $this->requireSession($sessionId);
            $this->assertStep($session, 'ask_ic', 'ask_ic_copy');

            $validated = $this->identityValidator->validate($identityInput);
            if (!$validated->isValid()) {
                throw new AppException('Identity validation failed.', 422, 'IDENTITY_INVALID', $validated->errors());
            }

            $draft = $this->decodeDraft($session['draft_payload'] ?? []);
            $draft['identity_type'] = $validated->data()['identity_type'] ?? null;
            $draft['identity_number'] = $validated->data()['identity_number'] ?? null;
            $draft['identity_number_compact'] = $validated->data()['identity_number_compact'] ?? null;

            $updated = $this->sessionRepository->update($sessionId, [
                'status' => 'awaiting_documents',
                'current_step' => 'ask_ic_copy',
                'draft_payload' => $this->encodeDraft($draft),
                'last_activity_at' => $this->now(),
                'updated_at' => $this->now(),
            ]);

            $applicant = $this->applicantService->finalizeFromSession($sessionId);

            $this->auditService->record('session_identity_saved', 'Identity saved and applicant finalized.', [
                'session_id' => $sessionId,
                'applicant_id' => $applicant['id'] ?? null,
            ], 'info', $sessionId);

            return [
                'session' => $updated ?? $session,
                'applicant' => $applicant,
            ];
        });
    }

    public function updateDraft(string $sessionId, array $patch): array
    {
        return $this->transactional(function () use ($sessionId, $patch): array {
            $session = $this->requireSession($sessionId);
            $draft = $this->decodeDraft($session['draft_payload'] ?? []);
            $draft = array_replace($draft, $patch);

            return $this->sessionRepository->update($sessionId, [
                'draft_payload' => $this->encodeDraft($draft),
                'last_activity_at' => $this->now(),
                'updated_at' => $this->now(),
            ]) ?? $session;
        });
    }

    public function markSubmitted(string $sessionId): array
    {
        return $this->statusService->transitionSession($sessionId, 'submitted', null, [], 'submission', 'Submission completed.');
    }

    public function markCompleted(string $sessionId): array
    {
        return $this->statusService->transitionSession($sessionId, 'completed', null, [], 'workflow_completed', 'Workflow completed.');
    }

    private function requireSession(string $sessionId): array
    {
        $session = $this->sessionRepository->findById($sessionId);
        if ($session === null) {
            throw new AppException('Session not found.', 404, 'SESSION_NOT_FOUND');
        }

        return $session;
    }

    private function assertStep(array $session, string $expectedStep, string $nextStep): void
    {
        $currentStep = (string) ($session['current_step'] ?? '');
        if (!$this->sessionValidator->isTransitionAllowed($currentStep, $nextStep)) {
            throw new AppException('Session step transition is not allowed.', 422, 'STEP_TRANSITION_INVALID', [
                'current_step' => $currentStep,
                'expected_next_step' => $nextStep,
            ]);
        }
    }

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

    private function encodeDraft(array $draft): string
    {
        return JsonHelper::encode($draft, true);
    }
}
