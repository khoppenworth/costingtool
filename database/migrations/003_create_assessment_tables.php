<?php
use App\Core\Database\DB;
use App\Core\Database\Migration;

return new class implements Migration {
    public function up(DB $db): void
    {
        $db->pdo()->exec("
            CREATE TABLE assessments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                organization_id INT NOT NULL,
                fiscal_year_id INT NOT NULL,
                assessment_period VARCHAR(100) NOT NULL,
                assumptions_notes LONGTEXT NULL,
                status VARCHAR(30) NOT NULL DEFAULT 'draft',
                calculation_version_id INT NOT NULL,
                created_by INT NOT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL
            );
            CREATE TABLE assessment_revisions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                assessment_id INT NOT NULL,
                revision_number INT NOT NULL,
                reason TEXT NULL,
                created_by INT NOT NULL,
                created_at DATETIME NOT NULL
            );
            CREATE TABLE workflow_history (
                id INT AUTO_INCREMENT PRIMARY KEY,
                assessment_id INT NOT NULL,
                from_status VARCHAR(30) NOT NULL,
                to_status VARCHAR(30) NOT NULL,
                acted_by INT NOT NULL,
                comments TEXT NULL,
                created_at DATETIME NOT NULL
            );
            CREATE TABLE module_statuses (
                id INT AUTO_INCREMENT PRIMARY KEY,
                assessment_id INT NOT NULL,
                module_key VARCHAR(100) NOT NULL,
                status VARCHAR(50) NOT NULL,
                updated_at DATETIME NOT NULL
            );
            CREATE TABLE validation_results (
                id INT AUTO_INCREMENT PRIMARY KEY,
                assessment_id INT NOT NULL,
                module_key VARCHAR(100) NOT NULL,
                severity VARCHAR(20) NOT NULL,
                field_name VARCHAR(100) NULL,
                message TEXT NOT NULL,
                created_at DATETIME NOT NULL
            );
            CREATE TABLE change_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                assessment_id INT NOT NULL,
                module_name VARCHAR(100) NOT NULL,
                field_name VARCHAR(100) NOT NULL,
                old_value LONGTEXT NULL,
                new_value LONGTEXT NULL,
                user_id INT NOT NULL,
                revision_number INT NOT NULL DEFAULT 1,
                reason TEXT NULL,
                created_at DATETIME NOT NULL
            );
        ");
    }
};
