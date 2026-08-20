<?php

declare(strict_types=1);

namespace Cidb\Backend\Models;

final class ReferenceFaqSubtopic extends AbstractModel
{
    public function tableName(): string
    {
        return 'reference_faq_subtopics';
    }

    public function primaryKey(): string
    {
        return 'subtopic_code';
    }

    public function fillable(): array
    {
        return [
            'topic_code',
            'label_en',
            'label_ms',
            'sort_order',
            'is_active',
            'created_at',
            'updated_at',
        ];
    }

    public function relationships(): array
    {
        return [
            'topic' => $this->belongsTo(ReferenceFaqTopic::class, 'topic_code', 'topic_code'),
            'questions' => $this->hasMany(ChatbotFaqQuestion::class, 'subtopic_code', 'subtopic_code'),
        ];
    }
}
