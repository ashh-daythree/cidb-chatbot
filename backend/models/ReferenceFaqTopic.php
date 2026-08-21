<?php

declare(strict_types=1);

namespace Cidb\Backend\Models;

final class ReferenceFaqTopic extends AbstractModel
{
    public function tableName(): string
    {
        return 'reference_faq_topics';
    }

    public function primaryKey(): string
    {
        return 'topic_code';
    }

    public function fillable(): array
    {
        return [
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
            'subtopics' => $this->hasMany(ReferenceFaqSubtopic::class, 'topic_code', 'topic_code'),
        ];
    }
}
