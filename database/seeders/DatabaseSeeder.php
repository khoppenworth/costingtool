<?php
use App\Core\Database\DB;
use App\Core\Database\Seeder;
use App\Core\Auth\Passwords;

return new class implements Seeder {
    public function run(DB $db): void
    {
        foreach (['Super Admin', 'Organization Admin', 'Data Entry User', 'Reviewer', 'Read-only Analyst'] as $role) {
            $db->insert('roles', ['name' => $role]);
        }

        foreach (config('permissions.permissions', []) as $permission) {
            $db->insert('permissions', ['permission_key' => $permission]);
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
                $db->insert('role_permissions', ['role_id' => $roleMap[$role], 'permission_id' => $permMap[$perm]]);
            }
        };

        $grant('Super Admin', config('permissions.permissions'));
        $grant('Organization Admin', ['assessments.view','assessments.create','assessments.edit','assessments.submit','exports.csv','reports.view']);
        $grant('Data Entry User', ['assessments.view','assessments.create','assessments.edit','assessments.submit']);
        $grant('Reviewer', ['assessments.view','assessments.review','assessments.approve','assessments.lock','reports.view']);
        $grant('Read-only Analyst', ['assessments.view','reports.view']);

        $orgId = $db->insert('organizations', ['name' => 'EPSS', 'is_active' => 1]);
        $db->insert('facilities', ['organization_id' => $orgId, 'name' => 'Central Unit', 'facility_type' => 'central_unit', 'is_active' => 1]);
        $db->insert('fiscal_years', ['label' => 'FY2026', 'start_date' => '2025-07-01', 'end_date' => '2026-06-30', 'is_active' => 1]);
        $db->insert('calculation_versions', ['version_code' => '1.0.0', 'description' => 'Baseline engine', 'created_at' => date('Y-m-d H:i:s')]);
        $db->insert('application_versions', ['version_code' => '0.1.0', 'package_id' => 'baseline', 'created_at' => date('Y-m-d H:i:s')]);
        $db->insert('system_settings', ['setting_key' => 'default_locale', 'setting_value' => 'en']);
        $db->insert('system_settings', ['setting_key' => 'country_name', 'setting_value' => 'Ethiopia']);

        $adminId = $db->insert('users', [
            'username' => 'admin',
            'email' => 'admin@example.org',
            'password_hash' => Passwords::hash('ChangeMe123!'),
            'display_name' => 'System Administrator',
            'is_active' => 1,
            'locale' => 'en',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $db->insert('user_role_assignments', ['user_id' => $adminId, 'role_id' => $roleMap['Super Admin']]);

        $db->insert('glossary_definitions', [
            'term_key' => 'cash_to_cash_cycle',
            'label_en' => 'Cash-to-cash cycle',
            'label_am' => 'ካሽ-ቱ-ካሽ ዑደት',
            'description_en' => 'Time between paying suppliers and receiving cash from customers.',
            'description_am' => 'ከአቅራቢዎች ክፍያ እስከ ከደንበኞች ገንዘብ መቀበል ድረስ የሚወስደው ጊዜ።',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
};
