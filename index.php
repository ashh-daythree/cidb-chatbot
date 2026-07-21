<?php

declare(strict_types=1);

$basePath = __DIR__;
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

$apiPrefixes = ['/session', '/documents', '/signature', '/submission'];
foreach ($apiPrefixes as $prefix) {
    if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
        require $basePath . '/backend/public/index.php';
        return;
    }
}

readfile($basePath . '/home.html');