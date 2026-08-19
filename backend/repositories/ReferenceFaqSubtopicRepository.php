<?php

declare(strict_types=1);

namespace Cidb\Backend\Repositories;

final class ReferenceFaqSubtopicRepository extends BaseRepository
{
    protected function tableName(): string
    {
        return 'reference_faq_subtopics';
    }

    protected function primaryKey(): string
    {
        return 'subtopic_code';
    }

    public function findActiveByCode(string $subtopicCode): ?array
    {
        return $this->findOneBy([
            'subtopic_code' => $subtopicCode,
            'is_active' => true,
        ]);
    }

    public function findActiveSubtopicsByTopic(string $topicCode): array
    {
        return $this->findAll([
            'topic_code' => $topicCode,
            'is_active' => true,
        ], 100, 0, 'sort_order', 'ASC');
    }
}
