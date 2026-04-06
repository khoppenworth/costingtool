<?php
declare(strict_types=1);

namespace App\Core\Auth\Middleware;

use App\Core\Auth\Auth;
use App\Core\Auth\PermissionService;
use App\Core\Container;
use App\Core\Request;
use App\Core\Response;

class PermissionMiddleware
{
    public function __construct(private string $permission)
    {
    }

    public function handle(Request $request, Container $container, array $params = []): ?Response
    {
        $auth = $container->get(Auth::class);
        if (!$auth->check()) {
            return new Response('', 302, ['Location' => '/login']);
        }
        if (!$container->get(PermissionService::class)->has($auth->id(), $this->permission)) {
            return new Response('Forbidden', 403);
        }
        return null;
    }
}
