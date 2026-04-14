# Automated Upgrade Guide (GitHub Releases + Safe Backup + DB Migrations)

## Goal

Replace the current **manual ZIP upload** process with an admin-driven upgrade flow that:

1. checks GitHub Releases from the web UI,
2. downloads and verifies the selected release artifact,
3. places the application in maintenance mode,
4. creates backups of both the database and current app files,
5. deploys the new files,
6. runs database migration scripts,
7. performs health checks, and
8. supports one-click rollback if any step fails.

This guide is designed to mirror the EPSS CAS style of controlled upgrades while fitting this codebase.

---

## Current state in this repository

- Upgrade UI exists at `/admin/upgrades` and currently expects a manually uploaded ZIP package.
- Upgrade logic is centralized in `App\Core\Upgrade\UpgradeManager`.
- Maintenance mode support already exists and is used during upgrades.
- DB migrations can already be executed through `Migrator`.

So the main change is not a full rewrite, but an extension from "manual local ZIP" to "release-driven remote upgrade orchestration."

---

## Target architecture

## 1) Release metadata source (GitHub)

Use GitHub Releases API for a configured repo, for example:

- `GET /repos/{owner}/{repo}/releases/latest`
- `GET /repos/{owner}/{repo}/releases`

Store the repository source in settings, e.g.:

- `UPGRADE_GITHUB_OWNER`
- `UPGRADE_GITHUB_REPO`
- `UPGRADE_RELEASE_ASSET` (default asset name like `app-upgrade.zip`)
- `UPGRADE_GITHUB_TOKEN` (optional, for private repos/rate limits)

Recommended: maintain a `release-manifest.json` as an asset alongside the ZIP.

---

## 2) Expected release artifact format

Use a deterministic package format to support verification and rollback:

```text
release-artifact.zip
├── manifest.json
├── app/
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── public/
│   ├── resources/
│   ├── cli/
│   ├── composer.json
│   └── composer.lock
├── database/
│   └── migrations/
└── checksums.txt
```

`manifest.json` should include:

- `package_id`
- `from_version`
- `to_version`
- `released_at`
- `migration_sequence`
- `min_php_version`
- `min_db_version`
- `artifact_sha256`

If possible, add signature verification (e.g., detached signature file).

---

## 3) New/updated backend services

## 3.1 `GitHubReleaseClient`

Responsibilities:

- fetch latest release,
- list compatible releases,
- return asset download URL + checksum metadata,
- normalize API failures for the UI.

## 3.2 `BackupService`

Responsibilities:

- create DB dump backup (`mysqldump`),
- create app filesystem archive backup (`tar.gz`),
- write metadata (`backup.json`) for rollback.

Suggested backup layout:

```text
storage/backups/
  2026-04-07T10-15-00Z/
    db.sql.gz
    app.tar.gz
    backup.json
```

`backup.json` example fields:

- `timestamp`
- `app_version_before`
- `db_dump_file`
- `app_backup_file`
- `created_by_user_id`

## 3.3 `DeploymentService`

Responsibilities:

- unpack release artifact to staging directory,
- validate manifest/checksum,
- sync app files to live location (atomic where possible),
- preserve runtime paths (`storage/`, `.env`, optional uploads).

## 3.4 `DatabaseUpgradeService`

Responsibilities:

- run migration scripts in order,
- record executed migrations,
- stop immediately on failure,
- produce detailed logs.

## 3.5 `RollbackService`

Responsibilities:

- restore app files from archive,
- restore DB from dump,
- mark upgrade as rolled back in logs,
- safely clear maintenance mode after recovery.

---

## 4) Web UI flow (`/admin/upgrades`)

Upgrade page should offer:

1. **Check for updates** button.
2. Release info panel:
   - current version,
   - latest version,
   - release date,
   - compatibility note,
   - changelog link.
3. **Run upgrade** action with confirmation modal showing:
   - backup plan,
   - expected downtime,
   - rollback policy.
4. Live progress panel:
   - `Downloading release`
   - `Verifying package`
   - `Backing up DB`
   - `Backing up app files`
   - `Deploying files`
   - `Running migrations`
   - `Health checks`
   - `Completed` or `Failed + Rolled back`

Also keep a table of historical upgrade jobs with details and operator.

---

## 5) Recommended orchestration sequence

Use this sequence in one orchestrator method (transaction-like behavior):

1. Validate admin permission and CSRF.
2. Query release metadata from GitHub.
3. Validate version progression (`to_version > current`).
4. Enable maintenance mode.
5. Download release asset into `storage/upgrades/tmp/...`.
6. Verify checksum/signature.
7. Create DB backup.
8. Create app backup archive.
9. Deploy new app files from artifact.
10. Run DB migrations.
11. Run post-upgrade smoke checks.
12. Write success log and update `application_versions`.
13. Disable maintenance mode.

On any failure after step 7:

- trigger rollback automatically,
- log rollback result,
- disable maintenance mode only when app is healthy again.

---

## 6) Database backup and restore strategy

## Backup command (example)

```bash
mysqldump --single-transaction --quick --routines --triggers \
  -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" | gzip > db.sql.gz
```

## Restore command (example)

```bash
gunzip -c db.sql.gz | mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME"
```

Implementation note:

- use `proc_open`/Symfony Process style wrapper (or equivalent) with strict exit-code checks,
- never log plaintext DB password,
- mask secrets in upgrade logs.

---

## 7) App backup and deployment strategy

## Backup current app

```bash
tar --exclude='storage/cache/*' --exclude='storage/logs/*' -czf app.tar.gz .
```

## Deploy new files

Preferred approach:

- unpack to a staging directory,
- validate required files,
- rsync/copy into live app directory while preserving:
  - `.env`
  - `storage/`
  - user-uploaded files

If your hosting allows symlink switch deployments, use:

- `releases/<version>` directory,
- `current` symlink swap for near-atomic cutover.

---

## 8) Migration script guidance

- Keep migrations idempotent where possible.
- Gate destructive changes behind explicit checks.
- Include preconditions and postconditions in each migration.
- Record each migration execution in the existing migration table.
- Add a `post_migrate` smoke validation script if needed.

Recommended migration release policy:

- one-way forward migrations,
- rollback relies on full DB backup restore (not ad-hoc down migrations in production).

---

## 9) Security and integrity controls

- Restrict upgrade action to super admin role only.
- Require CSRF token and recent re-auth (optional hardening).
- Enforce HTTPS for GitHub API and asset download.
- Verify checksums for all downloaded artifacts.
- Optionally pin GitHub release signer key/signature.
- Store detailed audit entries for every upgrade step.
- Throttle or lock concurrent upgrade attempts.

---

## 10) Logging and observability

Track each step in `upgrade_logs` with structured JSON fields:

- `job_id`
- `step`
- `status`
- `started_at`
- `ended_at`
- `duration_ms`
- `error_message` (sanitized)
- `backup_path`
- `release_tag`

Also log machine-readable events to `storage/logs/app.log`.

---

## 11) Suggested implementation plan (incremental)

## Phase 1: GitHub check + read-only UI

- Add "Check for updates" using Releases API.
- Show current vs latest version and changelog links.

## Phase 2: Automated backup + deploy + migrate

- Implement backup services and deployment orchestration.
- Reuse/extend current `UpgradeManager` and maintenance mode.

## Phase 3: Rollback automation

- Add auto rollback on any failed post-backup step.
- Add rollback test scenario to smoke test checklist.

## Phase 4: Hardening

- checksum/signature enforcement,
- concurrency locks,
- re-auth confirmation for upgrade action,
- richer observability and admin reporting.

---

## 12) Acceptance criteria

A release-driven upgrade is considered complete when:

- Admin can detect a newer GitHub release from UI.
- Upgrade creates both DB and app backups before deployment.
- New files deploy successfully and migrations execute in order.
- On failure, full rollback restores app + DB automatically.
- Logs clearly show who upgraded, what changed, and outcome.
- Post-upgrade health checks pass and maintenance mode is cleared.

---

## 13) Operational runbook (admin)

1. Open `/admin/upgrades`.
2. Click **Check for updates**.
3. Review release notes and compatibility warning(s).
4. Click **Run upgrade** and confirm downtime message.
5. Monitor step-by-step progress.
6. Verify success banner and new current version.
7. Run smoke checks (`cli/smoke-core.php`).
8. If failed, verify rollback status and investigate logs.

---

## 14) Notes for this baseline codebase

To keep changes minimal, evolve these existing pieces:

- Extend `UpgradeController` with:
  - `check()` endpoint for GitHub release discovery,
  - `run()` endpoint for orchestrated automated upgrade.
- Extend `UpgradeManager` to support:
  - remote artifact input,
  - backup hooks,
  - rollback hooks,
  - step-level logging.
- Update `resources/views/admin/upgrades.php` to include:
  - check-for-update panel,
  - release metadata,
  - action confirmation and progress output.

This gives you EPSS-CAS-like behavior without discarding existing upgrade scaffolding.
