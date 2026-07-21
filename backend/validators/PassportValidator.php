<?php

declare(strict_types=1);

namespace Cidb\Backend\Validators;

final class PassportValidator extends AbstractValidator
{
    public function validate(mixed $input): ValidationResult
    {
        $value = $this->normalizedString(is_array($input) ? ($input['passport'] ?? $input['identity_number'] ?? $input['ic'] ?? '') : $input);
        $normalized = mb_strtoupper($value);

        if ($normalized === '') {
            return $this->invalid('passport', 'passport_required', 'Passport number is required.');
        }

        if (preg_match('/^[A-Z0-9]{6,20}$/', $normalized) !== 1) {
            return $this->invalid(
                'passport',
                'passport_invalid_format',
                'Passport number must be 6 to 20 alphanumeric characters.',
                $value
            );
        }

        return ValidationResult::success([
            'identity_type' => 'PASSPORT',
            'identity_number' => $normalized,
            'identity_number_compact' => $normalized,
        ]);
    }
}

