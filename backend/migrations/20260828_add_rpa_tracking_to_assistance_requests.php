<?php

declare(strict_types=1);

namespace Cidb\Backend\Migrations;

use PDO;

final class AddRpaTrackingToAssistanceRequests extends AbstractMigration
{
    public function name(): string
    {
        return '20260828_add_rpa_tracking_to_assistance_requests';
    }

    public function up(PDO $pdo): void
    {
        $this->executeStatements($pdo, [
            <<<'SQL'
            ALTER TABLE chatbot_assistance_requests
                ADD COLUMN IF NOT EXISTS language_code varchar(5) NOT NULL DEFAULT 'en',
                ADD COLUMN IF NOT EXISTS rpa_status varchar(20) NOT NULL DEFAULT 'pending',
                ADD COLUMN IF NOT EXISTS case_reference_no varchar(120),
                ADD COLUMN IF NOT EXISTS rpa_schedule_id varchar(120),
                ADD COLUMN IF NOT EXISTS rpa_attempt_no integer NOT NULL DEFAULT 0,
                ADD COLUMN IF NOT EXISTS rpa_response_code integer,
                ADD COLUMN IF NOT EXISTS rpa_response_message text,
                ADD COLUMN IF NOT EXISTS rpa_response_payload jsonb,
                ADD COLUMN IF NOT EXISTS rpa_display_message text,
                ADD COLUMN IF NOT EXISTS rpa_triggered_at timestamptz,
                ADD COLUMN IF NOT EXISTS rpa_completed_at timestamptz
            SQL,
            <<<'SQL'
            ALTER TABLE chatbot_assistance_requests
            DROP CONSTRAINT IF EXISTS ck_chatbot_assistance_requests_rpa_status
            SQL,
            <<<'SQL'
            ALTER TABLE chatbot_assistance_requests
            ADD CONSTRAINT ck_chatbot_assistance_requests_rpa_status CHECK (
                rpa_status IN ('pending', 'logged', 'failed', 'not_triggered')
            )
            SQL,
            <<<'SQL'
            CREATE INDEX IF NOT EXISTS ix_chatbot_assistance_requests_rpa_schedule_id
            ON chatbot_assistance_requests (rpa_schedule_id)
            SQL,
        ]);
    }

    public function down(PDO $pdo): void
    {
        $this->executeStatements($pdo, [
            <<<'SQL'
            DROP INDEX IF EXISTS ix_chatbot_assistance_requests_rpa_schedule_id
            SQL,
            <<<'SQL'
            ALTER TABLE chatbot_assistance_requests
            DROP CONSTRAINT IF EXISTS ck_chatbot_assistance_requests_rpa_status
            SQL,
            <<<'SQL'
            ALTER TABLE chatbot_assistance_requests
                DROP COLUMN IF EXISTS language_code,
                DROP COLUMN IF EXISTS rpa_status,
                DROP COLUMN IF EXISTS case_reference_no,
                DROP COLUMN IF EXISTS rpa_schedule_id,
                DROP COLUMN IF EXISTS rpa_attempt_no,
                DROP COLUMN IF EXISTS rpa_response_code,
                DROP COLUMN IF EXISTS rpa_response_message,
                DROP COLUMN IF EXISTS rpa_response_payload,
                DROP COLUMN IF EXISTS rpa_display_message,
                DROP COLUMN IF EXISTS rpa_triggered_at,
                DROP COLUMN IF EXISTS rpa_completed_at
            SQL,
        ]);
    }
}
