<?php

declare(strict_types=1);

use Cidb\Backend\Bootstrap\Bootstrap;
use Cidb\Backend\Migrations\MigrationManager;

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

try {
    $container = Bootstrap::create(dirname(__DIR__));
    $applied = $container->get(MigrationManager::class)->runPending();

    if ($applied === []) {
        fwrite(STDOUT, 'No pending migrations.' . PHP_EOL);
    }

    foreach ($applied as $name) {
        fwrite(STDOUT, 'Applied: ' . $name . PHP_EOL);
    }
} catch (Throwable $e) {
    fwrite(STDERR, 'Migration failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
