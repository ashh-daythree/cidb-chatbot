<?php

declare(strict_types=1);

namespace Cidb\Backend\Migrations;

use PDO;

final class AddCompanyEmailIdCancellationFlow extends AbstractMigration
{
    public function name(): string
    {
        return '20260813_add_company_email_id_cancellation_flow';
    }

    public function up(PDO $pdo): void
    {
        $this->executeStatements($pdo, [
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
            INSERT INTO reference_request_types (request_type_code, label_en, label_ms, description, is_active)
            VALUES
            ('COMPANY_EMAIL_ID_CANCELLATION', 'Company Email ID Cancellation', 'Pembatalan Email ID Syarikat', 'Company CIDB chatbot request type', true)
            ON CONFLICT (request_type_code) DO NOTHING
            SQL,
            <<<SQL
            INSERT INTO reference_document_types (
                document_type_code,
                label_en,
                label_ms,
                capture_mode,
                is_required_for_submission,
                allow_multiple,
                sort_order,
                allowed_mime_types,
                max_file_size_mb,
                requires_ocr,
                is_active
            )
            VALUES
            ('SSM_PPK_CERTIFICATE', 'SSM / PPK Certificate', 'Sijil SSM / PPK', 'upload', true, false, 4, '["image/jpeg","image/png","image/jpg","image/webp","application/pdf"]'::jsonb, 10, false, true)
            ON CONFLICT (document_type_code) DO NOTHING
            SQL,
        ]);
    }

    public function down(PDO $pdo): void
    {
        $this->executeStatements($pdo, [
            <<<SQL
            DELETE FROM reference_document_types
            WHERE document_type_code = 'SSM_PPK_CERTIFICATE'
            SQL,
            <<<SQL
            DELETE FROM reference_request_types
            WHERE request_type_code = 'COMPANY_EMAIL_ID_CANCELLATION'
            SQL,
            <<<SQL
            ALTER TABLE chatbot_sessions
            DROP CONSTRAINT IF EXISTS ck_chatbot_sessions_current_step
            SQL,
            <<<SQL
            ALTER TABLE chatbot_sessions
            ADD CONSTRAINT ck_chatbot_sessions_current_step CHECK (
                current_step IN ('ask_lang', 'ask_state', 'ask_name', 'ask_ic', 'ask_mobile', 'ask_email', 'ask_ic_copy', 'done')
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
                    'awaiting_state',
                    'awaiting_name',
                    'awaiting_identity',
                    'awaiting_documents',
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
