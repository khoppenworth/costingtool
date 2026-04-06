<?php
declare(strict_types=1);

use App\Core\Auth\Middleware\AuthMiddleware;
use App\Core\Auth\Middleware\PermissionMiddleware;
use App\Modules\Auth\AuthController;
use App\Modules\Admin\AdminController;
use App\Modules\Admin\UpgradeController;
use App\Modules\Assessments\AssessmentController;
use App\Modules\SampleInformation\SampleInformationController;
use App\Modules\ExchangeInflation\ExchangeInflationController;

$app->router()->get('/', [AssessmentController::class, 'index'], [AuthMiddleware::class]);

$app->router()->get('/login', [AuthController::class, 'showLogin']);
$app->router()->post('/login', [AuthController::class, 'login']);
$app->router()->post('/logout', [AuthController::class, 'logout'], [AuthMiddleware::class]);

$app->router()->get('/admin', [AdminController::class, 'dashboard'], [AuthMiddleware::class, [PermissionMiddleware::class, 'admin.settings.manage']]);
$app->router()->get('/admin/upgrades', [UpgradeController::class, 'index'], [AuthMiddleware::class, [PermissionMiddleware::class, 'admin.upgrades.run']]);
$app->router()->post('/admin/upgrades', [UpgradeController::class, 'run'], [AuthMiddleware::class, [PermissionMiddleware::class, 'admin.upgrades.run']]);

$app->router()->get('/assessments', [AssessmentController::class, 'index'], [AuthMiddleware::class]);
$app->router()->get('/assessments/create', [AssessmentController::class, 'create'], [AuthMiddleware::class, [PermissionMiddleware::class, 'assessments.create']]);
$app->router()->post('/assessments', [AssessmentController::class, 'store'], [AuthMiddleware::class, [PermissionMiddleware::class, 'assessments.create']]);
$app->router()->get('/assessments/{id}', [AssessmentController::class, 'show'], [AuthMiddleware::class, [PermissionMiddleware::class, 'assessments.view']]);
$app->router()->post('/assessments/{id}/submit', [AssessmentController::class, 'submit'], [AuthMiddleware::class, [PermissionMiddleware::class, 'assessments.submit']]);
$app->router()->post('/assessments/{id}/approve', [AssessmentController::class, 'approve'], [AuthMiddleware::class, [PermissionMiddleware::class, 'assessments.approve']]);
$app->router()->post('/assessments/{id}/lock', [AssessmentController::class, 'lock'], [AuthMiddleware::class, [PermissionMiddleware::class, 'assessments.lock']]);

$app->router()->get('/assessments/{id}/sample-information', [SampleInformationController::class, 'edit'], [AuthMiddleware::class, [PermissionMiddleware::class, 'assessments.edit']]);
$app->router()->post('/assessments/{id}/sample-information', [SampleInformationController::class, 'update'], [AuthMiddleware::class, [PermissionMiddleware::class, 'assessments.edit']]);

$app->router()->get('/assessments/{id}/exchange-inflation', [ExchangeInflationController::class, 'edit'], [AuthMiddleware::class, [PermissionMiddleware::class, 'assessments.edit']]);
$app->router()->post('/assessments/{id}/exchange-inflation', [ExchangeInflationController::class, 'update'], [AuthMiddleware::class, [PermissionMiddleware::class, 'assessments.edit']]);
