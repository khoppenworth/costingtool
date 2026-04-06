<?php
use App\Core\Database\DB;
use App\Core\Database\Migration;

return new class implements Migration {
    public function up(DB $db): void
    {
        $db->pdo()->exec("
            CREATE TABLE organizations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(190) NOT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1
            );
            CREATE TABLE facilities (
                id INT AUTO_INCREMENT PRIMARY KEY,
                organization_id INT NOT NULL,
                name VARCHAR(190) NOT NULL,
                facility_type VARCHAR(100) NOT NULL DEFAULT 'facility',
                is_active TINYINT(1) NOT NULL DEFAULT 1
            );
            CREATE TABLE fiscal_years (
                id INT AUTO_INCREMENT PRIMARY KEY,
                label VARCHAR(50) NOT NULL,
                start_date DATE NOT NULL,
                end_date DATE NOT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1
            );
            CREATE TABLE system_settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                setting_key VARCHAR(150) NOT NULL UNIQUE,
                setting_value LONGTEXT NULL
            );
            CREATE TABLE calculation_versions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                version_code VARCHAR(50) NOT NULL UNIQUE,
                description TEXT NULL,
                created_at DATETIME NOT NULL
            );
            CREATE TABLE application_versions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                version_code VARCHAR(50) NOT NULL,
                package_id VARCHAR(100) NULL,
                created_at DATETIME NOT NULL
            );
            CREATE TABLE upgrade_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                package_id VARCHAR(100) NOT NULL,
                from_version VARCHAR(50) NOT NULL,
                to_version VARCHAR(50) NOT NULL,
                operator_user_id INT NULL,
                result_status VARCHAR(30) NOT NULL,
                details_json LONGTEXT NULL,
                created_at DATETIME NOT NULL
            );
            CREATE TABLE oidc_providers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                provider_name VARCHAR(100) NOT NULL,
                issuer_url VARCHAR(255) NULL,
                client_id VARCHAR(255) NULL,
                client_secret VARCHAR(255) NULL,
                scopes VARCHAR(255) NULL,
                is_enabled TINYINT(1) NOT NULL DEFAULT 0
            );
        ");
    }
};
