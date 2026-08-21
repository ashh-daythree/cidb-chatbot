<?php

declare(strict_types=1);

namespace Cidb\Backend\Migrations;

use PDO;

final class AddFaqFlow extends AbstractMigration
{
    public function name(): string
    {
        return '20260819_add_faq_flow';
    }

    public function up(PDO $pdo): void
    {
        $this->executeStatements($pdo, [
            <<<SQL
            CREATE TABLE IF NOT EXISTS reference_faq_topics (
                topic_code varchar(30) PRIMARY KEY,
                label_en varchar(150) NOT NULL,
                label_ms varchar(150) NOT NULL,
                sort_order integer NOT NULL DEFAULT 0,
                is_active boolean NOT NULL DEFAULT true,
                created_at timestamptz NOT NULL DEFAULT now(),
                updated_at timestamptz NOT NULL DEFAULT now()
            )
            SQL,
            <<<SQL
            CREATE TABLE IF NOT EXISTS reference_faq_subtopics (
                subtopic_code varchar(40) PRIMARY KEY,
                topic_code varchar(30) NOT NULL REFERENCES reference_faq_topics (topic_code),
                label_en varchar(150) NOT NULL,
                label_ms varchar(150) NOT NULL,
                sort_order integer NOT NULL DEFAULT 0,
                is_active boolean NOT NULL DEFAULT true,
                created_at timestamptz NOT NULL DEFAULT now(),
                updated_at timestamptz NOT NULL DEFAULT now()
            )
            SQL,
            <<<SQL
            CREATE INDEX IF NOT EXISTS ix_reference_faq_subtopics_topic_code
            ON reference_faq_subtopics (topic_code)
            SQL,
            <<<SQL
            CREATE TABLE IF NOT EXISTS chatbot_faq_questions (
                id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
                subtopic_code varchar(40) NOT NULL REFERENCES reference_faq_subtopics (subtopic_code),
                question_en text NOT NULL,
                question_ms text NOT NULL,
                answer_en text NOT NULL,
                answer_ms text NOT NULL,
                sort_order integer NOT NULL DEFAULT 0,
                is_active boolean NOT NULL DEFAULT true,
                created_at timestamptz NOT NULL DEFAULT now(),
                updated_at timestamptz NOT NULL DEFAULT now()
            )
            SQL,
            <<<SQL
            CREATE INDEX IF NOT EXISTS ix_chatbot_faq_questions_subtopic_sort
            ON chatbot_faq_questions (subtopic_code, sort_order)
            SQL,
            <<<SQL
            ALTER TABLE chatbot_sessions
            DROP CONSTRAINT IF EXISTS ck_chatbot_sessions_status
            SQL,
            <<<SQL
            ALTER TABLE chatbot_sessions
            ADD CONSTRAINT ck_chatbot_sessions_status CHECK (
                status IN (
                    'awaiting_language',
                    'awaiting_service',
                    'awaiting_state',
                    'awaiting_name',
                    'awaiting_identity',
                    'awaiting_documents',
                    'awaiting_company_ppk',
                    'awaiting_company_name',
                    'awaiting_company_email',
                    'awaiting_company_category',
                    'awaiting_company_director_name',
                    'awaiting_company_director_ic',
                    'awaiting_company_reason',
                    'awaiting_faq_topic',
                    'awaiting_faq_subtopic',
                    'submitted',
                    'under_review',
                    'completed',
                    'abandoned',
                    'expired',
                    'failed'
                )
            )
            SQL,
            <<<SQL
            ALTER TABLE chatbot_sessions
            DROP CONSTRAINT IF EXISTS ck_chatbot_sessions_current_step
            SQL,
            <<<SQL
            ALTER TABLE chatbot_sessions
            ADD CONSTRAINT ck_chatbot_sessions_current_step CHECK (
                current_step IN (
                    'ask_lang',
                    'ask_service',
                    'ask_state',
                    'ask_name',
                    'ask_ic',
                    'ask_mobile',
                    'ask_email',
                    'ask_ic_copy',
                    'ask_company_ppk',
                    'ask_company_name',
                    'ask_company_email',
                    'ask_company_category',
                    'ask_company_director_name',
                    'ask_company_director_ic',
                    'ask_company_reason',
                    'ask_faq_topic',
                    'ask_faq_subtopic',
                    'done'
                )
            )
            SQL,
            <<<SQL
            INSERT INTO reference_faq_topics (topic_code, label_en, label_ms, sort_order, is_active)
            VALUES
            ('SPKK', 'SPKK', 'SPKK', 1, true),
            ('STB', 'STB', 'STB', 2, true),
            ('PPK', 'PPK', 'PPK', 3, true)
            ON CONFLICT (topic_code) DO NOTHING
            SQL,
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

    public function down(PDO $pdo): void
    {
        $this->executeStatements($pdo, [
            <<<SQL
            DROP TABLE IF EXISTS chatbot_faq_questions
            SQL,
            <<<SQL
            DROP TABLE IF EXISTS reference_faq_subtopics
            SQL,
            <<<SQL
            DROP TABLE IF EXISTS reference_faq_topics
            SQL,
            <<<SQL
            ALTER TABLE chatbot_sessions
            DROP CONSTRAINT IF EXISTS ck_chatbot_sessions_current_step
            SQL,
            <<<SQL
            ALTER TABLE chatbot_sessions
            ADD CONSTRAINT ck_chatbot_sessions_current_step CHECK (
                current_step IN (
                    'ask_lang',
                    'ask_service',
                    'ask_state',
                    'ask_name',
                    'ask_ic',
                    'ask_mobile',
                    'ask_email',
                    'ask_ic_copy',
                    'ask_company_ppk',
                    'ask_company_name',
                    'ask_company_email',
                    'ask_company_category',
                    'ask_company_director_name',
                    'ask_company_director_ic',
                    'ask_company_reason',
                    'done'
                )
            )
            SQL,
            <<<SQL
            ALTER TABLE chatbot_sessions
            DROP CONSTRAINT IF EXISTS ck_chatbot_sessions_status
            SQL,
            <<<SQL
            ALTER TABLE chatbot_sessions
            ADD CONSTRAINT ck_chatbot_sessions_status CHECK (
                status IN (
                    'awaiting_language',
                    'awaiting_service',
                    'awaiting_state',
                    'awaiting_name',
                    'awaiting_identity',
                    'awaiting_documents',
                    'awaiting_company_ppk',
                    'awaiting_company_name',
                    'awaiting_company_email',
                    'awaiting_company_category',
                    'awaiting_company_director_name',
                    'awaiting_company_director_ic',
                    'awaiting_company_reason',
                    'submitted',
                    'under_review',
                    'completed',
                    'abandoned',
                    'expired',
                    'failed'
                )
            )
            SQL,
        ]);
    }
}
