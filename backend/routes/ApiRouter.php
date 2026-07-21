<?php

declare(strict_types=1);

namespace Cidb\Backend\Routes;

use Cidb\Backend\Bootstrap\Container;
use Cidb\Backend\Utils\Exceptions\AppException;

final class ApiRouter
{
    /**
     * @param array<int, array{method:string,path:string,controller:class-string,action:string}> $routes
     */
    public function __construct(
        private readonly Container $container,
        private readonly array $routes
    ) {
    }

    public function dispatch(array $request): array
    {
        $method = strtoupper((string) ($request['method'] ?? 'GET'));
        $path = $this->normalizePath((string) ($request['path'] ?? '/'));
        $allowedMethods = [];

        foreach ($this->routes as $route) {
            $matches = [];
            if ($this->match($route['path'], $path, $matches) === false) {
                continue;
            }

            $allowedMethods[] = strtoupper($route['method']);

            if (strtoupper($route['method']) !== $method) {
                continue;
            }

            $controller = $this->container->get($route['controller']);
            $action = $route['action'];

            if (!method_exists($controller, $action)) {
                throw new AppException('Endpoint handler is missing.', 500, 'HANDLER_MISSING');
            }

            return $controller->{$action}(array_merge($request, [
                'route_params' => $matches,
            ]));
        }

        if ($allowedMethods !== []) {
            throw new AppException('Method not allowed.', 405, 'METHOD_NOT_ALLOWED', [
                'allowed_methods' => array_values(array_unique($allowedMethods)),
            ]);
        }

        throw new AppException('Route not found.', 404, 'ROUTE_NOT_FOUND');
    }

    private function normalizePath(string $path): string
    {
        $path = '/' . ltrim($path, '/');
        $path = rtrim($path, '/');

        return $path === '' ? '/' : $path;
    }

    private function match(string $routePath, string $requestPath, array &$matches): bool
    {
        $pattern = $this->compilePattern($routePath);
        if (preg_match($pattern, $requestPath, $matchSet) !== 1) {
            return false;
        }

        foreach ($matchSet as $key => $value) {
            if (is_string($key)) {
                $matches[$key] = $value;
            }
        }

        return true;
    }

    private function compilePattern(string $routePath): string
    {
        $escaped = preg_quote($routePath, '#');
        $pattern = preg_replace('#\\\\\{([a-zA-Z_][a-zA-Z0-9_]*)\\\\\}#', '(?P<$1>[^/]+)', $escaped) ?? $escaped;

        return '#^' . $pattern . '$#';
    }
}
