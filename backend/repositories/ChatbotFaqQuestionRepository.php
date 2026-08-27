<?php

declare(strict_types=1);

namespace Cidb\Backend\Repositories;

use PDO;

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

    /**
     * Ranked keyword search: a row scores one point per keyword that appears in
     * any of the bilingual question/answer columns, ordered by score descending.
     * This lets a full question like "what is PPK renewal" (tokenized to
     * ["ppk", "renewal"] by the caller) match rows containing either or both
     * keywords, ranking rows that hit more keywords higher.
     *
     * @param string[] $keywords
     * @param string[] $topicCodes
     */
    public function searchQuestionsByKeywords(array $keywords, int $limit = 10, int $offset = 0, array $topicCodes = []): array
    {
        if ($keywords === []) {
            return [];
        }

        [$fromClause, $scoreExpression, $whereExpression, $params] = $this->buildKeywordMatchSql($keywords, $topicCodes);

        $sql = sprintf(
            'SELECT q.*, (%s) AS match_score FROM %s WHERE q.is_active = true AND (%s) ORDER BY match_score DESC, q.sort_order ASC LIMIT :limit OFFSET :offset',
            $scoreExpression,
            $fromClause,
            $whereExpression
        );

        $statement = $this->connection->pdo()->prepare($sql);
        $this->bindValues($statement, $params);
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param string[] $keywords
     * @param string[] $topicCodes
     */
    public function countSearchQuestionsByKeywords(array $keywords, array $topicCodes = []): int
    {
        if ($keywords === []) {
            return 0;
        }

        [$fromClause, , $whereExpression, $params] = $this->buildKeywordMatchSql($keywords, $topicCodes);

        $sql = sprintf(
            'SELECT COUNT(*) AS aggregate FROM %s WHERE q.is_active = true AND (%s)',
            $fromClause,
            $whereExpression
        );

        $statement = $this->connection->pdo()->prepare($sql);
        $this->bindValues($statement, $params);
        $statement->execute();
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return isset($row['aggregate']) ? (int) $row['aggregate'] : 0;
    }

    /**
     * Typo-tolerant fallback used only when a keyword search returns zero rows.
     * Scores every active question by the fraction of query keywords that have
     * a close (Levenshtein-distance) match among the question's own words, so
     * a misspelling like "renewall" still surfaces "Renewal Procedure".
     *
     * @param string[] $keywords
     * @param string[] $topicCodes
     */
    public function suggestClosestQuestions(array $keywords, int $limit = 3, array $topicCodes = []): array
    {
        if ($keywords === []) {
            return [];
        }

        $candidates = $this->findAll(['is_active' => true], 500, 0, 'sort_order', 'ASC');
        $scored = [];

        foreach ($candidates as $row) {
            if ($topicCodes !== [] && !$this->rowMatchesTopicCodes($row, $topicCodes)) {
                continue;
            }

            $candidateWords = $this->extractWords(($row['question_en'] ?? '') . ' ' . ($row['question_ms'] ?? ''));
            if ($candidateWords === []) {
                continue;
            }

            $hits = 0;
            foreach ($keywords as $keyword) {
                $bestDistance = null;
                foreach ($candidateWords as $word) {
                    $distance = levenshtein($keyword, $word);
                    if ($bestDistance === null || $distance < $bestDistance) {
                        $bestDistance = $distance;
                    }
                }
                $threshold = max(1, (int) floor(strlen($keyword) * 0.34));
                if ($bestDistance !== null && $bestDistance <= $threshold) {
                    $hits++;
                }
            }

            if ($hits > 0) {
                $scored[] = ['row' => $row, 'score' => $hits / count($keywords)];
            }
        }

        usort($scored, static fn (array $a, array $b): int => $b['score'] <=> $a['score']);

        return array_map(static fn (array $entry): array => $entry['row'], array_slice($scored, 0, $limit));
    }

    /**
     * @param string[] $keywords
     * @param string[] $topicCodes
     * @return array{0: string, 1: string, 2: string, 3: array<string, string>}
     */
    private function buildKeywordMatchSql(array $keywords, array $topicCodes = []): array
    {
        $scoreParts = [];
        $whereParts = [];
        $params = [];
        $fromClause = 'chatbot_faq_questions q';

        foreach (array_values($keywords) as $index => $keyword) {
            $placeholder = 'kw' . $index;
            $params[$placeholder] = '%' . $keyword . '%';
            $matchExpr = sprintf(
                '(q.question_en ILIKE :%1$s OR q.question_ms ILIKE :%1$s OR q.answer_en ILIKE :%1$s OR q.answer_ms ILIKE :%1$s)',
                $placeholder
            );
            $scoreParts[] = sprintf('(%s)::int', $matchExpr);
            $whereParts[] = $matchExpr;
        }

        $topicCodes = array_values(array_unique(array_filter(array_map(static fn (string $code): string => strtoupper(trim($code)), $topicCodes))));
        if ($topicCodes !== []) {
            $fromClause .= ' INNER JOIN reference_faq_subtopics s ON s.subtopic_code = q.subtopic_code';
            $topicPlaceholders = [];
            foreach ($topicCodes as $index => $topicCode) {
                $placeholder = 'topic' . $index;
                $topicPlaceholders[] = ':' . $placeholder;
                $params[$placeholder] = $topicCode;
            }
            $whereParts[] = 's.topic_code IN (' . implode(', ', $topicPlaceholders) . ')';
        }

        return [$fromClause, implode(' + ', $scoreParts), implode(' OR ', $whereParts), $params];
    }

    private function extractWords(string $text): array
    {
        $normalized = strtolower((string) preg_replace('/[^a-z0-9\s]/i', ' ', $text));

        return array_values(array_filter(
            array_map('trim', explode(' ', $normalized)),
            static fn (string $word): bool => strlen($word) >= 2
        ));
    }

    /**
     * @param string[] $topicCodes
     */
    private function rowMatchesTopicCodes(array $row, array $topicCodes): bool
    {
        $subtopicCode = strtoupper(trim((string) ($row['subtopic_code'] ?? '')));
        if ($subtopicCode === '') {
            return false;
        }

        foreach ($topicCodes as $topicCode) {
            $topicCode = strtoupper(trim($topicCode));
            if ($topicCode !== '' && str_starts_with($subtopicCode, $topicCode . '_')) {
                return true;
            }
        }

        return false;
    }
}
