<?php

declare(strict_types=1);

namespace Cidb\Backend\Validators;

final class IdentityValidator extends AbstractValidator
{
    public function __construct(
        private readonly ?MyKadValidator $myKadValidator = null,
        private readonly ?PassportValidator $passportValidator = null
    ) {
    }

    public function validate(mixed $input): ValidationResult
    {
        $myKadValidator = $this->myKadValidator ?? new MyKadValidator();
        $passportValidator = $this->passportValidator ?? new PassportValidator();

        $type = null;
        $value = null;

        if (is_array($input)) {
            $type = isset($input['identity_type']) ? mb_strtoupper(trim((string) $input['identity_type'])) : null;
            $value = $input['identity_number'] ?? $input['number'] ?? $input['ic'] ?? null;
        } else {
            $value = $input;
        }

        if ($this->isBlank($value)) {
            return $this->invalid('identity_number', 'identity_required', 'IC or passport number is required.');
        }

        if ($type === 'MYKAD') {
            $result = $myKadValidator->validate($value);

            return $result->isValid()
                ? ValidationResult::success($result->data())
                : ValidationResult::failure($result->errors());
        }

        if ($type === 'PASSPORT') {
            $result = $passportValidator->validate($value);

            return $result->isValid()
                ? ValidationResult::success($result->data())
                : ValidationResult::failure($result->errors());
        }

        $myKadResult = $myKadValidator->validate($value);
        if ($myKadResult->isValid()) {
            return $myKadResult;
        }

        $passportResult = $passportValidator->validate($value);
        if ($passportResult->isValid()) {
            return $passportResult;
        }

        return ValidationResult::failure()
            ->addError('identity_number', 'identity_invalid', 'Invalid IC / Passport number.', $value, [
                'expected' => ['MYKAD', 'PASSPORT'],
            ]);
    }
}

