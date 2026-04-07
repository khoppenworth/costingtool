<?php
use App\Core\Database\DB;
use App\Core\Database\Seeder;
use App\Core\Auth\Passwords;

return new class implements Seeder {
    public function run(DB $db): void
    {
        $now = date('Y-m-d H:i:s');

        $exists = static fn (string $table, string $column, mixed $value): bool =>
            $db->one("SELECT id FROM {$table} WHERE {$column} = :value LIMIT 1", ['value' => $value]) !== null;

        foreach (['Super Admin', 'Organization Admin', 'Data Entry User', 'Reviewer', 'Read-only Analyst'] as $role) {
            if (!$exists('roles', 'name', $role)) {
                $db->insert('roles', ['name' => $role]);
            }
        }

        foreach (config('permissions.permissions', []) as $permission) {
            if (!$exists('permissions', 'permission_key', $permission)) {
                $db->insert('permissions', ['permission_key' => $permission]);
            }
        }

        $roleMap = [];
        foreach ($db->all('SELECT * FROM roles') as $row) {
            $roleMap[$row['name']] = $row['id'];
        }
        $permMap = [];
        foreach ($db->all('SELECT * FROM permissions') as $row) {
            $permMap[$row['permission_key']] = $row['id'];
        }

        $grant = function(string $role, array $perms) use ($db, $roleMap, $permMap) {
            foreach ($perms as $perm) {
                $alreadyGranted = $db->one(
                    'SELECT 1 FROM role_permissions WHERE role_id = :role_id AND permission_id = :permission_id',
                    ['role_id' => $roleMap[$role], 'permission_id' => $permMap[$perm]]
                );
                if (!$alreadyGranted) {
                    $db->insert('role_permissions', ['role_id' => $roleMap[$role], 'permission_id' => $permMap[$perm]]);
                }
            }
        };

        $grant('Super Admin', config('permissions.permissions'));
        $grant('Organization Admin', ['assessments.view','assessments.create','assessments.edit','assessments.submit','exports.csv','reports.view']);
        $grant('Data Entry User', ['assessments.view','assessments.create','assessments.edit','assessments.submit']);
        $grant('Reviewer', ['assessments.view','assessments.review','assessments.approve','assessments.lock','reports.view']);
        $grant('Read-only Analyst', ['assessments.view','reports.view']);

        $org = $db->one('SELECT id FROM organizations WHERE name = :name LIMIT 1', ['name' => 'EPSS']);
        $orgId = $org['id'] ?? $db->insert('organizations', ['name' => 'EPSS', 'is_active' => 1]);

        if (!$db->one('SELECT id FROM facilities WHERE organization_id = :organization_id AND name = :name LIMIT 1', ['organization_id' => $orgId, 'name' => 'Central Unit'])) {
            $db->insert('facilities', ['organization_id' => $orgId, 'name' => 'Central Unit', 'facility_type' => 'central_unit', 'is_active' => 1]);
        }
        if (!$exists('fiscal_years', 'label', 'FY2026')) {
            $db->insert('fiscal_years', ['label' => 'FY2026', 'start_date' => '2025-07-01', 'end_date' => '2026-06-30', 'is_active' => 1]);
        }
        if (!$exists('calculation_versions', 'version_code', '1.0.0')) {
            $db->insert('calculation_versions', ['version_code' => '1.0.0', 'description' => 'Baseline engine', 'created_at' => $now]);
        }
        if (!$db->one('SELECT id FROM application_versions WHERE version_code = :version_code AND package_id = :package_id LIMIT 1', ['version_code' => '0.1.0', 'package_id' => 'baseline'])) {
            $db->insert('application_versions', ['version_code' => '0.1.0', 'package_id' => 'baseline', 'created_at' => $now]);
        }
        if (!$exists('system_settings', 'setting_key', 'default_locale')) {
            $db->insert('system_settings', ['setting_key' => 'default_locale', 'setting_value' => 'en']);
        }
        if (!$exists('system_settings', 'setting_key', 'country_name')) {
            $db->insert('system_settings', ['setting_key' => 'country_name', 'setting_value' => 'Ethiopia']);
        }

        $admin = $db->one('SELECT id FROM users WHERE username = :username LIMIT 1', ['username' => 'admin']);
        $adminId = $admin['id'] ?? $db->insert('users', [
            'username' => 'admin',
            'email' => 'admin@example.org',
            'password_hash' => Passwords::hash('ChangeMe123!'),
            'display_name' => 'System Administrator',
            'is_active' => 1,
            'locale' => 'en',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        if (!$db->one('SELECT 1 FROM user_role_assignments WHERE user_id = :user_id AND role_id = :role_id', ['user_id' => $adminId, 'role_id' => $roleMap['Super Admin']])) {
            $db->insert('user_role_assignments', ['user_id' => $adminId, 'role_id' => $roleMap['Super Admin']]);
        }

        if (!$exists('glossary_definitions', 'term_key', 'cash_to_cash_cycle')) {
            $db->insert('glossary_definitions', [
                'term_key' => 'cash_to_cash_cycle',
                'label_en' => 'Cash-to-cash cycle',
                'label_am' => 'ካሽ-ቱ-ካሽ ዑደት',
                'description_en' => 'Time between paying suppliers and receiving cash from customers.',
                'description_am' => 'ከአቅራቢዎች ክፍያ እስከ ከደንበኞች ገንዘብ መቀበል ድረስ የሚወስደው ጊዜ።',
                'updated_at' => $now,
            ]);
        }
    }
};
