# Offroad Booking API — Project Progress Checkpoint

Last updated: 2026-07-20 (Asia/Jakarta)
Branch: `main`
Repository: `allifiz/offroad-booking-api`
Local path: `C:\Projects\offroad-booking-api`

## Current backend status

Estimated progress:

- Core functional MVP: approximately 98–99%
- Production readiness: approximately 94–95%

The complete booking, payment, assignment, allocation, completion reward, withdrawal, audit, notification, rate-limit, queue-hardening, dashboard, and CSV reporting flows are implemented.

## Production queue hardening

Implemented:

- Existing Laravel database queue migrations for `jobs`, `job_batches`, and `failed_jobs` confirmed.
- Operational notifications run on the `notifications` queue after transaction commit.
- Retry policy: 5 tries.
- Timeout: 30 seconds with fail-on-timeout.
- Backoff: 10, 60, 300, and 900 seconds.
- Queue health config and command.
- Supervisor template and production operations guide.
- Queue policy and health tests.

Recommended worker:

```bash
php artisan queue:work database --queue=notifications,default --sleep=3 --tries=5 --timeout=30 --max-time=3600
```

## Admin dashboard metrics

Endpoint:

```text
GET /api/v1/admin/dashboard
```

Supports `date_from` and `date_to`, defaults to the latest 30 days, and limits the period to 366 days.

Metrics include bookings, participants, booking value, payments/revenue, driver and vehicle snapshots, withdrawals, and zero-filled daily trends.

Files:

- `app/Http/Controllers/Api/V1/Admin/DashboardController.php`
- `tests/Feature/AdminDashboardFlowTest.php`
- `docs/ADMIN_DASHBOARD.md`

## Admin CSV report exports

Endpoints:

```text
GET /api/v1/admin/reports/export/bookings
GET /api/v1/admin/reports/export/payments
GET /api/v1/admin/reports/export/drivers
GET /api/v1/admin/reports/export/withdrawals
```

Features:

- admin-only Sanctum access
- optional period and status filters
- default latest 30 days
- maximum period 366 days
- database cursor streaming for bounded memory usage
- UTF-8 BOM for Excel compatibility
- timestamped attachment filenames
- `Cache-Control: no-store`
- `X-Content-Type-Options: nosniff`
- spreadsheet formula-injection neutralization

Files:

- `app/Http/Controllers/Api/V1/Admin/ReportExportController.php`
- `tests/Feature/AdminReportExportFlowTest.php`
- `docs/CSV_REPORTS.md`

## Autonomous CI

Workflow: `.github/workflows/backend-tests.yml`.

Confirmed green before CSV export changes:

- OpenAPI lint
- SQLite feature suite
- MySQL concurrent-withdrawal suite

CSV export changes trigger a new CI run. Do not claim `AdminReportExportFlowTest` passes until GitHub reports the result.

## API documentation

Canonical contract: `docs/openapi.yaml`.

Operational guides:

- `docs/ADMIN_DASHBOARD.md`
- `docs/CSV_REPORTS.md`
- `docs/QUEUE_PRODUCTION.md`

## Next recommended work

1. Inspect and fix any CSV export CI failure.
2. Expand exact OpenAPI schemas and remaining admin endpoints.
3. Prepare backup, deployment, monitoring, and frontend/Flutter integration.

## Response format rule

After backend changes respond in this exact order:

1. **Changes**
2. **Endpoint changes**
3. **Cara pull changes**
4. **cURL Postman**
5. **Expected result cURL**
