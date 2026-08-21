<?php

declare(strict_types=1);

namespace Cidb\Backend\Migrations;

use PDO;

final class FixChatbotSessionCompanyContactConstraints extends AbstractMigration
{
    public function name(): string
    {
        return '20260820_fix_chatbot_session_company_contact_constraints';
    }

    public function up(PDO $pdo): void
    {
        $this->executeStatements($pdo, [
            <<<'SQL'
            ALTER TABLE chatbot_sessions
            DROP CONSTRAINT IF EXISTS ck_chatbot_sessions_current_step
            SQL,
            <<<'SQL'
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
                    'ask_company_contact',
                    'ask_company_category',
                    'ask_company_director_name',
                    'ask_company_director_ic',
                    'ask_company_reason',
                    'done'
                )
            )
            SQL,
            <<<'SQL'
            ALTER TABLE chatbot_sessions
            DROP CONSTRAINT IF EXISTS ck_chatbot_sessions_status
            SQL,
            <<<'SQL'
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
                    'awaiting_company_contact',
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

    public function down(PDO $pdo): void
    {
        $this->executeStatements($pdo, [
            <<<'SQL'
            ALTER TABLE chatbot_sessions
            DROP CONSTRAINT IF EXISTS ck_chatbot_sessions_current_step
            SQL,
            <<<'SQL'
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
            <<<'SQL'
            ALTER TABLE chatbot_sessions
            DROP CONSTRAINT IF EXISTS ck_chatbot_sessions_status
            SQL,
            <<<'SQL'
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
