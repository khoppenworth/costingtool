<?php
use App\Core\Database\DB;
use App\Core\Database\Migration;

return new class implements Migration {
    public function up(DB $db): void
    {
        $db->pdo()->exec("
            CREATE TABLE roles (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL UNIQUE
            );
            CREATE TABLE permissions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                permission_key VARCHAR(150) NOT NULL UNIQUE
            );
            CREATE TABLE role_permissions (
                role_id INT NOT NULL,
                permission_id INT NOT NULL,
                PRIMARY KEY (role_id, permission_id)
            );
            CREATE TABLE users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(100) NOT NULL UNIQUE,
                email VARCHAR(190) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                display_name VARCHAR(190) NOT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                locale VARCHAR(10) NOT NULL DEFAULT 'en',
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL
            );
            CREATE TABLE user_role_assignments (
                user_id INT NOT NULL,
                role_id INT NOT NULL,
                PRIMARY KEY (user_id, role_id)
            );
            CREATE TABLE audit_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NULL,
                action VARCHAR(100) NOT NULL,
                entity_type VARCHAR(100) NOT NULL,
                entity_id INT NULL,
                metadata_json LONGTEXT NULL,
                created_at DATETIME NOT NULL
            );
        ");
    }
};
