<?php

declare(strict_types=1);

namespace Cidb\Backend\Migrations;

use PDO;

interface MigrationInterface
{
    public function name(): string;

    public function up(PDO $pdo): void;

    public function down(PDO $pdo): void;
}

