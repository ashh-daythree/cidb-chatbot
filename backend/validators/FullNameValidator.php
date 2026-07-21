<?php

declare(strict_types=1);

namespace Cidb\Backend\Validators;

final class FullNameValidator extends AbstractValidator
{
    public function validate(mixed $input): ValidationResult
    {
        $value = $this->collapseWhitespace($this->normalizedString(is_array($input) ? ($input['full_name'] ?? $input['name'] ?? '') : $input));

        if ($value === '') {
            return $this->invalid('full_name', 'full_name_required', 'Full name is required.');
        }

        if (mb_strlen($value) < 2) {
            return $this->invalid('full_name', 'full_name_too_short', 'Full name must be at least 2 characters long.', $value);
        }

        if (mb_strlen($value) > 150) {
            return $this->invalid('full_name', 'full_name_too_long', 'Full name is too long.', $value);
        }

        if (preg_match('/[<>{}\\[\\]\\x00-\\x1F\\x7F]/u', $value) === 1) {
            return $this->invalid('full_name', 'full_name_invalid_characters', 'Full name contains invalid characters.', $value);
        }

        if (preg_match('/^[\p{L}][\p{L}\p{M}\p{Zs}\'\.\-]*$/u', $value) !== 1) {
            return $this->invalid(
                'full_name',
                'full_name_invalid_format',
                'Full name contains invalid characters. Use letters, spaces, apostrophes, hyphens, and periods only.',
                $value
            );
        }

        return ValidationResult::success([
            'full_name' => $value,
        ]);
    }
}

