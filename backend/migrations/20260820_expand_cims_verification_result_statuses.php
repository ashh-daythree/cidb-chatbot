<?php

declare(strict_types=1);

namespace Cidb\Backend\Migrations;

use PDO;

final class ExpandCimsVerificationResultStatuses extends AbstractMigration
{
    public function name(): string
    {
        return '20260820_expand_cims_verification_result_statuses';
    }

    public function up(PDO $pdo): void
    {
        $this->executeStatements($pdo, [
            <<<'SQL'
            ALTER TABLE cims_verification_results
            DROP CONSTRAINT IF EXISTS ck_cims_verification_results_result_status
            SQL,
            <<<'SQL'
            ALTER TABLE cims_verification_results
            ADD CONSTRAINT ck_cims_verification_results_result_status CHECK (
                result_status IN (
                    'pending',
                    'deleted',
                    'linked',
                    'norecord',
                    'error',
                    'approved',
                    'rejected',
                    'manual_review'
                )
            )
            SQL,
        ]);
    }

    public function down(PDO $pdo): void
    {
        $this->executeStatements($pdo, [
            <<<'SQL'
            ALTER TABLE cims_verification_results
            DROP CONSTRAINT IF EXISTS ck_cims_verification_results_result_status
            SQL,
            <<<'SQL'
            ALTER TABLE cims_verification_results
            ADD CONSTRAINT ck_cims_verification_results_result_status CHECK (
                result_status IN ('pending', 'deleted', 'linked', 'norecord', 'error')
            )
            SQL,
        ]);
    }
}
