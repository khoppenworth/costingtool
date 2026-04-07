<?php
declare(strict_types=1);

use App\Core\Application;
use App\Core\Container;
use App\Core\Database\DB;
use App\Core\I18n\Translator;
use App\Core\Auth\Auth;
use App\Core\Auth\PermissionService;
use App\Core\Auth\ScopeService;
use App\Core\Audit\AuditLogger;
use App\Core\Audit\ChangeTracker;
use App\Core\Upgrade\ManifestValidator;
use App\Core\Upgrade\UpgradeManager;
use App\Core\Support\ErrorHandler;
use App\Core\Workflow\RevisionComparisonService;

require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/bootstrap/helpers.php';

date_default_timezone_set(env('APP_TIMEZONE', 'UTC'));
ErrorHandler::register();

$container = new Container();

$container->singleton(DB::class, fn () => DB::make(config('database')));
$container->singleton(Translator::class, fn () => new Translator(base_path('app/lang'), env('APP_LOCALE', 'en'), env('APP_FALLBACK_LOCALE', 'en')));
$container->singleton(Auth::class, fn (Container $c) => new Auth($c->get(DB::class)));
$container->singleton(PermissionService::class, fn (Container $c) => new PermissionService($c->get(DB::class)));
$container->singleton(ScopeService::class, fn (Container $c) => new ScopeService($c->get(DB::class)));
$container->singleton(AuditLogger::class, fn (Container $c) => new AuditLogger($c->get(DB::class)));
$container->singleton(ChangeTracker::class, fn (Container $c) => new ChangeTracker($c->get(DB::class)));
$container->singleton(RevisionComparisonService::class, fn (Container $c) => new RevisionComparisonService($c->get(DB::class)));
$container->singleton(ManifestValidator::class, fn () => new ManifestValidator());
$container->singleton(UpgradeManager::class, fn (Container $c) => new UpgradeManager($c->get(DB::class), $c->get(ManifestValidator::class)));

$app = new Application($container);

require base_path('bootstrap/routes.php');

return $app;
