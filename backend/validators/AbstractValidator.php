<?php

declare(strict_types=1);

namespace Cidb\Backend\Validators;

abstract class AbstractValidator implements ValidatorInterface
{
    protected function result(): ValidationResult
    {
        return ValidationResult::success();
    }

    protected function invalid(string $field, string $code, string $message, mixed $value = null, array $meta = []): ValidationResult
    {
        return ValidationResult::failure()->addError($field, $code, $message, $value, $meta);
    }

    protected function normalizedString(mixed $value): string
    {
        return trim((string) $value);
    }

    protected function collapseWhitespace(string $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', $value) ?? $value);
    }

    protected function isBlank(mixed $value): bool
    {
        return trim((string) $value) === '';
    }

    protected function isUuid(mixed $value): bool
    {
        return is_string($value) && preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[1-5][0-9a-fA-F]{3}-[89abAB][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $value) === 1;
    }

    protected function isDateTimeString(mixed $value): bool
    {
        if (!is_string($value) || trim($value) === '') {
            return false;
        }

        try {
            new \DateTimeImmutable($value);
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    protected function isJsonObject(mixed $value): bool
    {
        return is_array($value);
    }

    /**
     * @param array<int, string> $errors
     */
    protected function anyValueMatches(array $values, array $errors): bool
    {
        foreach ($values as $value) {
            if (in_array($value, $errors, true)) {
                return true;
            }
        }

        return false;
    }
}

