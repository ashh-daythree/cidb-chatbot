<?php

declare(strict_types=1);

namespace Cidb\Backend\Migrations;

use PDO;

final class ExpandServiceRequestsCimsStatuses extends AbstractMigration
{
    public function name(): string
    {
        return '20260820_expand_service_requests_cims_statuses';
    }

    public function up(PDO $pdo): void
    {
        $this->executeStatements($pdo, [
            <<<'SQL'
            ALTER TABLE service_requests
            DROP CONSTRAINT IF EXISTS ck_service_requests_latest_cims_status
            SQL,
            <<<'SQL'
            ALTER TABLE service_requests
            ADD CONSTRAINT ck_service_requests_latest_cims_status CHECK (
                latest_cims_status IN (
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
            ALTER TABLE service_requests
            DROP CONSTRAINT IF EXISTS ck_service_requests_latest_cims_status
            SQL,
            <<<'SQL'
            ALTER TABLE service_requests
            ADD CONSTRAINT ck_service_requests_latest_cims_status CHECK (
                latest_cims_status IN ('pending', 'deleted', 'linked', 'norecord', 'error')
            )
            SQL,
        ]);
    }
}
