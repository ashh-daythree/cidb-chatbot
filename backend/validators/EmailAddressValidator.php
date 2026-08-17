<?php

declare(strict_types=1);

namespace Cidb\Backend\Validators;

final class EmailAddressValidator extends AbstractValidator
{
    public function validate(mixed $input): ValidationResult
    {
        $value = is_array($input)
            ? ($input['email'] ?? $input['email_address'] ?? $input['contact_email'] ?? '')
            : $input;

        $normalized = mb_strtolower($this->normalizedString($value));

        if ($normalized === '') {
            return $this->invalid('email', 'email_required', 'Email address is required.');
        }

        if (filter_var($normalized, FILTER_VALIDATE_EMAIL) === false) {
            return $this->invalid('email', 'email_invalid', 'Email address is invalid.', $normalized);
        }

        return ValidationResult::success([
            'email' => $normalized,
        ]);
    }
}
