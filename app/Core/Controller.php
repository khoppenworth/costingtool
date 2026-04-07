<?php
declare(strict_types=1);

namespace App\Core;

class Controller
{
    public function __construct(
        protected Container $container,
        protected Request $request,
        protected array $params = []
    ) {
    }

    protected function db(): \App\Core\Database\DB
    {
        return $this->container->get(\App\Core\Database\DB::class);
    }

    protected function auth(): \App\Core\Auth\Auth
    {
        return $this->container->get(\App\Core\Auth\Auth::class);
    }

    protected function permissions(): \App\Core\Auth\PermissionService
    {
        return $this->container->get(\App\Core\Auth\PermissionService::class);
    }

    protected function audit(): \App\Core\Audit\AuditLogger
    {
        return $this->container->get(\App\Core\Audit\AuditLogger::class);
    }

    protected function render(string $template, array $data = []): Response
    {
        return new Response(app()->view($template, $data));
    }

    protected function denyUnless(string $permission): ?Response
    {
        $userId = $this->auth()->id();
        if ($userId === null) {
            return new Response('', 302, ['Location' => '/login']);
        }

        if (!$this->permissions()->has($userId, $permission)) {
            return new Response('Forbidden', 403);
        }

        return null;
    }
}
