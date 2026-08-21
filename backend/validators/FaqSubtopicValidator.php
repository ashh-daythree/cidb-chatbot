<?php

declare(strict_types=1);

namespace Cidb\Backend\Validators;

use Cidb\Backend\Repositories\ReferenceFaqSubtopicRepository;

final class FaqSubtopicValidator extends AbstractValidator
{
    public function __construct(private readonly ReferenceFaqSubtopicRepository $subtopicRepository)
    {
    }

    public function validate(mixed $input): ValidationResult
    {
        $value = $this->normalizedString(is_array($input) ? ($input['subtopic_code'] ?? $input['subtopic'] ?? '') : $input);

        if ($value === '') {
            return $this->invalid('subtopic_code', 'subtopic_code_required', 'FAQ subtopic is required.');
        }

        $subtopic = $this->subtopicRepository->findActiveByCode(mb_strtoupper($value));
        if ($subtopic === null) {
            return $this->invalid('subtopic_code', 'subtopic_code_invalid', 'Invalid FAQ subtopic selected.', $value);
        }

        return ValidationResult::success([
            'subtopic_code' => $subtopic['subtopic_code'],
            'topic_code' => $subtopic['topic_code'],
            'subtopic_label_en' => $subtopic['label_en'],
            'subtopic_label_ms' => $subtopic['label_ms'],
        ]);
    }
}
