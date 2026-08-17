<?php

declare(strict_types=1);

namespace Cidb\Backend\Migrations;

use PDO;

final class AddContactStepsToChatbotSessions extends AbstractMigration
{
    public function name(): string
    {
        return '20260730_add_contact_steps_to_chatbot_sessions';
    }

    public function up(PDO $pdo): void
    {
        $pdo->exec(<<<SQL
            ALTER TABLE chatbot_sessions
            DROP CONSTRAINT IF EXISTS ck_chatbot_sessions_current_step
        SQL);

        $pdo->exec(<<<SQL
            ALTER TABLE chatbot_sessions
            ADD CONSTRAINT ck_chatbot_sessions_current_step CHECK (
                current_step IN ('ask_lang', 'ask_state', 'ask_name', 'ask_ic', 'ask_mobile', 'ask_email', 'ask_ic_copy', 'done')
            )
        SQL);
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec(<<<SQL
            ALTER TABLE chatbot_sessions
            DROP CONSTRAINT IF EXISTS ck_chatbot_sessions_current_step
        SQL);

        $pdo->exec(<<<SQL
            ALTER TABLE chatbot_sessions
            ADD CONSTRAINT ck_chatbot_sessions_current_step CHECK (
                current_step IN ('ask_lang', 'ask_state', 'ask_name', 'ask_ic', 'ask_ic_copy', 'done')
            )
        SQL);
    }
}
