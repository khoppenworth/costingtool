<?php
declare(strict_types=1);

namespace App\Core\Auth\Middleware;

use App\Core\Container;
use App\Core\Request;
use App\Core\Response;
use App\Core\Auth\Auth;

class AuthMiddleware
{
    public function handle(Request $request, Container $container, array $params = []): ?Response
    {
        if (!$container->get(Auth::class)->check()) {
            return new Response('', 302, ['Location' => '/login']);
        }
        return null;
    }
}
