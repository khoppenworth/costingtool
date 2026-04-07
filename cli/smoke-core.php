<?php
declare(strict_types=1);

use App\Core\Application;
use App\Core\Auth\Middleware\CsrfMiddleware;
use App\Core\Container;
use App\Core\Request;
use App\Core\Upgrade\MaintenanceMode;

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/bootstrap/helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$failures = 0;
$assert = static function (bool $condition, string $message) use (&$failures): void {
    if ($condition) {
        echo "[OK] {$message}\n";
        return;
    }
    echo "[FAIL] {$message}\n";
    $failures++;
};

$_SESSION = [];
$csrf = new CsrfMiddleware();
$container = new Container();
$invalid = $csrf->handle(new Request('POST', '/test', [], ['_csrf' => 'bad']), $container);
$assert($invalid !== null && $invalid->status() === 419, 'CSRF middleware rejects invalid token');

$token = csrf_token();
$valid = $csrf->handle(new Request('POST', '/test', [], ['_csrf' => $token]), $container);
$assert($valid === null, 'CSRF middleware accepts valid token');

MaintenanceMode::disable();
$app = new Application($container);
$normalResponse = $app->handle(new Request('GET', '/healthz'));
$assert($normalResponse->status() === 404, 'Application serves requests when maintenance mode is disabled');

MaintenanceMode::enable();
$maintResponse = $app->handle(new Request('GET', '/assessments'));
$assert($maintResponse->status() === 503, 'Maintenance mode blocks protected routes');
$upgradeResponse = $app->handle(new Request('GET', '/admin/upgrades'));
$assert($upgradeResponse->status() === 404, 'Maintenance mode leaves exempt path routable');
MaintenanceMode::disable();

exit($failures > 0 ? 1 : 0);
