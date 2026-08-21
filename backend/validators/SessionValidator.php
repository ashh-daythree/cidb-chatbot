<?php

declare(strict_types=1);

namespace Cidb\Backend\Validators;

final class SessionValidator extends AbstractValidator
{
    private const ALLOWED_STATUS = [
        'awaiting_language',
        'awaiting_service',
        'awaiting_state',
        'awaiting_name',
        'awaiting_identity',
        'awaiting_documents',
        'awaiting_company_ppk',
        'awaiting_company_name',
        'awaiting_company_email',
        'awaiting_company_contact',
        'awaiting_company_state',
        'awaiting_company_category',
        'awaiting_company_director_name',
        'awaiting_company_director_ic',
        'awaiting_company_reason',
        'submitted',
        'under_review',
        'completed',
        'abandoned',
        'expired',
        'failed',
    ];

    private const ALLOWED_STEPS = [
        'ask_lang',
        'ask_service',
        'ask_state',
        'ask_name',
        'ask_ic',
        'ask_mobile',
        'ask_email',
        'ask_ic_copy',
        'ask_company_ppk',
        'ask_company_name',
        'ask_company_email',
        'ask_company_contact',
        'ask_company_state',
        'ask_company_category',
        'ask_company_director_name',
        'ask_company_director_ic',
        'ask_company_reason',
        'done',
    ];

    /**
     * @var array<string, string|array<int, string>>
     */
    private array $stepTransitions = [
        'ask_lang' => 'ask_state',
        'ask_service' => ['ask_state', 'ask_company_ppk'],
        'ask_state' => 'ask_name',
        'ask_name' => 'ask_ic',
        'ask_ic' => 'ask_mobile',
        'ask_mobile' => 'ask_email',
        'ask_email' => 'ask_ic_copy',
        'ask_company_ppk' => 'ask_company_name',
        'ask_company_name' => 'ask_company_email',
        'ask_company_email' => 'ask_company_contact',
        'ask_company_contact' => ['ask_company_state', 'ask_company_category', 'ask_company_director_name'],
        'ask_company_state' => 'ask_company_director_name',
        'ask_company_category' => 'ask_company_director_name',
        'ask_company_director_name' => 'ask_company_director_ic',
        'ask_company_director_ic' => 'ask_company_reason',
        'ask_company_reason' => 'ask_ic_copy',
        'ask_ic_copy' => 'done',
        'done' => 'done',
    ];

    public function validate(mixed $input): ValidationResult
    {
        $session = is_array($input) ? $input : [];

        if ($session === []) {
            return $this->invalid('session', 'session_required', 'Session payload is required.');
        }

        $result = ValidationResult::success();

        if (!isset($session['id']) || !$this->isUuid($session['id'])) {
            $result->addError('session_id', 'session_id_invalid', 'Session ID must be a valid UUID.', $session['id'] ?? null);
        }

        $status = $this->normalizedString($session['status'] ?? '');
        if ($status === '') {
            $result->addError('status', 'status_required', 'Session status is required.');
        } elseif (!in_array($status, self::ALLOWED_STATUS, true)) {
            $result->addError('status', 'status_invalid', 'Session status is invalid.', $status, [
                'allowed' => self::ALLOWED_STATUS,
            ]);
        }

        $currentStep = $this->normalizedString($session['current_step'] ?? '');
        if ($currentStep === '') {
            $result->addError('current_step', 'current_step_required', 'Session step is required.');
        } elseif (!in_array($currentStep, self::ALLOWED_STEPS, true)) {
            $result->addError('current_step', 'current_step_invalid', 'Session step is invalid.', $currentStep, [
                'allowed' => self::ALLOWED_STEPS,
            ]);
        }

        if (array_key_exists('language_code', $session) && $session['language_code'] !== null && $session['language_code'] !== '') {
            $language = mb_strtolower($this->normalizedString($session['language_code']));
            if (!in_array($language, ['en', 'ms'], true)) {
                $result->addError('language_code', 'language_code_invalid', 'Language code is invalid.', $language, [
                    'allowed' => ['en', 'ms'],
                ]);
            }
        }

        if (array_key_exists('draft_payload', $session) && !is_array($session['draft_payload'])) {
            $result->addError('draft_payload', 'draft_payload_invalid', 'Draft payload must be an object/array.');
        }

        foreach (['started_at', 'last_activity_at', 'completed_at', 'expired_at', 'created_at', 'updated_at'] as $field) {
            if (array_key_exists($field, $session) && $session[$field] !== null && !$this->isDateTimeString($session[$field])) {
                $result->addError($field, $field . '_invalid', ucfirst(str_replace('_', ' ', $field)) . ' must be a valid date/time string.', $session[$field]);
            }
        }

        if ($currentStep !== '' && isset($this->stepTransitions[$currentStep])) {
            $nextStep = $this->stepTransitions[$currentStep];
            $result = $result->withData([
                'current_step' => $currentStep,
                'next_step' => is_array($nextStep) ? ($nextStep[0] ?? null) : $nextStep,
            ]);
        }

        return $result;
    }

    public function isTransitionAllowed(string $currentStep, string $nextStep): bool
    {
        if (!isset($this->stepTransitions[$currentStep])) {
            return false;
        }

        $allowed = $this->stepTransitions[$currentStep];
        if (is_array($allowed)) {
            return in_array($nextStep, $allowed, true);
        }

        return $allowed === $nextStep;
    }

    public function validateTransition(string $currentStep, string $nextStep): ValidationResult
    {
        if (!$this->isTransitionAllowed($currentStep, $nextStep)) {
            return $this->invalid(
                'current_step',
                'step_transition_invalid',
                'The requested session step transition is not allowed.',
                $currentStep . ' -> ' . $nextStep,
                ['allowed_next_step' => $this->stepTransitions[$currentStep] ?? null]
            );
        }

        return ValidationResult::success([
            'current_step' => $currentStep,
            'next_step' => $nextStep,
        ]);
    }

    /**
     * @return array<int, string>
     */
    public function allowedStatuses(): array
    {
        return self::ALLOWED_STATUS;
    }

    /**
     * @return array<int, string>
     */
    public function allowedSteps(): array
    {
        return self::ALLOWED_STEPS;
    }
}
