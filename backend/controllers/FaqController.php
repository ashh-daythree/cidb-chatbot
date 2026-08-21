<?php

declare(strict_types=1);

namespace Cidb\Backend\Controllers;

use Cidb\Backend\Repositories\ChatbotFaqQuestionRepository;
use Cidb\Backend\Repositories\ReferenceFaqSubtopicRepository;
use Cidb\Backend\Repositories\ReferenceFaqTopicRepository;

final class FaqController extends AbstractController
{
    private const QUESTIONS_PER_PAGE = 10;

    public function __construct(
        private readonly ReferenceFaqTopicRepository $topicRepository,
        private readonly ReferenceFaqSubtopicRepository $subtopicRepository,
        private readonly ChatbotFaqQuestionRepository $questionRepository
    ) {
    }

    public function topics(array $request): array
    {
        return $this->success([
            'topics' => $this->topicRepository->findActiveTopics(),
        ], 'FAQ topics retrieved.');
    }

    public function subtopics(array $request): array
    {
        $topicCode = $this->routeParam($request, 'topicCode');

        return $this->success([
            'subtopics' => $this->subtopicRepository->findActiveSubtopicsByTopic($topicCode),
        ], 'FAQ subtopics retrieved.');
    }

    public function questions(array $request): array
    {
        $subtopicCode = $this->routeParam($request, 'subtopicCode');
        $query = is_array($request['query'] ?? null) ? $request['query'] : [];
        $offset = max(0, (int) ($query['offset'] ?? 0));

        $questions = $this->questionRepository->findTopQuestionsBySubtopic($subtopicCode, self::QUESTIONS_PER_PAGE, $offset);
        $total = $this->questionRepository->countBySubtopic($subtopicCode);

        return $this->success([
            'questions' => $questions,
            'total' => $total,
            'has_more' => ($offset + count($questions)) < $total,
        ], 'FAQ questions retrieved.');
    }
}
