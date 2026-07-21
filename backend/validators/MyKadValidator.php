<?php

declare(strict_types=1);

namespace Cidb\Backend\Validators;

final class MyKadValidator extends AbstractValidator
{
    public function validate(mixed $input): ValidationResult
    {
        $value = $this->normalizedString(is_array($input) ? ($input['mykad'] ?? $input['identity_number'] ?? $input['ic'] ?? '') : $input);
        $compact = preg_replace('/[\s\-]/', '', $value) ?? '';

        if ($compact === '') {
            return $this->invalid('mykad', 'mykad_required', 'MyKad number is required.');
        }

        if (preg_match('/^\d{12}$/', $compact) !== 1) {
            return $this->invalid('mykad', 'mykad_invalid_format', 'MyKad must contain exactly 12 digits.');
        }

        $birthDate = substr($compact, 0, 6);
        $parsedDate = \DateTimeImmutable::createFromFormat('ymd', $birthDate);
        $dateErrors = \DateTimeImmutable::getLastErrors();
        $hasDateErrors = is_array($dateErrors) && (($dateErrors['warning_count'] ?? 0) > 0 || ($dateErrors['error_count'] ?? 0) > 0);

        if ($parsedDate === false || $hasDateErrors) {
            return $this->invalid('mykad', 'mykad_invalid_date', 'MyKad number contains an invalid birth date segment.', $value);
        }

        return ValidationResult::success([
            'identity_type' => 'MYKAD',
            'identity_number' => sprintf('%s-%s-%s', substr($compact, 0, 6), substr($compact, 6, 2), substr($compact, 8, 4)),
            'identity_number_compact' => $compact,
        ]);
    }
}

