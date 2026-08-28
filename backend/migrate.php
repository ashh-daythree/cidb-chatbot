<?php

declare(strict_types=1);

use Cidb\Backend\Bootstrap\Bootstrap;
use Cidb\Backend\Config\DatabaseConnection;
use Cidb\Backend\Migrations\MigrationInterface;

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$basePath = dirname(__DIR__);
$container = Bootstrap::create($basePath);

/** @var DatabaseConnection $connection */
$connection = $container->get(DatabaseConnection::class);
$pdo = $connection->pdo();

$migrationsDir = $basePath . DIRECTORY_SEPARATOR . 'backend' . DIRECTORY_SEPARATOR . 'migrations';
$mode = $argv[1] ?? 'status';

if ($mode === 'status') {
    $rows = $pdo->query('SELECT migration_name FROM migration_history ORDER BY migration_name')->fetchAll(PDO::FETCH_COLUMN);
    echo 'Applied migrations (' . count($rows) . '):' . PHP_EOL;
    foreach ($rows as $r) {
        echo "  - {$r}" . PHP_EOL;
    }
    exit(0);
}

if ($mode === 'one') {
    $file = $argv[2] ?? '';
    $path = $migrationsDir . DIRECTORY_SEPARATOR . $file;
    if (!is_file($path)) {
        echo "File not found: {$path}" . PHP_EOL;
        exit(1);
    }

    $before = get_declared_classes();
    require_once $path;
    $new = array_values(array_diff(get_declared_classes(), $before));

    $target = null;
    foreach ($new as $class) {
        if (is_subclass_of($class, MigrationInterface::class)) {
            $target = new $class();
            break;
        }
    }
    if ($target === null) {
        echo 'No migration class found in file.' . PHP_EOL;
        exit(1);
    }

    $already = $pdo->prepare('SELECT 1 FROM migration_history WHERE migration_name = :n');
    $already->execute([':n' => $target->name()]);
    if ($already->fetchColumn() !== false) {
        echo 'Already applied: ' . $target->name() . PHP_EOL;
        exit(0);
    }

    $pdo->beginTransaction();
    try {
        $target->up($pdo);
        $nextBatch = (int) $pdo->query('SELECT COALESCE(MAX(batch_no), 0) + 1 FROM migration_history')->fetchColumn();
        $stmt = $pdo->prepare('INSERT INTO migration_history (migration_name, batch_no, execution_time_ms, applied_at) VALUES (:n, :b, 0, now())');
        $stmt->execute([':n' => $target->name(), ':b' => $nextBatch]);
        $pdo->commit();
        echo 'Applied: ' . $target->name() . PHP_EOL;
    } catch (Throwable $e) {
        $pdo->rollBack();
        echo 'FAILED: ' . $e->getMessage() . PHP_EOL;
        exit(1);
    }
}
