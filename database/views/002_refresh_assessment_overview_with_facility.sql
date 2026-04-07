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
