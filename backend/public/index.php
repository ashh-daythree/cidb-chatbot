<?php

declare(strict_types=1);

use Cidb\Backend\Bootstrap\Bootstrap;
use Cidb\Backend\Routes\ApiRouter;
use Cidb\Backend\Utils\JsonHelper;
use Cidb\Backend\Utils\ErrorHandler;

require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

// Serve the chat UI and its static assets. Under `php -S` this file is the
// router script and receives every request, so anything not returned here
// falls through to the JSON API router and 404s. The list is an allowlist:
// the document root is the whole repository, so a denylist would eventually
// leak source.
if (PHP_SAPI === 'cli-server') {
    $staticPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');

    if ($docRoot !== false) {
        if ($staticPath === '/' || $staticPath === '/index.html') {
            $indexFile = $docRoot . DIRECTORY_SEPARATOR . 'home.html';
            if (is_file($indexFile)) {
                header('Content-Type: text/html; charset=utf-8');
                readfile($indexFile);
                return;
            }
        }

        $isPublicAsset = $staticPath === '/home.html'
            || $staticPath === '/frontend_api.js'
            || str_starts_with($staticPath, '/assets/');

        if ($isPublicAsset) {
            $target = realpath($docRoot . $staticPath);
            if ($target !== false
                && is_file($target)
                && str_starts_with($target, $docRoot . DIRECTORY_SEPARATOR)
            ) {
                return false;
            }
        }
    }
}

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

if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'GET'
    && (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/') === '/health'
) {
    if (PHP_SAPI !== 'cli') {
        http_response_code(200);
        header('Content-Type: application/json; charset=utf-8');
    }

    echo JsonHelper::encode([
        'success' => true,
        'status' => 'ok',
    ]);
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
