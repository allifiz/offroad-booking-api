# Offroad Booking — Project Progress Checkpoint

Last updated: 2026-07-20 (Asia/Jakarta)
Branch: `main`
Repository: `allifiz/offroad-booking-api`
Local path: `C:\Projects\offroad-booking-api`

## Current status

- Backend core MVP: approximately 99%.
- Backend production readiness: approximately 97%.
- Laravel admin web: core operational modules implemented, including reports and audit logs.

## Admin reports

Routes:

```text
GET /admin/reports
GET /admin/reports/export/bookings
GET /admin/reports/export/payments
GET /admin/reports/export/drivers
GET /admin/reports/export/withdrawals
```

Implemented:

- session-protected report center
- configurable `date_from` and `date_to`
- default latest 30 days
- maximum range 366 days
- direct CSV downloads for bookings, payments, drivers, and withdrawals
- reuses the canonical API export controller
- cursor streaming, UTF-8 BOM, no-store, nosniff, and formula-injection neutralization remain active

Files:

```text
app/Http/Controllers/Web/Admin/ReportController.php
resources/views/admin/reports/index.blade.php
```

## Admin audit logs

Routes:

```text
GET /admin/audit-logs
GET /admin/audit-logs/{auditLog}
```

Implemented:

- pagination
- event filtering
- actor name/email search
- subject type and ID filtering
- date filtering
- request method, URL, IP, and user-agent context
- formatted before/after JSON detail

Files:

```text
app/Http/Controllers/Web/Admin/AuditLogController.php
resources/views/admin/audit-logs/index.blade.php
resources/views/admin/audit-logs/show.blade.php
tests/Feature/AdminWebReportsAuditFlowTest.php
```

## Existing production operations

```bash
php artisan app:health
php artisan app:health --json
php artisan queue:health
php artisan queue:health --json
```

Deployment and backup:

```text
deploy/scripts/deploy.sh
deploy/scripts/backup.sh
deploy/supervisor/offroad-booking-worker.conf
docs/PRODUCTION_DEPLOYMENT.md
```

## Autonomous CI

Workflow: `.github/workflows/backend-tests.yml`.

CI was confirmed green before the reports/audit web changes. Do not claim `AdminWebReportsAuditFlowTest` passes until the latest workflow result is confirmed.

## API documentation

Canonical contract: `docs/openapi.yaml`.

Web-only routes are not part of OpenAPI. Canonical OpenAPI still needs dashboard, CSV response, exact schemas, and remaining admin API coverage.

## Next recommended work

1. Inspect and fix any reports/audit CI failure.
2. Complete canonical OpenAPI coverage.
3. Start customer web.
4. Start Flutter driver integration.

## Response format rule

After backend changes respond in this exact order:

1. **Changes**
2. **Endpoint changes**
3. **Cara pull changes**
4. **cURL Postman**
5. **Expected result cURL**
