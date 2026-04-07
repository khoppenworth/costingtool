<?php
use App\Core\Database\DB;
use App\Core\Database\Migration;

return new class implements Migration {
    public function up(DB $db): void
    {
        $db->pdo()->exec("
            CREATE TABLE user_organization_scopes (
                user_id INT NOT NULL,
                organization_id INT NOT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                PRIMARY KEY (user_id, organization_id)
            );

            CREATE TABLE assessment_periods (
                id INT AUTO_INCREMENT PRIMARY KEY,
                period_code VARCHAR(50) NOT NULL UNIQUE,
                label VARCHAR(120) NOT NULL,
                start_date DATE NOT NULL,
                end_date DATE NOT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1
            );
        ");
    }
};
