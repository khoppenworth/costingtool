<?php
declare(strict_types=1);

if (!function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        $base = dirname(__DIR__);
        return $path ? $base . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : $base;
    }
}

if (!function_exists('storage_path')) {
    function storage_path(string $path = ''): string
    {
        return base_path('storage' . ($path ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : ''));
    }
}

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        static $loaded = false;
        if (!$loaded) {
            $envFile = base_path('.env');
            if (file_exists($envFile)) {
                foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                    if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) {
                        continue;
                    }
                    [$k, $v] = explode('=', $line, 2);
                    $_ENV[trim($k)] = trim($v);
                }
            }
            $loaded = true;
        }
        return $_ENV[$key] ?? $default;
    }
}

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        static $cache = [];
        [$file, $item] = array_pad(explode('.', $key, 2), 2, null);
        if (!isset($cache[$file])) {
            $path = base_path('config/' . $file . '.php');
            $cache[$file] = file_exists($path) ? require $path : [];
        }
        if ($item === null) {
            return $cache[$file];
        }
        $segments = explode('.', $item);
        $value = $cache[$file];
        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        return $value;
    }
}

if (!function_exists('app')) {
    function app(?string $id = null): mixed
    {
        global $app;
        return $id ? $app->container()->get($id) : $app;
    }
}

if (!function_exists('view')) {
    function view(string $template, array $data = []): string
    {
        return app()->view($template, $data);
    }
}

if (!function_exists('redirect')) {
    function redirect(string $location): \App\Core\Response
    {
        return new \App\Core\Response('', 302, ['Location' => $location]);
    }
}

if (!function_exists('e')) {
    function e(string|null $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('__')) {
    function __(string $key, array $replace = []): string
    {
        return app(\App\Core\I18n\Translator::class)->get($key, $replace);
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return \App\Core\Auth\Csrf::token();
    }
}

if (!function_exists('old')) {
    function old(string $key, mixed $default = null): mixed
    {
        return $_SESSION['_old'][$key] ?? $default;
    }
}
