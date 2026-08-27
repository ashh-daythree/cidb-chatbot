<?php

declare(strict_types=1);

namespace Cidb\Backend\Controllers;

use Cidb\Backend\Repositories\ChatbotFaqQuestionRepository;
use Cidb\Backend\Repositories\ReferenceFaqSubtopicRepository;
use Cidb\Backend\Repositories\ReferenceFaqTopicRepository;

final class FaqController extends AbstractController
{
    private const QUESTIONS_PER_PAGE = 10;

    /**
     * Free-text search returns only the few most relevant questions rather than
     * a full page. A short list keeps the chat readable and pushes users toward
     * the "No" feedback path (assistance form) when none of the top hits fit,
     * instead of scrolling a long list of loosely-related answers.
     */
    private const SEARCH_RESULT_LIMIT = 3;

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

    /**
     * Words that carry no search meaning on their own (question framing,
     * articles, common connectors) so a full question like "what is PPK
     * renewal" reduces to the actual keywords: ["ppk", "renewal"].
     */
    private const SEARCH_STOPWORDS = [
        'what', 'is', 'are', 'the', 'a', 'an', 'how', 'do', 'does', 'to', 'for', 'of', 'in', 'on',
        'i', 'my', 'me', 'please', 'pls', 'can', 'could', 'you', 'tell', 'about', 'and', 'or', 'with', 'this', 'that', 'it',
        'apa', 'adalah', 'ialah', 'macam', 'mana', 'cara', 'untuk', 'yang', 'saya', 'boleh', 'tolong', 'mengenai', 'tentang', 'dan', 'atau', 'ini', 'itu',
    ];

    private const MAX_SEARCH_KEYWORDS = 8;

    public function search(array $request): array
    {
        $query = is_array($request['query'] ?? null) ? $request['query'] : [];
        $rawTerm = trim((string) ($query['q'] ?? ''));
        $offset = max(0, (int) ($query['offset'] ?? 0));

        if ($rawTerm === '') {
            return $this->success(['questions' => [], 'total' => 0, 'has_more' => false, 'suggestions' => []], 'FAQ search results retrieved.');
        }

        $topicCodes = $this->extractSearchTopicCodes($rawTerm);
        $keywords = $this->extractSearchKeywords($rawTerm, $topicCodes);

        $questions = $this->questionRepository->searchQuestionsByKeywords($keywords, self::SEARCH_RESULT_LIMIT, $offset, $topicCodes);
        $total = $this->questionRepository->countSearchQuestionsByKeywords($keywords, $topicCodes);

        $suggestions = [];
        if ($questions === [] && $offset === 0) {
            $suggestions = $this->questionRepository->suggestClosestQuestions($keywords, 3, $topicCodes);
        }

        return $this->success([
            'questions' => $questions,
            'total' => $total,
            'has_more' => ($offset + count($questions)) < $total,
            'suggestions' => $suggestions,
        ], 'FAQ search results retrieved.');
    }

    /**
     * @param string[] $topicCodes topic codes already recognised in the term
     * @return string[]
     */
    private function extractSearchKeywords(string $term, array $topicCodes = []): array
    {
        $normalized = strtolower((string) preg_replace('/[^a-z0-9\s]/i', ' ', $term));
        $tokens = array_values(array_filter(
            array_map('trim', explode(' ', $normalized)),
            static fn (string $token): bool => $token !== '' && strlen($token) >= 2 && !in_array($token, self::SEARCH_STOPWORDS, true)
        ));
        $tokens = array_slice(array_values(array_unique($tokens)), 0, self::MAX_SEARCH_KEYWORDS);

        // When the term names a topic (PPK/SPKK/STB) *and* carries other
        // keywords, drop the bare topic token: the topic filter already scopes
        // results to that document, so keeping "spkk" as a keyword would only
        // score every other document that name-drops SPKK in its answer text
        // (e.g. the STB "difference between PPK, SPKK and STB" entry).
        if ($topicCodes !== []) {
            $loweredCodes = array_map('strtolower', $topicCodes);
            $distinguishing = array_values(array_filter(
                $tokens,
                static fn (string $token): bool => !in_array($token, $loweredCodes, true)
            ));
            if ($distinguishing !== []) {
                $tokens = $distinguishing;
            }
        }

        if ($tokens === []) {
            $fallback = trim($normalized);

            return $fallback === '' ? [] : [$fallback];
        }

        return $tokens;
    }

    /**
     * @return string[]
     */
    private function extractSearchTopicCodes(string $term): array
    {
        $normalized = strtolower((string) preg_replace('/[^a-z0-9\s]/i', ' ', $term));
        $tokens = array_values(array_filter(
            array_map('trim', explode(' ', $normalized)),
            static fn (string $token): bool => $token !== ''
        ));

        $topicCodes = [];
        foreach (['ppk', 'spkk', 'stb'] as $topicCode) {
            if (in_array($topicCode, $tokens, true)) {
                $topicCodes[] = strtoupper($topicCode);
            }
        }

        return array_values(array_unique($topicCodes));
    }
}
