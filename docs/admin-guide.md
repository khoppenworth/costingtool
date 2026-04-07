# Admin Guide (Baseline)

## Purpose

This guide covers manual checks for the administrative foundation:

- user administration
- role assignment
- active/inactive status handling
- admin password reset
- organization/facility management
- fiscal year and assessment period setup
- core system settings persistence
- scope-aware access behavior

## Manual test steps

1. **Seed and login as Super Admin**
   - Run `php cli/migrate.php && php cli/seed.php`.
   - Login as `admin`.

2. **Users CRUD + role assignment**
   - Open `/admin/users`.
   - Create a user with role `Organization Admin` and assign one organization scope.
   - Edit that user and switch status to `Inactive`; verify login is blocked.
   - Re-activate and verify login succeeds.

3. **Admin password reset**
   - From `/admin/users`, submit a new password for the user.
   - Verify the user can authenticate with the reset password.

4. **Organization/facility management**
   - Open `/admin/organizations`.
   - Create a new organization.
   - Add facilities with types `facility`, `hub`, and `central_unit`.
   - Toggle active/inactive and verify values persist.

5. **Fiscal years and assessment periods**
   - Open `/admin/periods`.
   - Create a fiscal year and assessment period.
   - Set one as inactive and verify status persists in list.

6. **Settings persistence**
   - Open `/admin/settings`.
   - Save `default_locale`, `country_name`, `currency_code`, and `support_email`.
   - Refresh and verify saved values.

7. **Scope-aware access**
   - As Super Admin, create assessments for two different organizations.
   - Login as non-super user scoped to only one organization.
   - Verify only in-scope assessments appear in `/assessments`.
   - Attempt direct URL access to out-of-scope assessment and module routes; verify `403`.

8. **Audit checks**
   - Query `audit_logs` and verify records for user/org/facility/period/settings updates and password resets.

9. **Workflow behavior checks**
   - Create an assessment in `Draft`.
   - Progress through `Submitted -> Reviewed -> Approved -> Locked`.
   - Verify direct module edits fail while in `Submitted/Reviewed/Approved/Locked`.
   - Use unlock/reopen with a mandatory reason and verify:
     - status changes to `Returned`
     - `assessment_revisions` records a new revision
     - `workflow_history` includes unlock metadata
   - Open `/assessments/{id}/revisions` and compare revisions via the compare link.

## Notes

- Scope enforcement uses `user_organization_scopes` for non-super-admin roles.
- Super Admin remains global by design.
