<?php
declare(strict_types=1);

namespace App\Core;

use Closure;

class Router
{
    private array $routes = [];

    public function get(string $path, array|Closure $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, array|Closure $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    private function add(string $method, string $path, array|Closure $handler, array $middleware): void
    {
        $this->routes[] = compact('method', 'path', 'handler', 'middleware');
    }

    public function dispatch(Request $request, Container $container): Response
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $request->method) {
                continue;
            }

            $pattern = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $route['path']);
            if (!preg_match('#^' . $pattern . '$#', $request->path, $matches)) {
                continue;
            }

            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

            foreach ($route['middleware'] as $middleware) {
                if (is_array($middleware)) {
                    [$class, $argument] = $middleware;
                    $instance = new $class($argument);
                } else {
                    $instance = new $middleware();
                }
                $maybeResponse = $instance->handle($request, $container, $params);
                if ($maybeResponse instanceof Response) {
                    return $maybeResponse;
                }
            }

            $handler = $route['handler'];
            if ($handler instanceof Closure) {
                return $handler($request, $params);
            }

            [$class, $method] = $handler;
            $controller = new $class($container, $request, $params);
            return $controller->$method();
        }

        return new Response('Not Found', 404);
    }
}
