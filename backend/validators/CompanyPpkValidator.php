<?php

declare(strict_types=1);

namespace Cidb\Backend\Validators;

final class CompanyPpkValidator extends AbstractValidator
{
    /**
     * Validate a company PPK / SSM registration number.
     *
     * Accepted formats:
     * - Modern 12-digit SSM number: YYYYXXNNNNNN where XX is 01-06
     * - Legacy registry numbers such as 1234567-A or SA0000001-D
     */
    public function validate(mixed $input, string $field = 'ppk_number'): ValidationResult
    {
        $value = is_array($input)
            ? ($input['ppk_number'] ?? $input['ssm_number'] ?? $input['registration_number'] ?? $input['company_number'] ?? $input['value'] ?? $input['text'] ?? $input['label'] ?? $input['name'] ?? '')
            : $input;

        $normalized = mb_strtoupper($this->normalizedString($value));

        if ($normalized === '') {
            return $this->invalid($field, 'ppk_required', 'PPK / SSM number is required.');
        }

        if ($this->isModernSsmNumber($normalized)) {
            return ValidationResult::success([
                'ppk_number' => $normalized,
                'ppk_number_format' => 'modern',
            ]);
        }

        if ($this->isLegacySsmNumber($normalized)) {
            return ValidationResult::success([
                'ppk_number' => $normalized,
                'ppk_number_format' => 'legacy',
            ]);
        }

        return $this->invalid($field, 'ppk_invalid', 'PPK / SSM number is invalid.', $normalized);
    }

    private function isModernSsmNumber(string $value): bool
    {
        if (preg_match('/^\d{12}$/', $value) !== 1) {
            return false;
        }

        $entityCode = substr($value, 4, 2);

        return in_array($entityCode, ['01', '02', '03', '04', '05', '06'], true);
    }

    private function isLegacySsmNumber(string $value): bool
    {
        return preg_match('/^(?:[A-Z]{0,3})\d{7}-[A-Z]$/', $value) === 1;
    }
}
