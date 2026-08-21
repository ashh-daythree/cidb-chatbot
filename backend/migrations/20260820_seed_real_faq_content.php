<?php

declare(strict_types=1);

namespace Cidb\Backend\Migrations;

use PDO;

final class SeedRealFaqContent extends AbstractMigration
{
    public function name(): string
    {
        return '20260820_seed_real_faq_content';
    }

    public function up(PDO $pdo): void
    {
        $pdo->exec('DELETE FROM chatbot_faq_questions');
        $pdo->exec('DELETE FROM reference_faq_subtopics');

        $data = require __DIR__ . '/data/faq_content.php';

        $insertSubtopic = $pdo->prepare(
            'INSERT INTO reference_faq_subtopics (subtopic_code, topic_code, label_en, label_ms, sort_order, is_active)
             VALUES (:subtopic_code, :topic_code, :label_en, :label_ms, :sort_order, true)'
        );

        $insertQuestion = $pdo->prepare(
            'INSERT INTO chatbot_faq_questions (subtopic_code, question_en, question_ms, answer_en, answer_ms, sort_order, is_active)
             VALUES (:subtopic_code, :question_en, :question_ms, :answer_en, :answer_ms, :sort_order, true)'
        );

        foreach ($data as $topicCode => $topic) {
            foreach ($topic['subtopics'] as $subtopic) {
                $insertSubtopic->execute([
                    'subtopic_code' => $subtopic['code'],
                    'topic_code' => $topicCode,
                    'label_en' => $subtopic['label_en'],
                    'label_ms' => $subtopic['label_ms'],
                    'sort_order' => $subtopic['sort_order'],
                ]);
            }

            foreach ($topic['questions'] as $question) {
                $insertQuestion->execute([
                    'subtopic_code' => $question['subtopic_code'],
                    'question_en' => $question['question_en'],
                    'question_ms' => $question['question_ms'],
                    'answer_en' => $question['answer_en'],
                    'answer_ms' => $question['answer_ms'],
                    'sort_order' => $question['sort_order'],
                ]);
            }
        }
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec('DELETE FROM chatbot_faq_questions');
        $pdo->exec('DELETE FROM reference_faq_subtopics');

        $this->executeStatements($pdo, [
            <<<SQL
            INSERT INTO reference_faq_subtopics (subtopic_code, topic_code, label_en, label_ms, sort_order, is_active)
            VALUES
            ('SPKK_GENERAL', 'SPKK', 'General', 'Umum', 1, true),
            ('STB_GENERAL', 'STB', 'General', 'Umum', 1, true),
            ('PPK_GENERAL', 'PPK', 'General', 'Umum', 1, true)
            ON CONFLICT (subtopic_code) DO NOTHING
            SQL,
            <<<SQL
            INSERT INTO chatbot_faq_questions (subtopic_code, question_en, question_ms, answer_en, answer_ms, sort_order, is_active)
            VALUES
            ('SPKK_GENERAL', 'What is SPKK?', 'Apakah itu SPKK?', 'Placeholder answer for SPKK general question 1. Real content to be provided.', 'Jawapan sementara bagi soalan umum SPKK 1. Kandungan sebenar akan disediakan.', 1, true),
            ('SPKK_GENERAL', 'How do I register for SPKK?', 'Bagaimana saya mendaftar SPKK?', 'Placeholder answer for SPKK general question 2. Real content to be provided.', 'Jawapan sementara bagi soalan umum SPKK 2. Kandungan sebenar akan disediakan.', 2, true),
            ('STB_GENERAL', 'What is STB?', 'Apakah itu STB?', 'Placeholder answer for STB general question 1. Real content to be provided.', 'Jawapan sementara bagi soalan umum STB 1. Kandungan sebenar akan disediakan.', 1, true),
            ('STB_GENERAL', 'How do I apply for STB?', 'Bagaimana saya memohon STB?', 'Placeholder answer for STB general question 2. Real content to be provided.', 'Jawapan sementara bagi soalan umum STB 2. Kandungan sebenar akan disediakan.', 2, true),
            ('PPK_GENERAL', 'What is PPK?', 'Apakah itu PPK?', 'Placeholder answer for PPK general question 1. Real content to be provided.', 'Jawapan sementara bagi soalan umum PPK 1. Kandungan sebenar akan disediakan.', 1, true),
            ('PPK_GENERAL', 'How do I renew PPK?', 'Bagaimana saya memperbaharui PPK?', 'Placeholder answer for PPK general question 2. Real content to be provided.', 'Jawapan sementara bagi soalan umum PPK 2. Kandungan sebenar akan disediakan.', 2, true)
            SQL,
        ]);
    }
}
