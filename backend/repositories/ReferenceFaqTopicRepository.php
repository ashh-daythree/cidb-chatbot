<?php

declare(strict_types=1);

namespace Cidb\Backend\Repositories;

final class ReferenceFaqTopicRepository extends BaseRepository
{
    protected function tableName(): string
    {
        return 'reference_faq_topics';
    }

    protected function primaryKey(): string
    {
        return 'topic_code';
    }

    public function findActiveByCode(string $topicCode): ?array
    {
        return $this->findOneBy([
            'topic_code' => $topicCode,
            'is_active' => true,
        ]);
    }

    public function findActiveTopics(): array
    {
        return $this->findAll(['is_active' => true], 100, 0, 'sort_order', 'ASC');
    }
}
