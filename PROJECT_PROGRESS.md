# Offroad Booking API — Project Progress Checkpoint

Last updated: 2026-07-20 (Asia/Jakarta)
Branch: `main`
Repository: `allifiz/offroad-booking-api`
Local path: `C:\Projects\offroad-booking-api`

## Current backend status

Estimated progress:

- Core functional MVP: approximately 99%
- Production readiness: approximately 96%

Implemented end-to-end:

- booking, payment, assignment, allocation, completion reward, withdrawal
- audit logs, notifications, rate limiting
- queue hardening and queue health
- admin dashboard metrics
- streamed CSV reports
- application health checks
- production deployment, backup, and recovery scripts

## Production health

Commands:

```bash
php artisan app:health
php artisan app:health --json
php artisan queue:health
php artisan queue:health --json
```

`app:health` verifies:

- database connectivity
- writable default storage
- accessible `jobs` and `failed_jobs` tables

Test: `tests/Feature/ApplicationHealthFlowTest.php`.

## Deployment and backup

Files:

```text
deploy/scripts/deploy.sh
deploy/scripts/backup.sh
deploy/supervisor/offroad-booking-worker.conf
docs/PRODUCTION_DEPLOYMENT.md
```

Deployment behavior:

- maintenance mode with automatic recovery trap
- fetch/reset to configured branch
- production Composer install
- forced migrations
- storage link
- cache rebuild
- queue restart
- application health gate before reopening

Backup behavior:

- compressed transactional MySQL dump
- compressed `storage/app/public`
- protected `.env` backup
- SHA-256 checksums
- default 14-day retention

## Admin reporting

Dashboard:

```text
GET /api/v1/admin/dashboard
```

CSV exports:

```text
GET /api/v1/admin/reports/export/bookings
GET /api/v1/admin/reports/export/payments
GET /api/v1/admin/reports/export/drivers
GET /api/v1/admin/reports/export/withdrawals
```

## Autonomous CI

Workflow: `.github/workflows/backend-tests.yml`.

Previously confirmed green:

- OpenAPI lint
- SQLite feature suite
- MySQL concurrent-withdrawal suite

The newest reporting/deployment commits trigger a new run. Do not claim `AdminReportExportFlowTest` or `ApplicationHealthFlowTest` passes until GitHub reports it.

## API documentation

Canonical contract: `docs/openapi.yaml`.

Operational guides:

- `docs/ADMIN_DASHBOARD.md`
- `docs/CSV_REPORTS.md`
- `docs/QUEUE_PRODUCTION.md`
- `docs/PRODUCTION_DEPLOYMENT.md`

The canonical OpenAPI still needs expansion for dashboard, CSV responses, exact schemas, and remaining admin CRUD operations.

## Next recommended work

1. Inspect and fix current CI failures, if any.
2. Complete canonical OpenAPI coverage.
3. Add external monitoring/alert delivery and backup restore verification.
4. Start Laravel admin/customer frontend and Flutter driver integration.

## Response format rule

After backend changes respond in this exact order:

1. **Changes**
2. **Endpoint changes**
3. **Cara pull changes**
4. **cURL Postman**
5. **Expected result cURL**
