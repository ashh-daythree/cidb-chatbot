<?php

declare(strict_types=1);

namespace Cidb\Backend\Validators;

final class MobileNumberValidator extends AbstractValidator
{
    public function validate(mixed $input): ValidationResult
    {
        $value = is_array($input)
            ? ($input['mobile'] ?? $input['mobile_number'] ?? $input['phone'] ?? $input['phone_number'] ?? '')
            : $input;

        $normalized = preg_replace('/[\s\-\(\)]/', '', $this->normalizedString($value)) ?? '';

        if ($normalized === '') {
            return $this->invalid('mobile', 'mobile_required', 'Mobile number is required.');
        }

        if (preg_match('/^\+?\d{8,15}$/', $normalized) !== 1) {
            return $this->invalid(
                'mobile',
                'mobile_invalid',
                'Mobile number is invalid.',
                $normalized,
                [
                    'expected_format' => '+? followed by 8 to 15 digits',
                ]
            );
        }

        return ValidationResult::success([
            'mobile' => $normalized,
        ]);
    }
}
