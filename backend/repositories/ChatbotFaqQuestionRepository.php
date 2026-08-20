<?php

declare(strict_types=1);

namespace Cidb\Backend\Repositories;

final class ChatbotFaqQuestionRepository extends BaseRepository
{
    protected function tableName(): string
    {
        return 'chatbot_faq_questions';
    }

    protected function primaryKey(): string
    {
        return 'id';
    }

    public function findTopQuestionsBySubtopic(string $subtopicCode, int $limit = 10, int $offset = 0): array
    {
        return $this->findAll([
            'subtopic_code' => $subtopicCode,
            'is_active' => true,
        ], $limit, $offset, 'sort_order', 'ASC');
    }

    public function countBySubtopic(string $subtopicCode): int
    {
        return $this->count([
            'subtopic_code' => $subtopicCode,
            'is_active' => true,
        ]);
    }
}
