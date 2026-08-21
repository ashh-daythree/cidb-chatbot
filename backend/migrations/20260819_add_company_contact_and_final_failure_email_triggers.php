<?php

declare(strict_types=1);

namespace Cidb\Backend\Migrations;

use PDO;

final class AddCompanyContactAndFinalFailureEmailTriggers extends AbstractMigration
{
    public function name(): string
    {
        return '20260819_add_company_contact_and_final_failure_email_triggers';
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
            CREATE TABLE IF NOT EXISTS final_failure_email_triggers (
                id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
                session_id uuid NOT NULL REFERENCES chatbot_sessions(id) ON DELETE CASCADE ON UPDATE CASCADE,
                request_id uuid REFERENCES service_requests(id) ON DELETE CASCADE ON UPDATE CASCADE,
                failure_type varchar(20) NOT NULL,
                service_type varchar(20) NOT NULL,
                attempt_no integer NOT NULL,
                status varchar(20) NOT NULL,
                payload jsonb NOT NULL DEFAULT '{}'::jsonb,
                response_code varchar(50),
                response_message text,
                response_payload jsonb NOT NULL DEFAULT '{}'::jsonb,
                detected_at timestamptz NOT NULL DEFAULT now(),
                triggered_at timestamptz,
                completed_at timestamptz,
                created_at timestamptz NOT NULL DEFAULT now(),
                updated_at timestamptz NOT NULL DEFAULT now(),
                CONSTRAINT uq_final_failure_email_triggers_session_failure UNIQUE (session_id, failure_type),
                CONSTRAINT ck_final_failure_email_triggers_failure_type CHECK (failure_type IN ('ocr', 'cancellation')),
                CONSTRAINT ck_final_failure_email_triggers_service_type CHECK (service_type IN ('individual', 'company')),
                CONSTRAINT ck_final_failure_email_triggers_status CHECK (status IN ('detected', 'triggering', 'triggered', 'failed')),
                CONSTRAINT ck_final_failure_email_triggers_payload CHECK (jsonb_typeof(payload) = 'object'),
                CONSTRAINT ck_final_failure_email_triggers_response_payload CHECK (jsonb_typeof(response_payload) = 'object'),
                CONSTRAINT ck_final_failure_email_triggers_attempt_no CHECK (attempt_no > 0)
            )
            SQL,
            <<<'SQL'
            CREATE INDEX IF NOT EXISTS idx_final_failure_email_triggers_session_id ON final_failure_email_triggers (session_id)
            SQL,
            <<<'SQL'
            CREATE INDEX IF NOT EXISTS idx_final_failure_email_triggers_request_id ON final_failure_email_triggers (request_id)
            SQL,
            <<<'SQL'
            CREATE INDEX IF NOT EXISTS idx_final_failure_email_triggers_failure_type ON final_failure_email_triggers (failure_type)
            SQL,
            <<<'SQL'
            CREATE INDEX IF NOT EXISTS idx_final_failure_email_triggers_status ON final_failure_email_triggers (status)
            SQL,
        ]);
    }

    public function down(PDO $pdo): void
    {
        $this->executeStatements($pdo, [
            <<<'SQL'
            DROP TABLE IF EXISTS final_failure_email_triggers
            SQL,
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
        ]);
    }
}
