<?php

declare(strict_types=1);

namespace Cidb\Backend\Migrations;

use PDO;

abstract class AbstractMigration implements MigrationInterface
{
    protected function executeStatements(PDO $pdo, array $statements): void
    {
        foreach ($statements as $statement) {
            $sql = trim((string) $statement);

            if ($sql === '') {
                continue;
            }

            $pdo->exec($sql);
        }
    }
}

