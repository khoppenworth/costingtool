<?php
use App\Core\Database\DB;
use App\Core\Database\Migration;

return new class implements Migration {
    public function up(DB $db): void
    {
        $db->pdo()->exec("
            ALTER TABLE assessments
                ADD COLUMN facility_id INT NULL AFTER organization_id,
                ADD COLUMN metadata_json LONGTEXT NULL AFTER assumptions_notes,
                ADD COLUMN current_revision INT NOT NULL DEFAULT 1 AFTER calculation_version_id;

            ALTER TABLE workflow_history
                ADD COLUMN metadata_json LONGTEXT NULL AFTER comments;
        ");
    }
};
