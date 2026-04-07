<?php
declare(strict_types=1);

namespace App\Core;

use App\Core\Support\ErrorHandler;
use App\Core\Upgrade\MaintenanceMode;

class Application
{
    private Router $router;

    public function __construct(private Container $container)
    {
        $this->router = new Router();
        $this->bootstrapSession();
    }

    public function container(): Container
    {
        return $this->container;
    }

    public function router(): Router
    {
        return $this->router;
    }

    public function handle(Request $request): Response
    {
        if ($this->inMaintenanceMode($request)) {
            return new Response('Service temporarily unavailable for maintenance.', 503, ['Retry-After' => '300']);
        }

        try {
            return $this->router->dispatch($request, $this->container);
        } catch (\Throwable $e) {
            ErrorHandler::report($e);
            return config('app.debug')
                ? new Response('<pre>' . e($e->getMessage() . "\n\n" . $e->getTraceAsString()) . '</pre>', 500)
                : new Response('Application error', 500);
        }
    }

    public function view(string $template, array $data = []): string
    {
        $viewFile = base_path('resources/views/' . str_replace('.', '/', $template) . '.php');
        extract($data);
        ob_start();
        require $viewFile;
        return (string) ob_get_clean();
    }

    private function bootstrapSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name(config('auth.session_name', 'tcsa_session'));
            session_set_cookie_params([
                'lifetime' => (int) config('auth.session_lifetime', 7200),
                'path' => '/',
                'domain' => '',
                'secure' => (bool) config('auth.secure_cookie', false),
                'httponly' => true,
                'samesite' => (string) config('auth.same_site', 'Lax'),
            ]);
            ini_set('session.use_strict_mode', '1');
            ini_set('session.cookie_httponly', '1');
            session_start();
        }
    }

    private function inMaintenanceMode(Request $request): bool
    {
        if (!MaintenanceMode::enabled()) {
            return false;
        }

        $exemptPaths = config('app.maintenance_exempt_paths', ['/admin/upgrades']);
        return !in_array($request->path, $exemptPaths, true);
    }
}
