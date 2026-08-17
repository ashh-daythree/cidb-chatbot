<?php

declare(strict_types=1);

namespace Cidb\Backend\Migrations;

use PDO;

final class MakeServiceRequestsApplicantIdNullable extends AbstractMigration
{
    public function name(): string
    {
        return '20260814_make_service_requests_applicant_id_nullable';
    }

    public function up(PDO $pdo): void
    {
        $this->executeStatements($pdo, [
            <<<SQL
            ALTER TABLE service_requests
            ALTER COLUMN applicant_id DROP NOT NULL
            SQL,
        ]);
    }

    public function down(PDO $pdo): void
    {
        $this->executeStatements($pdo, [
            <<<SQL
            ALTER TABLE service_requests
            ALTER COLUMN applicant_id SET NOT NULL
            SQL,
        ]);
    }
}
