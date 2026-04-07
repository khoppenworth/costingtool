<?php
declare(strict_types=1);

namespace App\Core\Auth\Middleware;

use App\Core\Auth\Auth;
use App\Core\Auth\ScopeService;
use App\Core\Container;
use App\Core\Request;
use App\Core\Response;

class AssessmentScopeMiddleware
{
    public function handle(Request $request, Container $container, array $params = []): ?Response
    {
        $userId = $container->get(Auth::class)->id();
        $assessmentId = isset($params['id']) ? (int) $params['id'] : 0;
        if ($userId === null || $assessmentId <= 0) {
            return new Response('Forbidden', 403);
        }

        if (!$container->get(ScopeService::class)->canAccessAssessment($userId, $assessmentId)) {
            return new Response('Forbidden', 403);
        }
        return null;
    }
}
