<?php

declare(strict_types=1);

namespace Cidb\Backend\Migrations;

use PDO;

final class AddRpaResponseTextToCimsVerificationResults extends AbstractMigration
{
    public function name(): string
    {
        return '20260721_add_rpa_response_text_to_cims_verification_results';
    }

    public function up(PDO $pdo): void
    {
        $pdo->exec(<<<SQL
            ALTER TABLE cims_verification_results
            ADD COLUMN IF NOT EXISTS rpa_response_text text NOT NULL DEFAULT ''
        SQL);
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec(<<<SQL
            ALTER TABLE cims_verification_results
            DROP COLUMN IF EXISTS rpa_response_text
        SQL);
    }
}
