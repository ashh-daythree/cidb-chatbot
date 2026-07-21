<?php

declare(strict_types=1);

namespace Cidb\Backend\Validators;

final class LanguageValidator extends AbstractValidator
{
    /**
     * @var array<string, array{code: string, label: string, aliases: array<int, string>}>
     */
    private array $languages = [
        'en' => [
            'code' => 'en',
            'label' => 'English',
            'aliases' => ['english', 'en'],
        ],
        'ms' => [
            'code' => 'ms',
            'label' => 'Bahasa Malaysia',
            'aliases' => ['bahasa malaysia', 'bahasa melayu', 'malay', 'ms', 'bm'],
        ],
    ];

    public function validate(mixed $input): ValidationResult
    {
        $value = $this->normalizedString(is_array($input) ? ($input['language'] ?? $input['code'] ?? $input['language_code'] ?? '') : $input);

        if ($value === '') {
            return $this->invalid('language', 'language_required', 'Language is required.');
        }

        $needle = mb_strtolower($value);

        foreach ($this->languages as $language) {
            if (in_array($needle, $language['aliases'], true)) {
                return ValidationResult::success([
                    'language_code' => $language['code'],
                    'language_name' => $language['label'],
                ]);
            }
        }

        return $this->invalid(
            'language',
            'language_invalid',
            'Unsupported language. Only English or Bahasa Malaysia are allowed.',
            $value,
            ['allowed' => array_column($this->languages, 'label')]
        );
    }

    /**
     * @return array<string, array{code: string, label: string, aliases: array<int, string>}>
     */
    public function allowedLanguages(): array
    {
        return $this->languages;
    }
}

