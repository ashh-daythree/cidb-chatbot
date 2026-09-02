<?php

declare(strict_types=1);

namespace Cidb\Backend\Migrations;

use PDO;

final class AddFaqAssistanceEnquiryFlow extends AbstractMigration
{
    public function name(): string
    {
        return '20260903_add_faq_assistance_enquiry_flow';
    }

    public function up(PDO $pdo): void
    {
        $this->executeStatements($pdo, [
            <<<SQL
            INSERT INTO reference_request_types (request_type_code, label_en, label_ms, description, is_active)
            VALUES
            ('FAQ_ASSISTANCE_ENQUIRY', 'FAQ Assistance Enquiry', 'Pertanyaan Bantuan FAQ', 'FAQ assistance form enquiry routed to the RPA bot', true)
            ON CONFLICT (request_type_code) DO NOTHING
            SQL,
            <<<'SQL'
            ALTER TABLE chatbot_assistance_requests
                ADD COLUMN IF NOT EXISTS service_request_id uuid
            SQL,
            <<<'SQL'
            ALTER TABLE chatbot_assistance_requests
            DROP CONSTRAINT IF EXISTS fk_chatbot_assistance_requests_service_request
            SQL,
            <<<'SQL'
            ALTER TABLE chatbot_assistance_requests
            ADD CONSTRAINT fk_chatbot_assistance_requests_service_request
                FOREIGN KEY (service_request_id) REFERENCES service_requests (id)
            SQL,
            // Self-heal: these columns are used by VerificationService but were added to the
            // live DB out-of-repo (no committed migration). Make a fresh migrate.php work.
            <<<'SQL'
            ALTER TABLE cims_verification_results
                ADD COLUMN IF NOT EXISTS display_message text,
                ADD COLUMN IF NOT EXISTS retry_available boolean NOT NULL DEFAULT false
            SQL,
        ]);
    }

    public function down(PDO $pdo): void
    {
        $this->executeStatements($pdo, [
            <<<'SQL'
            ALTER TABLE chatbot_assistance_requests
            DROP CONSTRAINT IF EXISTS fk_chatbot_assistance_requests_service_request
            SQL,
            <<<'SQL'
            ALTER TABLE chatbot_assistance_requests
                DROP COLUMN IF EXISTS service_request_id
            SQL,
            <<<SQL
            DELETE FROM reference_request_types
            WHERE request_type_code = 'FAQ_ASSISTANCE_ENQUIRY'
            SQL,
        ]);
    }
}
