<?php

declare(strict_types=1);

namespace Cidb\Backend\Services;

use Cidb\Backend\Config\DatabaseConnection;
use Cidb\Backend\Repositories\ChatbotFaqQuestionRepository;
use Cidb\Backend\Repositories\ChatbotSessionRepository;
use Cidb\Backend\Repositories\ChatbotWorkflowRepository;
use Cidb\Backend\Repositories\ReferenceFaqSubtopicRepository;
use Cidb\Backend\Repositories\ReferenceLanguageRepository;
use Cidb\Backend\Utils\JsonHelper;
use Cidb\Backend\Validators\EmailAddressValidator;
use Cidb\Backend\Validators\FaqSubtopicValidator;
use Cidb\Backend\Validators\FaqTopicValidator;
use Cidb\Backend\Validators\CompanyPpkValidator;
use Cidb\Backend\Validators\FullNameValidator;
use Cidb\Backend\Validators\IdentityValidator;
use Cidb\Backend\Validators\LanguageValidator;
use Cidb\Backend\Validators\MalaysianStateValidator;
use Cidb\Backend\Validators\MobileNumberValidator;
use Cidb\Backend\Validators\SessionValidator;
use Cidb\Backend\Utils\Exceptions\AppException;

final class SessionService extends AbstractService
{
    public function __construct(
        DatabaseConnection $connection,
        private readonly ChatbotSessionRepository $sessionRepository,
        private readonly ChatbotWorkflowRepository $workflowRepository,
        private readonly ReferenceLanguageRepository $languageRepository,
        private readonly ReferenceFaqSubtopicRepository $faqSubtopicRepository,
        private readonly ChatbotFaqQuestionRepository $faqQuestionRepository,
        private readonly SessionValidator $sessionValidator,
        private readonly LanguageValidator $languageValidator,
        private readonly MalaysianStateValidator $stateValidator,
        private readonly FullNameValidator $fullNameValidator,
        private readonly CompanyPpkValidator $companyPpkValidator,
        private readonly IdentityValidator $identityValidator,
        private readonly MobileNumberValidator $mobileValidator,
        private readonly EmailAddressValidator $emailValidator,
        private readonly FaqTopicValidator $faqTopicValidator,
        private readonly FaqSubtopicValidator $faqSubtopicValidator,
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
            $draft['service_type'] = null;

            $updated = $this->sessionRepository->update($sessionId, [
                'language_code' => $languageCode,
                'status' => 'awaiting_service',
                'current_step' => 'ask_service',
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

    public function saveServiceSelection(string $sessionId, mixed $serviceInput): array
    {
        return $this->transactional(function () use ($sessionId, $serviceInput): array {
            $session = $this->requireSession($sessionId);

            $serviceType = $this->normalizeServiceType($serviceInput);
            if ($serviceType === null) {
                throw new AppException('Service selection failed.', 422, 'SERVICE_TYPE_INVALID', [
                    'service_type' => 'Please choose Individual Email ID Cancellation or Company Email ID Cancellation.',
                ]);
            }

            $nextStep = match ($serviceType) {
                'company' => 'ask_company_ppk',
                'faq' => 'ask_faq_topic',
                default => 'ask_state',
            };
            $this->assertStep($session, 'ask_service', $nextStep);

            $draft = $this->decodeDraft($session['draft_payload'] ?? []);
            $draft['service_type'] = $serviceType;
            $draft['service_label'] = match ($serviceType) {
                'company' => 'Company Email ID Cancellation',
                'faq' => 'FAQ',
                default => 'Individual Email ID Cancellation',
            };
            $draft['category'] = $serviceType;
            $draft['company_category'] = $serviceType;

            $nextStatus = match ($serviceType) {
                'company' => 'awaiting_company_ppk',
                'faq' => 'awaiting_faq_topic',
                default => 'awaiting_state',
            };

            $updated = $this->sessionRepository->update($sessionId, [
                'status' => $nextStatus,
                'current_step' => $nextStep,
                'draft_payload' => $this->encodeDraft($draft),
                'last_activity_at' => $this->now(),
                'updated_at' => $this->now(),
            ]);

            $this->auditService->record('session_service_saved', 'Service selection saved.', [
                'session_id' => $sessionId,
                'service_type' => $serviceType,
            ], 'info', $sessionId);

            return $updated ?? $session;
        });
    }

    public function saveState(string $sessionId, mixed $stateInput): array
    {
        return $this->transactional(function () use ($sessionId, $stateInput): array {
            $session = $this->requireSession($sessionId);
            $draft = $this->decodeDraft($session['draft_payload'] ?? []);
            $serviceType = $this->normalizeServiceType($draft['service_type'] ?? null) ?? 'individual';
            $nextStep = $serviceType === 'company' ? 'ask_company_ppk' : 'ask_name';

            $this->assertStep($session, 'ask_state', $nextStep);

            $validated = $this->stateValidator->validate($stateInput);
            if (!$validated->isValid()) {
                throw new AppException('State validation failed.', 422, 'STATE_INVALID', $validated->errors());
            }

            $draft['state_code'] = $validated->data()['state_code'] ?? null;
            $draft['state_name'] = $validated->data()['state_name'] ?? null;

            $nextStatus = $serviceType === 'company' ? 'awaiting_company_ppk' : 'awaiting_name';

            $updated = $this->sessionRepository->update($sessionId, [
                'status' => $nextStatus,
                'current_step' => $nextStep,
                'draft_payload' => $this->encodeDraft($draft),
                'last_activity_at' => $this->now(),
                'updated_at' => $this->now(),
            ]);

            $this->auditService->record('session_state_saved', 'State saved.', [
                'session_id' => $sessionId,
                'state_code' => $draft['state_code'] ?? null,
                'service_type' => $serviceType,
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
            $this->assertStep($session, 'ask_ic', 'ask_mobile');

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
                'current_step' => 'ask_mobile',
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

    /**
     * Updates the identity details after a verification failure.
     *
     * @param array<string, mixed> $identityInput
     */
    public function updateIdentityDetails(string $sessionId, array $identityInput): array
    {
        return $this->transactional(function () use ($sessionId, $identityInput): array {
            $session = $this->requireSession($sessionId);
            $draft = $this->decodeDraft($session['draft_payload'] ?? []);
            $serviceType = $this->normalizeServiceType($draft['service_type'] ?? null) ?? 'individual';

            $updatedDraft = $draft;

            if ($serviceType === 'company') {
                $currentIdentityType = (string) ($draft['director_identity_type'] ?? '');
                $directorNameValue = $identityInput['director_full_name'] ?? $identityInput['full_name'] ?? $identityInput['name'] ?? null;
                $directorIdentityValue = $identityInput['director_identity_number'] ?? $identityInput['identity_number'] ?? $identityInput['ic'] ?? $identityInput['passport'] ?? $identityInput['identity'] ?? null;

                $validatedName = $this->fullNameValidator->validate($directorNameValue);
                if (!$validatedName->isValid()) {
                    throw new AppException('Director name validation failed.', 422, 'DIRECTOR_NAME_INVALID', $validatedName->errors());
                }

                $validatedIdentity = $this->identityValidator->validate([
                    'identity_type' => $identityInput['identity_type'] ?? ($currentIdentityType !== '' ? $currentIdentityType : null),
                    'identity_number' => $directorIdentityValue,
                ]);
                if (!$validatedIdentity->isValid()) {
                    throw new AppException('Director IC validation failed.', 422, 'DIRECTOR_IDENTITY_INVALID', $validatedIdentity->errors());
                }

                $updatedDraft['director_full_name'] = $validatedName->data()['full_name'] ?? null;
                $updatedDraft['director_identity_type'] = $validatedIdentity->data()['identity_type'] ?? ($currentIdentityType !== '' ? $currentIdentityType : null);
                $updatedDraft['director_identity_number'] = $validatedIdentity->data()['identity_number'] ?? null;
                $updatedDraft['director_identity_number_compact'] = $validatedIdentity->data()['identity_number_compact'] ?? null;
            } else {
                $currentIdentityType = (string) ($draft['identity_type'] ?? '');
                $fullNameValue = $identityInput['full_name'] ?? $identityInput['name'] ?? null;
                $identityValue = $identityInput['identity_number'] ?? $identityInput['ic'] ?? $identityInput['passport'] ?? $identityInput['identity'] ?? null;

                $validatedName = $this->fullNameValidator->validate($fullNameValue);
                if (!$validatedName->isValid()) {
                    throw new AppException('Name validation failed.', 422, 'FULL_NAME_INVALID', $validatedName->errors());
                }

                $validatedIdentity = $this->identityValidator->validate([
                    'identity_type' => $identityInput['identity_type'] ?? ($currentIdentityType !== '' ? $currentIdentityType : null),
                    'identity_number' => $identityValue,
                ]);
                if (!$validatedIdentity->isValid()) {
                    throw new AppException('Identity validation failed.', 422, 'IDENTITY_INVALID', $validatedIdentity->errors());
                }

                $updatedDraft['full_name'] = $validatedName->data()['full_name'] ?? null;
                $updatedDraft['identity_type'] = $validatedIdentity->data()['identity_type'] ?? ($currentIdentityType !== '' ? $currentIdentityType : null);
                $updatedDraft['identity_number'] = $validatedIdentity->data()['identity_number'] ?? null;
                $updatedDraft['identity_number_compact'] = $validatedIdentity->data()['identity_number_compact'] ?? null;

                $this->applicantService->updateIdentityFromSession(
                    $sessionId,
                    (string) ($updatedDraft['full_name'] ?? ''),
                    [
                        'identity_type' => $updatedDraft['identity_type'] ?? null,
                        'identity_number' => $updatedDraft['identity_number'] ?? null,
                    ]
                );
            }

            $updated = $this->sessionRepository->update($sessionId, [
                'draft_payload' => $this->encodeDraft($updatedDraft),
                'last_activity_at' => $this->now(),
                'updated_at' => $this->now(),
            ]);

            $this->auditService->record('session_identity_details_updated', 'Identity details updated from failed verification flow.', [
                'session_id' => $sessionId,
                'service_type' => $serviceType,
            ], 'info', $sessionId);

            return $updated ?? $session;
        });
    }

    /**
     * Updates all retry-editable data before resubmission.
     *
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function updateRetryEditableData(string $sessionId, array $input): array
    {
        return $this->transactional(function () use ($sessionId, $input): array {
            $session = $this->requireSession($sessionId);
            $draft = $this->decodeDraft($session['draft_payload'] ?? []);
            $serviceType = $this->normalizeServiceType($draft['service_type'] ?? null) ?? 'individual';

            if ($serviceType === 'company') {
                $updatedDraft = $draft;

                $stateResult = $this->stateValidator->validate($input['state'] ?? $input['state_name'] ?? null);
                if (!$stateResult->isValid()) {
                    throw new AppException('Company state validation failed.', 422, 'COMPANY_STATE_INVALID', $stateResult->errors());
                }

                $ppkResult = $this->companyPpkValidator->validate($input['company_ppk_number'] ?? $input['ppk_number'] ?? $input['ssm_number'] ?? null, 'company.ppk_number');
                if (!$ppkResult->isValid()) {
                    throw new AppException('Company PPK/SSM validation failed.', 422, 'COMPANY_PPK_INVALID', $ppkResult->errors());
                }

                $companyName = $this->normalizedTextValue($input['company_name'] ?? $input['name'] ?? null);
                if ($companyName === '') {
                    throw new AppException('Company name validation failed.', 422, 'COMPANY_NAME_REQUIRED', [
                        'company_name' => 'This field is required.',
                    ]);
                }

                $emailResult = $this->emailValidator->validate($input['company_email'] ?? $input['email'] ?? $input['email_address'] ?? null);
                if (!$emailResult->isValid()) {
                    throw new AppException('Company email validation failed.', 422, 'COMPANY_EMAIL_INVALID', $emailResult->errors());
                }

                $contactResult = $this->mobileValidator->validate($input['company_contact_number'] ?? $input['contact_number'] ?? $input['mobile'] ?? $input['mobile_number'] ?? $input['phone'] ?? $input['phone_number'] ?? null);
                if (!$contactResult->isValid()) {
                    throw new AppException('Company contact number validation failed.', 422, 'COMPANY_CONTACT_INVALID', $contactResult->errors());
                }

                $directorNameResult = $this->fullNameValidator->validate($input['director_full_name'] ?? $input['full_name'] ?? $input['name'] ?? null);
                if (!$directorNameResult->isValid()) {
                    throw new AppException('Director name validation failed.', 422, 'DIRECTOR_NAME_INVALID', $directorNameResult->errors());
                }

                $currentIdentityType = (string) ($draft['director_identity_type'] ?? '');
                $directorIdentityResult = $this->identityValidator->validate([
                    'identity_type' => $input['director_identity_type'] ?? $input['identity_type'] ?? ($currentIdentityType !== '' ? $currentIdentityType : null),
                    'identity_number' => $input['director_identity_number'] ?? $input['identity_number'] ?? $input['ic'] ?? $input['passport'] ?? $input['identity'] ?? null,
                ]);
                if (!$directorIdentityResult->isValid()) {
                    throw new AppException('Director IC validation failed.', 422, 'DIRECTOR_IDENTITY_INVALID', $directorIdentityResult->errors());
                }

                $reason = $this->normalizedTextValue($input['company_cancellation_reason'] ?? $input['reason'] ?? $input['company_reason'] ?? null);
                if ($reason === '') {
                    throw new AppException('Cancellation reason validation failed.', 422, 'COMPANY_REASON_REQUIRED', [
                        'company_cancellation_reason' => 'This field is required.',
                    ]);
                }

                $updatedDraft['state_code'] = $stateResult->data()['state_code'] ?? null;
                $updatedDraft['state_name'] = $stateResult->data()['state_name'] ?? null;
                $updatedDraft['company_ppk_number'] = (string) ($ppkResult->data()['ppk_number'] ?? '');
                $updatedDraft['company_name'] = $companyName;
                $updatedDraft['company_email'] = (string) ($emailResult->data()['email'] ?? '');
                $updatedDraft['company_contact_number'] = (string) ($contactResult->data()['mobile'] ?? '');
                $updatedDraft['director_full_name'] = (string) ($directorNameResult->data()['full_name'] ?? '');
                $updatedDraft['director_identity_type'] = $directorIdentityResult->data()['identity_type'] ?? ($currentIdentityType !== '' ? $currentIdentityType : null);
                $updatedDraft['director_identity_number'] = $directorIdentityResult->data()['identity_number'] ?? null;
                $updatedDraft['director_identity_number_compact'] = $directorIdentityResult->data()['identity_number_compact'] ?? null;
                $updatedDraft['company_cancellation_reason'] = $reason;
                $updatedDraft['category'] = (string) ($updatedDraft['category'] ?? $updatedDraft['service_type'] ?? 'company');
                $updatedDraft['company_category'] = $updatedDraft['category'];

                $updated = $this->sessionRepository->update($sessionId, [
                    'draft_payload' => $this->encodeDraft($updatedDraft),
                    'last_activity_at' => $this->now(),
                    'updated_at' => $this->now(),
                ]);

                $this->auditService->record('session_retry_edit_saved', 'Company retry data updated.', [
                    'session_id' => $sessionId,
                ], 'info', $sessionId);

                return $updated ?? $session;
            }

            $updatedDraft = $draft;

            $stateResult = $this->stateValidator->validate($input['state'] ?? $input['state_name'] ?? null);
            if (!$stateResult->isValid()) {
                throw new AppException('State validation failed.', 422, 'STATE_INVALID', $stateResult->errors());
            }

            $nameResult = $this->fullNameValidator->validate($input['full_name'] ?? $input['name'] ?? null);
            if (!$nameResult->isValid()) {
                throw new AppException('Name validation failed.', 422, 'FULL_NAME_INVALID', $nameResult->errors());
            }

            $identityType = $input['identity_type'] ?? ($draft['identity_type'] ?? null);
            $identityResult = $this->identityValidator->validate([
                'identity_type' => $identityType,
                'identity_number' => $input['identity_number'] ?? $input['ic'] ?? $input['passport'] ?? $input['identity'] ?? null,
            ]);
            if (!$identityResult->isValid()) {
                throw new AppException('Identity validation failed.', 422, 'IDENTITY_INVALID', $identityResult->errors());
            }

            $mobileResult = $this->mobileValidator->validate($input['mobile'] ?? $input['mobile_number'] ?? $input['phone'] ?? $input['phone_number'] ?? null);
            if (!$mobileResult->isValid()) {
                throw new AppException('Mobile validation failed.', 422, 'MOBILE_INVALID', $mobileResult->errors());
            }

            $emailResult = $this->emailValidator->validate($input['email'] ?? $input['email_address'] ?? null);
            if (!$emailResult->isValid()) {
                throw new AppException('Email validation failed.', 422, 'EMAIL_INVALID', $emailResult->errors());
            }

            $updatedDraft['state_code'] = $stateResult->data()['state_code'] ?? null;
            $updatedDraft['state_name'] = $stateResult->data()['state_name'] ?? null;
            $updatedDraft['full_name'] = $nameResult->data()['full_name'] ?? null;
            $updatedDraft['identity_type'] = $identityResult->data()['identity_type'] ?? null;
            $updatedDraft['identity_number'] = $identityResult->data()['identity_number'] ?? null;
            $updatedDraft['identity_number_compact'] = $identityResult->data()['identity_number_compact'] ?? null;
            $updatedDraft['mobile'] = $mobileResult->data()['mobile'] ?? null;
            $updatedDraft['email'] = $emailResult->data()['email'] ?? null;

            $updated = $this->sessionRepository->update($sessionId, [
                'draft_payload' => $this->encodeDraft($updatedDraft),
                'last_activity_at' => $this->now(),
                'updated_at' => $this->now(),
            ]);

            $applicant = $this->applicantService->finalizeFromSession($sessionId);

            $this->auditService->record('session_retry_edit_saved', 'Individual retry data updated.', [
                'session_id' => $sessionId,
                'applicant_id' => $applicant['id'] ?? null,
            ], 'info', $sessionId);

            return [
                'session' => $updated ?? $session,
                'applicant' => $applicant,
            ];
        });
    }

    public function saveMobile(string $sessionId, mixed $mobileInput): array
    {
        return $this->transactional(function () use ($sessionId, $mobileInput): array {
            $session = $this->requireSession($sessionId);
            $this->assertStep($session, 'ask_mobile', 'ask_email');

            $validated = $this->mobileValidator->validate($mobileInput);
            if (!$validated->isValid()) {
                throw new AppException('Mobile validation failed.', 422, 'MOBILE_INVALID', $validated->errors());
            }

            $draft = $this->decodeDraft($session['draft_payload'] ?? []);
            $draft['mobile'] = $validated->data()['mobile'] ?? null;

            $updated = $this->sessionRepository->update($sessionId, [
                'current_step' => 'ask_email',
                'draft_payload' => $this->encodeDraft($draft),
                'last_activity_at' => $this->now(),
                'updated_at' => $this->now(),
            ]);

            $this->auditService->record('session_mobile_saved', 'Mobile number saved.', [
                'session_id' => $sessionId,
            ], 'info', $sessionId);

            return $updated ?? $session;
        });
    }

    public function saveEmail(string $sessionId, mixed $emailInput): array
    {
        return $this->transactional(function () use ($sessionId, $emailInput): array {
            $session = $this->requireSession($sessionId);
            $this->assertStep($session, 'ask_email', 'ask_ic_copy');

            $validated = $this->emailValidator->validate($emailInput);
            if (!$validated->isValid()) {
                throw new AppException('Email validation failed.', 422, 'EMAIL_INVALID', $validated->errors());
            }

            $draft = $this->decodeDraft($session['draft_payload'] ?? []);
            $draft['email'] = $validated->data()['email'] ?? null;

            $updated = $this->sessionRepository->update($sessionId, [
                'current_step' => 'ask_ic_copy',
                'draft_payload' => $this->encodeDraft($draft),
                'last_activity_at' => $this->now(),
                'updated_at' => $this->now(),
            ]);

            $this->auditService->record('session_email_saved', 'Email address saved.', [
                'session_id' => $sessionId,
            ], 'info', $sessionId);

            return $updated ?? $session;
        });
    }

    public function saveCompanyPpkNumber(string $sessionId, mixed $ppkInput): array
    {
        return $this->transactional(function () use ($sessionId, $ppkInput): array {
            $session = $this->requireSession($sessionId);
            $this->assertStep($session, 'ask_company_ppk', 'ask_company_name');

            $validated = $this->companyPpkValidator->validate($ppkInput, 'ppk_number');
            if (!$validated->isValid()) {
                throw new AppException('Please enter a valid PPK / SSM number.', 422, 'COMPANY_PPK_INVALID', $validated->errors());
            }

            $draft = $this->decodeDraft($session['draft_payload'] ?? []);
            $draft['company_ppk_number'] = (string) ($validated->data()['ppk_number'] ?? '');

            $updated = $this->sessionRepository->update($sessionId, [
                'status' => 'awaiting_company_name',
                'current_step' => 'ask_company_name',
                'draft_payload' => $this->encodeDraft($draft),
                'last_activity_at' => $this->now(),
                'updated_at' => $this->now(),
            ]);

            $this->auditService->record('session_company_ppk_saved', 'Company PPK/SSM number saved.', [
                'session_id' => $sessionId,
                'company_ppk_number' => $draft['company_ppk_number'],
                'ppk_number_format' => $validated->data()['ppk_number_format'] ?? null,
            ], 'info', $sessionId);

            return $updated ?? $session;
        });
    }

    public function saveCompanyName(string $sessionId, mixed $nameInput): array
    {
        return $this->saveCompanyDraftField($sessionId, 'ask_company_name', 'ask_company_email', 'awaiting_company_email', 'company_name', $nameInput, 'session_company_name_saved', 'Company name saved.', 'COMPANY_NAME_REQUIRED');
    }

    public function saveCompanyEmail(string $sessionId, mixed $emailInput): array
    {
        return $this->transactional(function () use ($sessionId, $emailInput): array {
            $session = $this->requireSession($sessionId);
            $this->assertStep($session, 'ask_company_email', 'ask_company_contact');

            $validated = $this->emailValidator->validate($emailInput);
            if (!$validated->isValid()) {
                throw new AppException('Company email validation failed.', 422, 'COMPANY_EMAIL_INVALID', $validated->errors());
            }

            $draft = $this->decodeDraft($session['draft_payload'] ?? []);
            $draft['company_email'] = $validated->data()['email'] ?? null;
            $draft['category'] = (string) ($draft['category'] ?? $draft['service_type'] ?? 'company');
            $draft['company_category'] = $draft['category'];

            $updated = $this->sessionRepository->update($sessionId, [
                'status' => 'awaiting_company_contact',
                'current_step' => 'ask_company_contact',
                'draft_payload' => $this->encodeDraft($draft),
                'last_activity_at' => $this->now(),
                'updated_at' => $this->now(),
            ]);

            $this->auditService->record('session_company_email_saved', 'Company email address saved.', [
                'session_id' => $sessionId,
            ], 'info', $sessionId);

            return $updated ?? $session;
        });
    }

    public function saveCompanyContactNumber(string $sessionId, mixed $mobileInput): array
    {
        return $this->transactional(function () use ($sessionId, $mobileInput): array {
            $session = $this->requireSession($sessionId);
            $this->assertStep($session, 'ask_company_contact', 'ask_company_state');

            $validated = $this->mobileValidator->validate($mobileInput);
            if (!$validated->isValid()) {
                throw new AppException('Company contact number validation failed.', 422, 'COMPANY_CONTACT_INVALID', $validated->errors());
            }

            $draft = $this->decodeDraft($session['draft_payload'] ?? []);
            $draft['company_contact_number'] = $validated->data()['mobile'] ?? null;

            $updated = $this->sessionRepository->update($sessionId, [
                'status' => 'awaiting_company_state',
                'current_step' => 'ask_company_state',
                'draft_payload' => $this->encodeDraft($draft),
                'last_activity_at' => $this->now(),
                'updated_at' => $this->now(),
            ]);

            $this->auditService->record('session_company_contact_saved', 'Company contact number saved.', [
                'session_id' => $sessionId,
            ], 'info', $sessionId);

            return $updated ?? $session;
        });
    }

    public function saveCompanyState(string $sessionId, mixed $stateInput): array
    {
        return $this->transactional(function () use ($sessionId, $stateInput): array {
            $session = $this->requireSession($sessionId);
            $this->assertStep($session, 'ask_company_state', 'ask_company_director_name');

            $validated = $this->stateValidator->validate($stateInput);
            if (!$validated->isValid()) {
                throw new AppException('Company state validation failed.', 422, 'COMPANY_STATE_INVALID', $validated->errors());
            }

            $draft = $this->decodeDraft($session['draft_payload'] ?? []);
            $draft['state_code'] = $validated->data()['state_code'] ?? null;
            $draft['state_name'] = $validated->data()['state_name'] ?? null;

            $updated = $this->sessionRepository->update($sessionId, [
                'status' => 'awaiting_company_director_name',
                'current_step' => 'ask_company_director_name',
                'draft_payload' => $this->encodeDraft($draft),
                'last_activity_at' => $this->now(),
                'updated_at' => $this->now(),
            ]);

            $this->auditService->record('session_company_state_saved', 'Company state saved.', [
                'session_id' => $sessionId,
                'state_code' => $draft['state_code'] ?? null,
            ], 'info', $sessionId);

            return $updated ?? $session;
        });
    }

    public function saveCompanyCategory(string $sessionId, mixed $categoryInput): array
    {
        return $this->saveCompanyDraftField($sessionId, 'ask_company_category', 'ask_company_director_name', 'awaiting_company_director_name', 'company_category', $categoryInput, 'session_company_category_saved', 'Company category saved.', 'COMPANY_CATEGORY_REQUIRED');
    }

    public function saveCompanyDirectorName(string $sessionId, mixed $nameInput): array
    {
        return $this->transactional(function () use ($sessionId, $nameInput): array {
            $session = $this->requireSession($sessionId);
            $this->assertStep($session, 'ask_company_director_name', 'ask_company_director_ic');

            $validated = $this->fullNameValidator->validate($nameInput);
            if (!$validated->isValid()) {
                throw new AppException('Director name validation failed.', 422, 'DIRECTOR_NAME_INVALID', $validated->errors());
            }

            $draft = $this->decodeDraft($session['draft_payload'] ?? []);
            $draft['director_full_name'] = $validated->data()['full_name'] ?? null;

            $updated = $this->sessionRepository->update($sessionId, [
                'status' => 'awaiting_company_director_ic',
                'current_step' => 'ask_company_director_ic',
                'draft_payload' => $this->encodeDraft($draft),
                'last_activity_at' => $this->now(),
                'updated_at' => $this->now(),
            ]);

            $this->auditService->record('session_company_director_name_saved', 'Company director name saved.', [
                'session_id' => $sessionId,
            ], 'info', $sessionId);

            return $updated ?? $session;
        });
    }

    public function saveCompanyDirectorIdentity(string $sessionId, mixed $identityInput): array
    {
        return $this->transactional(function () use ($sessionId, $identityInput): array {
            $session = $this->requireSession($sessionId);
            $this->assertStep($session, 'ask_company_director_ic', 'ask_company_reason');

            $validated = $this->identityValidator->validate($identityInput);
            if (!$validated->isValid()) {
                throw new AppException('Director IC validation failed.', 422, 'DIRECTOR_IDENTITY_INVALID', $validated->errors());
            }

            $draft = $this->decodeDraft($session['draft_payload'] ?? []);
            $draft['director_identity_type'] = $validated->data()['identity_type'] ?? null;
            $draft['director_identity_number'] = $validated->data()['identity_number'] ?? null;
            $draft['director_identity_number_compact'] = $validated->data()['identity_number_compact'] ?? null;

            $updated = $this->sessionRepository->update($sessionId, [
                'status' => 'awaiting_company_reason',
                'current_step' => 'ask_company_reason',
                'draft_payload' => $this->encodeDraft($draft),
                'last_activity_at' => $this->now(),
                'updated_at' => $this->now(),
            ]);

            $this->auditService->record('session_company_director_identity_saved', 'Company director identity saved.', [
                'session_id' => $sessionId,
            ], 'info', $sessionId);

            return $updated ?? $session;
        });
    }

    public function saveCompanyReason(string $sessionId, mixed $reasonInput): array
    {
        return $this->saveCompanyDraftField($sessionId, 'ask_company_reason', 'ask_ic_copy', 'awaiting_documents', 'company_cancellation_reason', $reasonInput, 'session_company_reason_saved', 'Company cancellation reason saved.', 'COMPANY_REASON_REQUIRED');
    }

    public function saveFaqTopicSelection(string $sessionId, mixed $topicInput): array
    {
        return $this->transactional(function () use ($sessionId, $topicInput): array {
            $session = $this->requireSession($sessionId);
            $this->assertStep($session, 'ask_faq_topic', 'ask_faq_subtopic');

            $validated = $this->faqTopicValidator->validate($topicInput);
            if (!$validated->isValid()) {
                throw new AppException('FAQ topic validation failed.', 422, 'FAQ_TOPIC_INVALID', $validated->errors());
            }

            $topicCode = (string) $validated->data()['topic_code'];

            $draft = $this->decodeDraft($session['draft_payload'] ?? []);
            $draft['faq_topic_code'] = $topicCode;
            unset($draft['faq_subtopic_code']);

            $updated = $this->sessionRepository->update($sessionId, [
                'status' => 'awaiting_faq_subtopic',
                'current_step' => 'ask_faq_subtopic',
                'draft_payload' => $this->encodeDraft($draft),
                'last_activity_at' => $this->now(),
                'updated_at' => $this->now(),
            ]);

            $this->auditService->record('session_faq_topic_saved', 'FAQ topic selected.', [
                'session_id' => $sessionId,
                'topic_code' => $topicCode,
            ], 'info', $sessionId);

            return [
                'session' => $updated ?? $session,
                'subtopics' => $this->faqSubtopicRepository->findActiveSubtopicsByTopic($topicCode),
            ];
        });
    }

    public function saveFaqSubtopicSelection(string $sessionId, mixed $subtopicInput): array
    {
        return $this->transactional(function () use ($sessionId, $subtopicInput): array {
            $session = $this->requireSession($sessionId);
            $this->assertStep($session, 'ask_faq_subtopic', 'ask_faq_subtopic');

            $validated = $this->faqSubtopicValidator->validate($subtopicInput);
            if (!$validated->isValid()) {
                throw new AppException('FAQ subtopic validation failed.', 422, 'FAQ_SUBTOPIC_INVALID', $validated->errors());
            }

            $subtopicCode = (string) $validated->data()['subtopic_code'];
            $topicCode = (string) $validated->data()['topic_code'];

            $draft = $this->decodeDraft($session['draft_payload'] ?? []);
            if (($draft['faq_topic_code'] ?? null) !== $topicCode) {
                throw new AppException('FAQ subtopic does not belong to the selected topic.', 422, 'FAQ_SUBTOPIC_TOPIC_MISMATCH');
            }
            $draft['faq_subtopic_code'] = $subtopicCode;

            $updated = $this->sessionRepository->update($sessionId, [
                'draft_payload' => $this->encodeDraft($draft),
                'last_activity_at' => $this->now(),
                'updated_at' => $this->now(),
            ]);

            $this->auditService->record('session_faq_subtopic_saved', 'FAQ subtopic selected.', [
                'session_id' => $sessionId,
                'subtopic_code' => $subtopicCode,
            ], 'info', $sessionId);

            $questions = $this->faqQuestionRepository->findTopQuestionsBySubtopic($subtopicCode, 10);
            $total = $this->faqQuestionRepository->countBySubtopic($subtopicCode);

            return [
                'session' => $updated ?? $session,
                'questions' => $questions,
                'total' => $total,
                'has_more' => count($questions) < $total,
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

    private function normalizeServiceType(mixed $value): ?string
    {
        $normalized = mb_strtolower(trim((string) $value));
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;
        $normalized = str_replace(['_', '-'], ' ', $normalized);

        if ($normalized === '') {
            return null;
        }

        if (in_array($normalized, ['1', 'individual', 'individual email id cancellation', 'pembatalan email id individu'], true)) {
            return 'individual';
        }

        if (in_array($normalized, ['2', 'company', 'company email id cancellation', 'pembatalan email id syarikat'], true)) {
            return 'company';
        }

        if (in_array($normalized, ['3', 'faq', 'frequently asked questions'], true)) {
            return 'faq';
        }

        return null;
    }

    private function saveCompanyDraftField(
        string $sessionId,
        string $expectedCurrentStep,
        string $nextStep,
        string $nextStatus,
        string $draftKey,
        mixed $valueInput,
        string $auditEvent,
        string $auditMessage,
        string $errorCode
    ): array {
        return $this->transactional(function () use ($sessionId, $expectedCurrentStep, $nextStep, $nextStatus, $draftKey, $valueInput, $auditEvent, $auditMessage, $errorCode): array {
            $session = $this->requireSession($sessionId);
            $this->assertStep($session, $expectedCurrentStep, $nextStep);

            $value = $this->normalizedTextValue($valueInput);
            if ($value === '') {
                throw new AppException('Company field validation failed.', 422, $errorCode, [
                    $draftKey => 'This field is required.',
                ]);
            }

            $draft = $this->decodeDraft($session['draft_payload'] ?? []);
            $draft[$draftKey] = $value;
            if ($draftKey === 'company_category') {
                $draft['category'] = $value;
            }

            $updated = $this->sessionRepository->update($sessionId, [
                'status' => $nextStatus,
                'current_step' => $nextStep,
                'draft_payload' => $this->encodeDraft($draft),
                'last_activity_at' => $this->now(),
                'updated_at' => $this->now(),
            ]);

            $this->auditService->record($auditEvent, $auditMessage, [
                'session_id' => $sessionId,
                $draftKey => $value,
            ], 'info', $sessionId);

            return $updated ?? $session;
        });
    }

    private function normalizedTextValue(mixed $value): string
    {
        if (is_array($value)) {
            $value = $value['value'] ?? $value['text'] ?? $value['label'] ?? $value['name'] ?? $value['company_name'] ?? $value['ppk_number'] ?? $value['ssm_number'] ?? '';
        }

        $text = trim((string) $value);

        return preg_replace('/\s+/', ' ', $text) ?? $text;
    }

    private function encodeDraft(array $draft): string
    {
        return JsonHelper::encode($draft, true);
    }
}
