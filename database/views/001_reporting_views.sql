CREATE OR REPLACE VIEW vw_assessment_overview AS
SELECT
    a.id AS assessment_id,
    a.title,
    a.status,
    a.organization_id,
    o.name AS organization_name,
    a.facility_id,
    f.name AS facility_name,
    a.fiscal_year_id,
    fy.label AS fiscal_year_label,
    a.assessment_period,
    a.calculation_version_id,
    cv.version_code AS calculation_version,
    a.created_at,
    a.updated_at
FROM assessments a
JOIN organizations o ON o.id = a.organization_id
LEFT JOIN facilities f ON f.id = a.facility_id
JOIN fiscal_years fy ON fy.id = a.fiscal_year_id
LEFT JOIN calculation_versions cv ON cv.id = a.calculation_version_id;

CREATE OR REPLACE VIEW vw_hr_costs_by_year AS
SELECT
    assessment_id,
    year,
    staff_category,
    SUM(annual_cost_etb) AS total_annual_cost_etb,
    SUM(allocation_percent) AS total_allocation_percent
FROM hr_summary_rows
GROUP BY assessment_id, year, staff_category;

CREATE OR REPLACE VIEW vw_working_capital_kpis AS
SELECT
    assessment_id,
    year,
    CASE WHEN cost_of_goods_used > 0 THEN (ending_inventory / cost_of_goods_used) * 365 ELSE NULL END AS inventory_days,
    CASE WHEN total_credit_sales > 0 THEN (outstanding_accounts_receivable / total_credit_sales) * 365 ELSE NULL END AS ar_days,
    CASE WHEN cost_of_goods_purchased > 0 THEN (outstanding_accounts_payable / cost_of_goods_purchased) * 365 ELSE NULL END AS ap_days,
    CASE
        WHEN cost_of_goods_used > 0 AND total_credit_sales > 0 AND cost_of_goods_purchased > 0
        THEN ((ending_inventory / cost_of_goods_used) * 365) + ((outstanding_accounts_receivable / total_credit_sales) * 365) - ((outstanding_accounts_payable / cost_of_goods_purchased) * 365)
        ELSE NULL
    END AS cash_to_cash_cycle_days
FROM working_capital_entries;
