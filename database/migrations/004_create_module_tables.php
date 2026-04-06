<?php
use App\Core\Database\DB;
use App\Core\Database\Migration;

return new class implements Migration {
    public function up(DB $db): void
    {
        $db->pdo()->exec("
            CREATE TABLE sample_information (
                assessment_id INT PRIMARY KEY,
                sites_surveyed INT NOT NULL,
                sites_total INT NOT NULL,
                central_units INT NOT NULL,
                hubs INT NOT NULL,
                notes TEXT NULL,
                updated_at DATETIME NOT NULL
            );
            CREATE TABLE exchange_inflation_rates (
                id INT AUTO_INCREMENT PRIMARY KEY,
                assessment_id INT NOT NULL,
                year INT NOT NULL,
                etb_per_usd DECIMAL(18,6) NOT NULL,
                usd_per_etb DECIMAL(18,8) NOT NULL,
                interest_rate DECIMAL(8,3) NOT NULL,
                inflation_rate DECIMAL(8,3) NOT NULL,
                source_notes TEXT NULL
            );
            CREATE TABLE hr_summary_rows (
                id INT AUTO_INCREMENT PRIMARY KEY,
                assessment_id INT NOT NULL,
                year INT NOT NULL,
                staff_category VARCHAR(150) NOT NULL,
                annual_cost_etb DECIMAL(18,2) NOT NULL DEFAULT 0,
                allocation_percent DECIMAL(8,2) NOT NULL DEFAULT 0
            );
            CREATE TABLE hr_backend_rows (
                id INT AUTO_INCREMENT PRIMARY KEY,
                assessment_id INT NOT NULL,
                year INT NOT NULL,
                staff_group VARCHAR(150) NOT NULL,
                department VARCHAR(150) NULL,
                function_name VARCHAR(150) NULL,
                allocation_share DECIMAL(8,2) NOT NULL DEFAULT 0,
                org_level VARCHAR(100) NULL,
                annual_cost_etb DECIMAL(18,2) NOT NULL DEFAULT 0,
                notes TEXT NULL
            );
            CREATE TABLE epss_cost_summary_rows (
                id INT AUTO_INCREMENT PRIMARY KEY,
                assessment_id INT NOT NULL,
                year INT NOT NULL,
                category VARCHAR(150) NOT NULL,
                subcategory VARCHAR(150) NULL,
                amount_etb DECIMAL(18,2) NOT NULL DEFAULT 0
            );
            CREATE TABLE epss_cost_backend_rows (
                id INT AUTO_INCREMENT PRIMARY KEY,
                assessment_id INT NOT NULL,
                year INT NOT NULL,
                category VARCHAR(150) NOT NULL,
                cost_ingredient VARCHAR(150) NULL,
                cost_element VARCHAR(150) NULL,
                amount_etb DECIMAL(18,2) NOT NULL DEFAULT 0,
                notes TEXT NULL
            );
            CREATE TABLE revenue_entries (
                id INT AUTO_INCREMENT PRIMARY KEY,
                assessment_id INT NOT NULL,
                year INT NOT NULL,
                category VARCHAR(150) NOT NULL,
                amount_etb DECIMAL(18,2) NOT NULL DEFAULT 0
            );
            CREATE TABLE other_value_entries (
                id INT AUTO_INCREMENT PRIMARY KEY,
                assessment_id INT NOT NULL,
                year INT NOT NULL,
                commodity_type VARCHAR(150) NULL,
                unit_of_measure VARCHAR(100) NULL,
                units_received DECIMAL(18,2) NULL,
                units_shipped DECIMAL(18,2) NULL,
                transport_weight DECIMAL(18,2) NULL,
                annual_throughput DECIMAL(18,2) NULL,
                annual_commodity_cost DECIMAL(18,2) NULL,
                contribution_percent DECIMAL(8,2) NULL
            );
            CREATE TABLE rdf_program_allocations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                assessment_id INT NOT NULL,
                function_name VARCHAR(150) NOT NULL,
                rdf_percent DECIMAL(8,2) NOT NULL DEFAULT 0,
                program_percent DECIMAL(8,2) NOT NULL DEFAULT 0,
                assumptions TEXT NULL
            );
            CREATE TABLE risk_entries (
                id INT AUTO_INCREMENT PRIMARY KEY,
                assessment_id INT NOT NULL,
                risk_type VARCHAR(150) NOT NULL,
                description TEXT NULL,
                previous_value DECIMAL(18,2) NULL,
                current_value DECIMAL(18,2) NULL,
                impact_etb DECIMAL(18,2) NULL,
                notes TEXT NULL
            );
            CREATE TABLE working_capital_entries (
                id INT AUTO_INCREMENT PRIMARY KEY,
                assessment_id INT NOT NULL,
                year INT NOT NULL,
                beginning_inventory DECIMAL(18,2) NULL,
                ending_inventory DECIMAL(18,2) NULL,
                cost_of_goods_used DECIMAL(18,2) NULL,
                outstanding_accounts_receivable DECIMAL(18,2) NULL,
                total_credit_sales DECIMAL(18,2) NULL,
                outstanding_accounts_payable DECIMAL(18,2) NULL,
                cost_of_goods_purchased DECIMAL(18,2) NULL
            );
            CREATE TABLE glossary_definitions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                term_key VARCHAR(150) NOT NULL UNIQUE,
                label_en VARCHAR(255) NOT NULL,
                label_am VARCHAR(255) NULL,
                description_en TEXT NULL,
                description_am TEXT NULL,
                updated_at DATETIME NOT NULL
            );
            CREATE TABLE equipment_assets (
                id INT AUTO_INCREMENT PRIMARY KEY,
                assessment_id INT NOT NULL,
                year INT NOT NULL,
                level_name VARCHAR(100) NULL,
                equipment_category VARCHAR(150) NULL,
                equipment_item VARCHAR(150) NULL,
                quantity INT NOT NULL DEFAULT 0,
                purchased_value DECIMAL(18,2) NULL,
                donated_value DECIMAL(18,2) NULL,
                purchase_year INT NULL,
                funding_source VARCHAR(150) NULL,
                maintenance_value DECIMAL(18,2) NULL,
                notes TEXT NULL
            );
            CREATE TABLE infrastructure_assets (
                id INT AUTO_INCREMENT PRIMARY KEY,
                assessment_id INT NOT NULL,
                year INT NOT NULL,
                level_name VARCHAR(100) NULL,
                length_m DECIMAL(12,2) NULL,
                width_m DECIMAL(12,2) NULL,
                total_area DECIMAL(12,2) NULL,
                utilization_level VARCHAR(100) NULL,
                acquisition_value DECIMAL(18,2) NULL,
                in_service_year INT NULL,
                annual_depreciation DECIMAL(18,2) NULL,
                annual_maintenance DECIMAL(18,2) NULL,
                notes TEXT NULL
            );
            CREATE TABLE attachments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                assessment_id INT NOT NULL,
                module_key VARCHAR(100) NULL,
                file_name VARCHAR(255) NOT NULL,
                file_path VARCHAR(255) NOT NULL,
                mime_type VARCHAR(100) NULL,
                uploaded_by INT NOT NULL,
                description TEXT NULL,
                created_at DATETIME NOT NULL
            );
        ");
    }
};
