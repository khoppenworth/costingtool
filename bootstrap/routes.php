<?php
declare(strict_types=1);

use App\Core\Auth\Middleware\AuthMiddleware;
use App\Core\Auth\Middleware\CsrfMiddleware;
use App\Core\Auth\Middleware\PermissionMiddleware;
use App\Modules\Auth\AuthController;
use App\Modules\Admin\AdminController;
use App\Modules\Admin\AdminManagementController;
use App\Modules\Admin\UpgradeController;
use App\Modules\Assessments\AssessmentController;
use App\Modules\SampleInformation\SampleInformationController;
use App\Modules\ExchangeInflation\ExchangeInflationController;

$app->router()->get('/', [AssessmentController::class, 'index'], [AuthMiddleware::class]);

$app->router()->get('/login', [AuthController::class, 'showLogin']);
$app->router()->post('/login', [AuthController::class, 'login'], [CsrfMiddleware::class]);
$app->router()->post('/logout', [AuthController::class, 'logout'], [AuthMiddleware::class, CsrfMiddleware::class]);

$app->router()->get('/admin', [AdminController::class, 'dashboard'], [AuthMiddleware::class, [PermissionMiddleware::class, 'admin.settings.manage']]);
$app->router()->get('/admin/upgrades', [UpgradeController::class, 'index'], [AuthMiddleware::class, [PermissionMiddleware::class, 'admin.upgrades.run']]);
$app->router()->post('/admin/upgrades', [UpgradeController::class, 'run'], [AuthMiddleware::class, [PermissionMiddleware::class, 'admin.upgrades.run'], CsrfMiddleware::class]);
$app->router()->get('/admin/users', [AdminManagementController::class, 'users'], [AuthMiddleware::class, [PermissionMiddleware::class, 'admin.users.manage']]);
$app->router()->post('/admin/users', [AdminManagementController::class, 'createUser'], [AuthMiddleware::class, [PermissionMiddleware::class, 'admin.users.manage'], CsrfMiddleware::class]);
$app->router()->post('/admin/users/{id}', [AdminManagementController::class, 'updateUser'], [AuthMiddleware::class, [PermissionMiddleware::class, 'admin.users.manage'], CsrfMiddleware::class]);
$app->router()->post('/admin/users/{id}/reset-password', [AdminManagementController::class, 'resetPassword'], [AuthMiddleware::class, [PermissionMiddleware::class, 'admin.users.manage'], CsrfMiddleware::class]);
$app->router()->get('/admin/organizations', [AdminManagementController::class, 'organizations'], [AuthMiddleware::class, [PermissionMiddleware::class, 'admin.settings.manage']]);
$app->router()->post('/admin/organizations', [AdminManagementController::class, 'createOrganization'], [AuthMiddleware::class, [PermissionMiddleware::class, 'admin.settings.manage'], CsrfMiddleware::class]);
$app->router()->post('/admin/organizations/{id}', [AdminManagementController::class, 'updateOrganization'], [AuthMiddleware::class, [PermissionMiddleware::class, 'admin.settings.manage'], CsrfMiddleware::class]);
$app->router()->post('/admin/facilities', [AdminManagementController::class, 'createFacility'], [AuthMiddleware::class, [PermissionMiddleware::class, 'admin.settings.manage'], CsrfMiddleware::class]);
$app->router()->post('/admin/facilities/{id}', [AdminManagementController::class, 'updateFacility'], [AuthMiddleware::class, [PermissionMiddleware::class, 'admin.settings.manage'], CsrfMiddleware::class]);
$app->router()->get('/admin/periods', [AdminManagementController::class, 'periods'], [AuthMiddleware::class, [PermissionMiddleware::class, 'admin.settings.manage']]);
$app->router()->post('/admin/fiscal-years', [AdminManagementController::class, 'createFiscalYear'], [AuthMiddleware::class, [PermissionMiddleware::class, 'admin.settings.manage'], CsrfMiddleware::class]);
$app->router()->post('/admin/fiscal-years/{id}', [AdminManagementController::class, 'updateFiscalYear'], [AuthMiddleware::class, [PermissionMiddleware::class, 'admin.settings.manage'], CsrfMiddleware::class]);
$app->router()->post('/admin/assessment-periods', [AdminManagementController::class, 'createAssessmentPeriod'], [AuthMiddleware::class, [PermissionMiddleware::class, 'admin.settings.manage'], CsrfMiddleware::class]);
$app->router()->post('/admin/assessment-periods/{id}', [AdminManagementController::class, 'updateAssessmentPeriod'], [AuthMiddleware::class, [PermissionMiddleware::class, 'admin.settings.manage'], CsrfMiddleware::class]);
$app->router()->get('/admin/settings', [AdminManagementController::class, 'settings'], [AuthMiddleware::class, [PermissionMiddleware::class, 'admin.settings.manage']]);
$app->router()->post('/admin/settings', [AdminManagementController::class, 'saveSettings'], [AuthMiddleware::class, [PermissionMiddleware::class, 'admin.settings.manage'], CsrfMiddleware::class]);

$app->router()->get('/assessments', [AssessmentController::class, 'index'], [AuthMiddleware::class]);
$app->router()->get('/assessments/create', [AssessmentController::class, 'create'], [AuthMiddleware::class, [PermissionMiddleware::class, 'assessments.create']]);
$app->router()->post('/assessments', [AssessmentController::class, 'store'], [AuthMiddleware::class, [PermissionMiddleware::class, 'assessments.create'], CsrfMiddleware::class]);
$app->router()->get('/assessments/{id}', [AssessmentController::class, 'show'], [AuthMiddleware::class, [PermissionMiddleware::class, 'assessments.view'], \App\Core\Auth\Middleware\AssessmentScopeMiddleware::class]);
$app->router()->post('/assessments/{id}/submit', [AssessmentController::class, 'submit'], [AuthMiddleware::class, [PermissionMiddleware::class, 'assessments.submit'], \App\Core\Auth\Middleware\AssessmentScopeMiddleware::class, CsrfMiddleware::class]);
$app->router()->post('/assessments/{id}/review', [AssessmentController::class, 'review'], [AuthMiddleware::class, [PermissionMiddleware::class, 'assessments.review'], \App\Core\Auth\Middleware\AssessmentScopeMiddleware::class, CsrfMiddleware::class]);
$app->router()->post('/assessments/{id}/return', [AssessmentController::class, 'returnForCorrection'], [AuthMiddleware::class, [PermissionMiddleware::class, 'assessments.review'], \App\Core\Auth\Middleware\AssessmentScopeMiddleware::class, CsrfMiddleware::class]);
$app->router()->post('/assessments/{id}/approve', [AssessmentController::class, 'approve'], [AuthMiddleware::class, [PermissionMiddleware::class, 'assessments.approve'], \App\Core\Auth\Middleware\AssessmentScopeMiddleware::class, CsrfMiddleware::class]);
$app->router()->post('/assessments/{id}/lock', [AssessmentController::class, 'lock'], [AuthMiddleware::class, [PermissionMiddleware::class, 'assessments.lock'], \App\Core\Auth\Middleware\AssessmentScopeMiddleware::class, CsrfMiddleware::class]);
$app->router()->post('/assessments/{id}/unlock', [AssessmentController::class, 'unlock'], [AuthMiddleware::class, [PermissionMiddleware::class, 'assessments.unlock'], \App\Core\Auth\Middleware\AssessmentScopeMiddleware::class, CsrfMiddleware::class]);
$app->router()->get('/assessments/{id}/revisions', [AssessmentController::class, 'revisions'], [AuthMiddleware::class, [PermissionMiddleware::class, 'assessments.view'], \App\Core\Auth\Middleware\AssessmentScopeMiddleware::class]);
$app->router()->get('/assessments/{id}/revisions/compare', [AssessmentController::class, 'compareRevisions'], [AuthMiddleware::class, [PermissionMiddleware::class, 'assessments.view'], \App\Core\Auth\Middleware\AssessmentScopeMiddleware::class]);

$app->router()->get('/assessments/{id}/sample-information', [SampleInformationController::class, 'edit'], [AuthMiddleware::class, [PermissionMiddleware::class, 'assessments.edit'], \App\Core\Auth\Middleware\AssessmentScopeMiddleware::class]);
$app->router()->post('/assessments/{id}/sample-information', [SampleInformationController::class, 'update'], [AuthMiddleware::class, [PermissionMiddleware::class, 'assessments.edit'], \App\Core\Auth\Middleware\AssessmentScopeMiddleware::class, CsrfMiddleware::class]);

$app->router()->get('/assessments/{id}/exchange-inflation', [ExchangeInflationController::class, 'edit'], [AuthMiddleware::class, [PermissionMiddleware::class, 'assessments.edit'], \App\Core\Auth\Middleware\AssessmentScopeMiddleware::class]);
$app->router()->post('/assessments/{id}/exchange-inflation', [ExchangeInflationController::class, 'update'], [AuthMiddleware::class, [PermissionMiddleware::class, 'assessments.edit'], \App\Core\Auth\Middleware\AssessmentScopeMiddleware::class, CsrfMiddleware::class]);
