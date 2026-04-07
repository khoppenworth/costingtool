<?php
declare(strict_types=1);

namespace App\Core\Auth\Middleware;

use App\Core\Auth\Csrf;
use App\Core\Container;
use App\Core\Request;
use App\Core\Response;
use RuntimeException;

class CsrfMiddleware
{
    public function handle(Request $request, Container $container, array $params = []): ?Response
    {
        try {
            Csrf::validate((string) $request->input('_csrf'));
            return null;
        } catch (RuntimeException $e) {
            return new Response('Invalid CSRF token.', 419);
        }
    }
}
