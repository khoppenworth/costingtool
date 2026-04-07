<?php
declare(strict_types=1);

namespace App\Modules\Admin;

use App\Core\Controller;
use App\Core\Response;
use App\Core\Auth\Passwords;

class AdminManagementController extends Controller
{
    public function users(): Response
    {
        $users = $this->db()->all(
            'SELECT u.*, GROUP_CONCAT(DISTINCT r.name ORDER BY r.name SEPARATOR ", ") AS role_names
             FROM users u
             LEFT JOIN user_role_assignments ura ON ura.user_id = u.id
             LEFT JOIN roles r ON r.id = ura.role_id
             GROUP BY u.id
             ORDER BY u.id DESC'
        );
        $roles = $this->db()->all('SELECT * FROM roles ORDER BY name');
        $organizations = $this->db()->all('SELECT * FROM organizations ORDER BY name');
        $userRoleMap = [];
        foreach ($this->db()->all('SELECT user_id, role_id FROM user_role_assignments') as $row) {
            $userRoleMap[(int) $row['user_id']][] = (int) $row['role_id'];
        }
        $userOrganizationMap = [];
        foreach ($this->db()->all('SELECT user_id, organization_id FROM user_organization_scopes WHERE is_active = 1') as $row) {
            $userOrganizationMap[(int) $row['user_id']][] = (int) $row['organization_id'];
        }
        return $this->render('admin.users', compact('users', 'roles', 'organizations', 'userRoleMap', 'userOrganizationMap'));
    }

    public function createUser(): Response
    {
        $username = trim((string) $this->request->input('username'));
        $email = trim((string) $this->request->input('email'));
        $displayName = trim((string) $this->request->input('display_name'));
        $password = (string) $this->request->input('password');
        $roleIds = array_map('intval', (array) ($this->request->post['role_ids'] ?? []));
        $organizationIds = array_map('intval', (array) ($this->request->post['organization_ids'] ?? []));
        $locale = (string) $this->request->input('locale', 'en');

        if ($username === '' || $email === '' || $displayName === '' || $password === '' || $roleIds === []) {
            return new Response(view('admin.users', ['error' => 'Username, email, display name, password and at least one role are required.', 'users' => [], 'roles' => $this->db()->all('SELECT * FROM roles ORDER BY name'), 'organizations' => $this->db()->all('SELECT * FROM organizations ORDER BY name'), 'userRoleMap' => [], 'userOrganizationMap' => []]), 422);
        }

        $now = date('Y-m-d H:i:s');
        $userId = $this->db()->insert('users', [
            'username' => $username,
            'email' => $email,
            'password_hash' => Passwords::hash($password),
            'display_name' => $displayName,
            'is_active' => 1,
            'locale' => in_array($locale, ['en', 'am'], true) ? $locale : 'en',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        foreach ($roleIds as $roleId) {
            $this->db()->insert('user_role_assignments', ['user_id' => $userId, 'role_id' => $roleId]);
        }

        foreach ($organizationIds as $organizationId) {
            $this->db()->insert('user_organization_scopes', ['user_id' => $userId, 'organization_id' => $organizationId, 'is_active' => 1]);
        }

        $this->audit()->log($this->auth()->id(), 'create', 'user', $userId, ['roles' => $roleIds, 'organization_ids' => $organizationIds]);
        return redirect('/admin/users');
    }

    public function updateUser(): Response
    {
        $userId = (int) $this->params['id'];
        $isActive = (int) $this->request->input('is_active', 0);
        $roleIds = array_map('intval', (array) ($this->request->post['role_ids'] ?? []));
        $organizationIds = array_map('intval', (array) ($this->request->post['organization_ids'] ?? []));

        $this->db()->update('users', ['is_active' => $isActive, 'updated_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $userId]);
        $this->db()->statement('DELETE FROM user_role_assignments WHERE user_id = :user_id', ['user_id' => $userId]);
        $this->db()->statement('DELETE FROM user_organization_scopes WHERE user_id = :user_id', ['user_id' => $userId]);

        foreach ($roleIds as $roleId) {
            $this->db()->insert('user_role_assignments', ['user_id' => $userId, 'role_id' => $roleId]);
        }
        foreach ($organizationIds as $organizationId) {
            $this->db()->insert('user_organization_scopes', ['user_id' => $userId, 'organization_id' => $organizationId, 'is_active' => 1]);
        }

        $this->audit()->log($this->auth()->id(), 'update', 'user', $userId, ['is_active' => $isActive, 'roles' => $roleIds, 'organization_ids' => $organizationIds]);
        return redirect('/admin/users');
    }

    public function resetPassword(): Response
    {
        $userId = (int) $this->params['id'];
        $newPassword = (string) $this->request->input('new_password');
        if (strlen($newPassword) < 10) {
            return new Response('Password must be at least 10 characters.', 422);
        }

        $this->db()->update('users', [
            'password_hash' => Passwords::hash($newPassword),
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = :id', ['id' => $userId]);

        $this->audit()->log($this->auth()->id(), 'reset_password', 'user', $userId);
        return redirect('/admin/users');
    }

    public function organizations(): Response
    {
        $organizations = $this->db()->all('SELECT * FROM organizations ORDER BY id DESC');
        $facilities = $this->db()->all(
            'SELECT f.*, o.name AS organization_name
             FROM facilities f
             JOIN organizations o ON o.id = f.organization_id
             ORDER BY f.id DESC'
        );
        return $this->render('admin.organizations', compact('organizations', 'facilities'));
    }

    public function createOrganization(): Response
    {
        $name = trim((string) $this->request->input('name'));
        if ($name === '') {
            return new Response('Organization name is required.', 422);
        }
        $orgId = $this->db()->insert('organizations', ['name' => $name, 'is_active' => 1]);
        $this->audit()->log($this->auth()->id(), 'create', 'organization', $orgId, ['name' => $name]);
        return redirect('/admin/organizations');
    }

    public function updateOrganization(): Response
    {
        $orgId = (int) $this->params['id'];
        $this->db()->update('organizations', ['name' => trim((string) $this->request->input('name')), 'is_active' => (int) $this->request->input('is_active', 0)], 'id = :id', ['id' => $orgId]);
        $this->audit()->log($this->auth()->id(), 'update', 'organization', $orgId);
        return redirect('/admin/organizations');
    }

    public function createFacility(): Response
    {
        $facilityId = $this->db()->insert('facilities', [
            'organization_id' => (int) $this->request->input('organization_id'),
            'name' => trim((string) $this->request->input('name')),
            'facility_type' => trim((string) $this->request->input('facility_type', 'facility')),
            'is_active' => 1,
        ]);
        $this->audit()->log($this->auth()->id(), 'create', 'facility', $facilityId);
        return redirect('/admin/organizations');
    }

    public function updateFacility(): Response
    {
        $facilityId = (int) $this->params['id'];
        $this->db()->update('facilities', [
            'organization_id' => (int) $this->request->input('organization_id'),
            'name' => trim((string) $this->request->input('name')),
            'facility_type' => trim((string) $this->request->input('facility_type', 'facility')),
            'is_active' => (int) $this->request->input('is_active', 0),
        ], 'id = :id', ['id' => $facilityId]);
        $this->audit()->log($this->auth()->id(), 'update', 'facility', $facilityId);
        return redirect('/admin/organizations');
    }

    public function periods(): Response
    {
        $fiscalYears = $this->db()->all('SELECT * FROM fiscal_years ORDER BY start_date DESC');
        $assessmentPeriods = $this->db()->all('SELECT * FROM assessment_periods ORDER BY start_date DESC');
        return $this->render('admin.periods', compact('fiscalYears', 'assessmentPeriods'));
    }

    public function createFiscalYear(): Response
    {
        $id = $this->db()->insert('fiscal_years', [
            'label' => trim((string) $this->request->input('label')),
            'start_date' => (string) $this->request->input('start_date'),
            'end_date' => (string) $this->request->input('end_date'),
            'is_active' => 1,
        ]);
        $this->audit()->log($this->auth()->id(), 'create', 'fiscal_year', $id);
        return redirect('/admin/periods');
    }

    public function updateFiscalYear(): Response
    {
        $id = (int) $this->params['id'];
        $this->db()->update('fiscal_years', [
            'label' => trim((string) $this->request->input('label')),
            'start_date' => (string) $this->request->input('start_date'),
            'end_date' => (string) $this->request->input('end_date'),
            'is_active' => (int) $this->request->input('is_active', 0),
        ], 'id = :id', ['id' => $id]);
        $this->audit()->log($this->auth()->id(), 'update', 'fiscal_year', $id);
        return redirect('/admin/periods');
    }

    public function createAssessmentPeriod(): Response
    {
        $id = $this->db()->insert('assessment_periods', [
            'period_code' => trim((string) $this->request->input('period_code')),
            'label' => trim((string) $this->request->input('label')),
            'start_date' => (string) $this->request->input('start_date'),
            'end_date' => (string) $this->request->input('end_date'),
            'is_active' => 1,
        ]);
        $this->audit()->log($this->auth()->id(), 'create', 'assessment_period', $id);
        return redirect('/admin/periods');
    }

    public function updateAssessmentPeriod(): Response
    {
        $id = (int) $this->params['id'];
        $this->db()->update('assessment_periods', [
            'period_code' => trim((string) $this->request->input('period_code')),
            'label' => trim((string) $this->request->input('label')),
            'start_date' => (string) $this->request->input('start_date'),
            'end_date' => (string) $this->request->input('end_date'),
            'is_active' => (int) $this->request->input('is_active', 0),
        ], 'id = :id', ['id' => $id]);
        $this->audit()->log($this->auth()->id(), 'update', 'assessment_period', $id);
        return redirect('/admin/periods');
    }

    public function settings(): Response
    {
        $settings = $this->db()->all('SELECT * FROM system_settings ORDER BY setting_key');
        return $this->render('admin.settings', compact('settings'));
    }

    public function saveSettings(): Response
    {
        $keys = ['default_locale', 'country_name', 'currency_code', 'support_email'];
        foreach ($keys as $key) {
            $value = (string) $this->request->input($key, '');
            $existing = $this->db()->one('SELECT id FROM system_settings WHERE setting_key = :setting_key', ['setting_key' => $key]);
            if ($existing) {
                $this->db()->update('system_settings', ['setting_value' => $value], 'id = :id', ['id' => $existing['id']]);
            } else {
                $this->db()->insert('system_settings', ['setting_key' => $key, 'setting_value' => $value]);
            }
        }

        $this->audit()->log($this->auth()->id(), 'update', 'system_settings', null, ['keys' => $keys]);
        return redirect('/admin/settings');
    }
}
