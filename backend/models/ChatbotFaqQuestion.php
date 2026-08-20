<?php

declare(strict_types=1);

namespace Cidb\Backend\Models;

final class ChatbotFaqQuestion extends AbstractModel
{
    public function tableName(): string
    {
        return 'chatbot_faq_questions';
    }

    public function primaryKey(): string
    {
        return 'id';
    }

    public function fillable(): array
    {
        return [
            'subtopic_code',
            'question_en',
            'question_ms',
            'answer_en',
            'answer_ms',
            'sort_order',
            'is_active',
            'created_at',
            'updated_at',
        ];
    }

    public function relationships(): array
    {
        return [
            'subtopic' => $this->belongsTo(ReferenceFaqSubtopic::class, 'subtopic_code', 'subtopic_code'),
        ];
    }
}
