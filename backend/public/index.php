<?php

declare(strict_types=1);

use Cidb\Backend\Bootstrap\Bootstrap;
use Cidb\Backend\Routes\ApiRouter;
use Cidb\Backend\Utils\JsonHelper;
use Cidb\Backend\Utils\ErrorHandler;

require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$basePath = dirname(__DIR__, 2);
$container = Bootstrap::create($basePath);

function cidb_allowed_origins(array $fallbackOrigins = []): array
{
    $raw = trim((string) getenv('CORS_ALLOWED_ORIGINS'));
    if ($raw !== '') {
        $origins = array_filter(array_map('trim', explode(',', $raw)), static fn (string $origin): bool => $origin !== '');
        return array_values(array_unique($origins));
    }

    $appUrl = trim((string) getenv('APP_URL'));
    $devOrigins = [
        'http://localhost:5500',
        'http://127.0.0.1:5500',
        'http://localhost:3000',
        'http://127.0.0.1:3000',
    ];

    return array_values(array_unique(array_filter(array_merge(
        $fallbackOrigins,
        $appUrl !== '' ? [$appUrl] : [],
        $devOrigins
    ), static fn (string $origin): bool => $origin !== '')));
}

function cidb_apply_cors_headers(): void
{
    $origin = trim((string) ($_SERVER['HTTP_ORIGIN'] ?? ''));
    $allowedOrigins = cidb_allowed_origins();

    if ($origin !== '' && in_array($origin, $allowedOrigins, true)) {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Vary: Origin');
    }

    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin');
    header('Access-Control-Max-Age: 86400');
}

cidb_apply_cors_headers();

if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'OPTIONS') {
    if (PHP_SAPI !== 'cli') {
        http_response_code(204);
    }

    return;
}

/** @var ErrorHandler $errorHandler */
$errorHandler = $container->get(ErrorHandler::class);
$errorHandler->register();

$request = [
    'method' => $_SERVER['REQUEST_METHOD'] ?? 'GET',
    'path' => parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/',
    'query' => $_GET ?? [],
    'post' => $_POST ?? [],
    'files' => $_FILES ?? [],
    'headers' => function_exists('getallheaders') ? getallheaders() : [],
    'body' => file_get_contents('php://input') ?: '',
];

$router = new ApiRouter($container, require $basePath . DIRECTORY_SEPARATOR . 'backend' . DIRECTORY_SEPARATOR . 'routes' . DIRECTORY_SEPARATOR . 'api.php');
$response = $router->dispatch($request);

if (PHP_SAPI !== 'cli') {
    http_response_code($response['statusCode']);
    header('Content-Type: application/json; charset=utf-8');
}

echo JsonHelper::encode($response['payload']);
