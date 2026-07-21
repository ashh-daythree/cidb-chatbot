<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Cidb\Backend\Config\EnvironmentLoader;
use Cidb\Backend\Config\DatabaseConfig;
use Cidb\Backend\Config\DatabaseConnection;

try {
    // Load the .env file
    EnvironmentLoader::load(__DIR__ . '/../.env');

    // Create configuration
    $config = DatabaseConfig::fromEnv();

    // Create connection
    $db = new DatabaseConnection($config);

    // Attempt connection
    $pdo = $db->pdo();

    echo "✅ Database connected successfully!" . PHP_EOL;

    // Optional: print PostgreSQL version
    echo "PostgreSQL Version: " .
        $pdo->query("SELECT version()")->fetchColumn() .
        PHP_EOL;

} catch (Throwable $e) {
    echo "❌ Connection failed!" . PHP_EOL;
    echo $e->getMessage() . PHP_EOL;
}