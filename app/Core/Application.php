<?php
declare(strict_types=1);

namespace App\Core;

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
        try {
            return $this->router->dispatch($request, $this->container);
        } catch (\Throwable $e) {
            if (config('app.debug')) {
                return new Response('<pre>' . e($e->getMessage() . "\n\n" . $e->getTraceAsString()) . '</pre>', 500);
            }
            return new Response('Application error', 500);
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
            session_set_cookie_params(config('auth.session_lifetime', 7200));
            session_start();
        }
    }
}
