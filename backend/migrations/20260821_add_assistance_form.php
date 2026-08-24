<?php

declare(strict_types=1);

namespace Cidb\Backend\Migrations;

use PDO;

final class AddAssistanceForm extends AbstractMigration
{
    public function name(): string
    {
        return '20260821_add_assistance_form';
    }

    public function up(PDO $pdo): void
    {
        $this->executeStatements($pdo, [
            <<<SQL
            CREATE TABLE IF NOT EXISTS chatbot_assistance_requests (
                id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
                session_id uuid NOT NULL REFERENCES chatbot_sessions (id),
                state varchar(100) NOT NULL,
                customer_name varchar(255) NOT NULL,
                applicant_category varchar(20) NOT NULL CHECK (applicant_category IN ('individual', 'company')),
                phone varchar(30) NOT NULL,
                email varchar(255) NOT NULL,
                enquiry_title text NOT NULL,
                enquiry_description text NOT NULL,
                id_number varchar(30) NOT NULL,
                company_name varchar(255),
                company_registration_no varchar(60),
                attachment_document_id uuid REFERENCES uploaded_documents (id),
                status varchar(20) NOT NULL DEFAULT 'new',
                created_at timestamptz NOT NULL DEFAULT now(),
                updated_at timestamptz NOT NULL DEFAULT now()
            )
            SQL,
            <<<SQL
            CREATE INDEX IF NOT EXISTS ix_chatbot_assistance_requests_session_id
            ON chatbot_assistance_requests (session_id)
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
            ('ASSISTANCE_ATTACHMENT', 'Supporting Document', 'Dokumen Sokongan', 'upload', false, false, 90, '["image/jpeg","image/png","image/jpg","image/webp","application/pdf"]'::jsonb, 10, false, true)
            ON CONFLICT (document_type_code) DO NOTHING
            SQL,
        ]);
    }

    public function down(PDO $pdo): void
    {
        $this->executeStatements($pdo, [
            <<<SQL
            DELETE FROM reference_document_types
            WHERE document_type_code = 'ASSISTANCE_ATTACHMENT'
            SQL,
            <<<SQL
            DROP TABLE IF EXISTS chatbot_assistance_requests
            SQL,
        ]);
    }
}
