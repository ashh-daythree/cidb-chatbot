<?php

declare(strict_types=1);

namespace Cidb\Backend\Migrations;

use PDO;

final class AddFormFieldsToAssistanceRequests extends AbstractMigration
{
    public function name(): string
    {
        return '20260829_add_form_fields_to_assistance_requests';
    }

    public function up(PDO $pdo): void
    {
        $this->executeStatements($pdo, [
            <<<'SQL'
            ALTER TABLE chatbot_assistance_requests
                ADD COLUMN IF NOT EXISTS cases_category           varchar(100),
                ADD COLUMN IF NOT EXISTS sub_category_1           varchar(150),
                ADD COLUMN IF NOT EXISTS sub_category_2           varchar(200),
                ADD COLUMN IF NOT EXISTS attachment_document_id_2 uuid,
                ADD COLUMN IF NOT EXISTS attachment_document_id_3 uuid
            SQL,
            <<<'SQL'
            ALTER TABLE chatbot_assistance_requests
            DROP CONSTRAINT IF EXISTS fk_chatbot_assistance_requests_attachment_2
            SQL,
            <<<'SQL'
            ALTER TABLE chatbot_assistance_requests
            ADD CONSTRAINT fk_chatbot_assistance_requests_attachment_2
                FOREIGN KEY (attachment_document_id_2) REFERENCES uploaded_documents (id)
            SQL,
            <<<'SQL'
            ALTER TABLE chatbot_assistance_requests
            DROP CONSTRAINT IF EXISTS fk_chatbot_assistance_requests_attachment_3
            SQL,
            <<<'SQL'
            ALTER TABLE chatbot_assistance_requests
            ADD CONSTRAINT fk_chatbot_assistance_requests_attachment_3
                FOREIGN KEY (attachment_document_id_3) REFERENCES uploaded_documents (id)
            SQL,
        ]);
    }

    public function down(PDO $pdo): void
    {
        $this->executeStatements($pdo, [
            <<<'SQL'
            ALTER TABLE chatbot_assistance_requests
            DROP CONSTRAINT IF EXISTS fk_chatbot_assistance_requests_attachment_2
            SQL,
            <<<'SQL'
            ALTER TABLE chatbot_assistance_requests
            DROP CONSTRAINT IF EXISTS fk_chatbot_assistance_requests_attachment_3
            SQL,
            <<<'SQL'
            ALTER TABLE chatbot_assistance_requests
                DROP COLUMN IF EXISTS cases_category,
                DROP COLUMN IF EXISTS sub_category_1,
                DROP COLUMN IF EXISTS sub_category_2,
                DROP COLUMN IF EXISTS attachment_document_id_2,
                DROP COLUMN IF EXISTS attachment_document_id_3
            SQL,
        ]);
    }
}
