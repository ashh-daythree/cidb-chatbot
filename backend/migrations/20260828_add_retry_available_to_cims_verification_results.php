<?php

declare(strict_types=1);

namespace Cidb\Backend\Migrations;

use PDO;

final class AddRetryAvailableToCimsVerificationResults extends AbstractMigration
{
    public function name(): string
    {
        return '20260828_add_retry_available_to_cims_verification_results';
    }

    public function up(PDO $pdo): void
    {
        $this->executeStatements($pdo, [
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
            ALTER TABLE cims_verification_results
                DROP COLUMN IF EXISTS retry_available
            SQL,
        ]);
    }
}
