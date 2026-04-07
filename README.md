# TCSA Web Application Baseline (LAMP)

This repository is a modular baseline implementation of the **TCSA Web Application** described in the SRS v1.1. It is intentionally focused on a **working baseline** rather than polished UI.

## Included baseline capabilities

- Modular PHP 8.1+ application structure
- MySQL/MariaDB migrations and seeders
- Centralized authentication and role-based permissions
- English and Amharic translation files
- Assessment workflow: draft, submitted, reviewed, returned, approved, locked
- Audit logging and field-level change logging
- Baseline modules implemented:
  - Assessment setup
  - Sample information
  - Exchange, interest, and inflation
- Scaffold placeholders for the remaining workbook-derived modules
- BI-friendly SQL reporting views
- Admin-only manual upgrade utility with manifest validation and maintenance mode
- CLI migration, seeding, and admin bootstrap scripts
- Structured application error logging to `storage/logs/app.log`

## Important note

This baseline is designed for **iterative enhancement**. It provides the core architecture and initial working modules so that future development can extend the remaining modules without large refactoring.

## Stack

- Ubuntu Server
- Apache 2
- PHP 8.1+
- MySQL 8 or MariaDB 10.6+
- Composer

## Quick start

### 1. Copy the repo to your server

```bash
git clone <your-repo-url> tcsa
cd tcsa
```

### 2. Install PHP dependencies

```bash
composer install
cp .env.example .env
```

### 3. Update `.env`

Set the DB credentials and base URL.

### 4. Create the database

```sql
CREATE DATABASE tcsa CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 5. Run a preflight check

This validates required PHP extensions, writable storage paths, env keys, and DB connectivity:

```bash
php cli/preflight.php
```

Use `--skip-db` only if you intentionally want to skip the DB connectivity check:

```bash
php cli/preflight.php --skip-db
```

### 6. Run migrations and seeders

```bash
php cli/migrate.php
php cli/seed.php
```

Or run both in one command:

```bash
php cli/install.php
```

Use `--skip-seed` if you only want schema setup:

```bash
php cli/install.php --skip-seed
```

The migration and seeding commands are upgrade-safe and idempotent at the orchestration level:

- `php cli/migrate.php` only executes files not yet recorded in `schema_migrations`
- `php cli/seed.php` can be run repeatedly without duplicating baseline reference records

### 7. Configure Apache document root

Point the Apache vhost to:

```text
/path/to/tcsa/public
```

Enable `AllowOverride All` so `.htaccess` rewrites work.

Example Apache vhost:

```apache
<VirtualHost *:80>
    ServerName tcsa.local
    DocumentRoot /var/www/tcsa/public

    <Directory /var/www/tcsa/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/tcsa_error.log
    CustomLog ${APACHE_LOG_DIR}/tcsa_access.log combined
</VirtualHost>
```

### 8. Log in

Seeded admin account:

- Username: `admin`
- Password: `ChangeMe123!`

Change the password immediately after first login.

## Directory overview

```text
app/
  Core/              framework-like kernel services
  Modules/           feature modules
  lang/              translation files
bootstrap/           app bootstrap, routes, helpers
config/              externalized config
database/
  migrations/        schema changes
  seeders/           master data
  views/             BI reporting views
cli/                 migration and admin scripts
public/              web root
resources/views/     php templates
storage/             uploads, cache, logs, upgrades
```

## Baseline workflow behavior

Allowed transitions are configured in `config/workflow.php`:

- draft -> submitted
- submitted -> reviewed / returned / approved
- reviewed -> returned / approved
- returned -> draft / submitted
- approved -> locked

## Upgrade utility

The admin-only upgrade utility is available at:

```text
/admin/upgrades
```

It expects a ZIP package with a structure like this:

```text
upgrade-package.zip
├── manifest.json
├── migrations/
│   ├── 20260406_001_add_new_table.php
│   └── 20260406_002_add_view.sql
```

Example manifest:

```json
{
  "package_id": "tcsa-0.1.1",
  "from_version": "0.1.0",
  "to_version": "0.1.1",
  "migration_sequence": [
    "20260406_001_add_new_table.php",
    "20260406_002_add_view.sql"
  ]
}
```

During upgrade execution, the app enters maintenance mode and serves HTTP `503` for non-exempt paths until completion.

## BI-ready reporting views

The project includes:

- `vw_assessment_overview`
- `vw_hr_costs_by_year`
- `vw_working_capital_kpis`

These views are intended for MySQL-native reporting and tools like Looker Studio via a MySQL connector on the LAN.

## Recommended next implementation steps

1. Add full CRUD for the remaining workbook modules
2. Add CSV import/export services and templates for detail-heavy modules
3. Add OIDC provider UI and callback flow
4. Add attachments service and file restrictions
5. Add printable/PDF summary output
6. Add reviewer comparison UI for revision history
7. Expand validation engine into reusable module validators
8. Add organization/facility scope enforcement in middleware and queries

## Security notes

This baseline includes:

- password hashing
- session-based auth
- CSRF checks on forms
- centralized permission checks
- audit logging
- centralized CSRF middleware on all POST routes
- session ID regeneration on login/logout

## Maintenance and operations

- Maintenance mode flag file: `storage/cache/maintenance.flag` (configurable with `APP_MAINTENANCE_FILE`)
- Exempt route(s) while maintenance mode is active: `/admin/upgrades`
- App error log: `storage/logs/app.log`
- Core smoke test script:

```bash
php cli/smoke-core.php
```

## Administrative foundation (baseline)

The baseline now includes production-minded admin workflows for:

- User CRUD (create + status/role/scope update)
- Role assignment
- Admin-initiated password reset
- Organization and facility/hub/central-unit management
- Fiscal year and assessment period management
- System settings persistence (`system_settings`)
- Scope-aware assessment access checks using organization scope

Primary screens:

- `/admin/users`
- `/admin/organizations`
- `/admin/periods`
- `/admin/settings`

See `docs/admin-guide.md` for manual verification steps and role/scope behavior checks.

## Assessment workflow baseline quality

Workflow states:

- Draft
- Submitted
- Reviewed
- Returned for Correction (`returned`)
- Approved
- Locked

Key behaviors:

- Only valid transitions are allowed via `config/workflow.php`.
- Module statuses use: `Not Started`, `In Progress`, `Complete`, `Complete with Warnings`, `Validation Errors`.
- Locked assessments are read-only.
- Edit operations are restricted to editable workflow states (currently `draft` and `returned`).
- Admin/reviewer unlock-reopen requires a mandatory reason and creates a new revision.
- Workflow actions are captured in `workflow_history` (with metadata JSON).
- Human-readable revision comparison is available at `/assessments/{id}/revisions/compare`.

Smoke test:

```bash
php cli/smoke-workflow.php
```

Before production use, add:

- secure cookies and HTTPS-only cookies
- login throttling
- password reset UI
- stronger session hardening
- file upload scanning and MIME validation
- organization-scoped data filters everywhere

## License

Set the license that matches your intended GitHub distribution model.
