<?php

declare(strict_types=1);

namespace Cidb\Backend\Validators;

use Cidb\Backend\Repositories\ReferenceFaqTopicRepository;

final class FaqTopicValidator extends AbstractValidator
{
    public function __construct(private readonly ReferenceFaqTopicRepository $topicRepository)
    {
    }

    public function validate(mixed $input): ValidationResult
    {
        $value = $this->normalizedString(is_array($input) ? ($input['topic_code'] ?? $input['topic'] ?? '') : $input);

        if ($value === '') {
            return $this->invalid('topic_code', 'topic_code_required', 'FAQ topic is required.');
        }

        $topic = $this->topicRepository->findActiveByCode(mb_strtoupper($value));
        if ($topic === null) {
            return $this->invalid('topic_code', 'topic_code_invalid', 'Invalid FAQ topic selected.', $value);
        }

        return ValidationResult::success([
            'topic_code' => $topic['topic_code'],
            'topic_label_en' => $topic['label_en'],
            'topic_label_ms' => $topic['label_ms'],
        ]);
    }
}
