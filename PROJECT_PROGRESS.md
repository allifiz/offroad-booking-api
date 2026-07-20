# Offroad Booking — Project Progress Checkpoint

Last updated: 2026-07-20 (Asia/Jakarta)
Branch: `main`
Repository: `allifiz/offroad-booking-api`
Local path: `C:\Projects\offroad-booking-api`

## Current status

- Backend core MVP: approximately 99%.
- Backend production readiness: approximately 97%.
- Laravel admin web: core operational modules implemented, including reports and audit logs.

## Latest CI issue and fix

The SQLite feature suite failed while rendering Admin Web Blade views because GitHub Actions had not generated:

```text
public/build/manifest.json
```

The application views use Laravel `@vite`, so the manifest must exist before feature tests render those views.

Workflow fix in `.github/workflows/backend-tests.yml`:

```text
setup Node.js 22
→ npm install --ignore-scripts
→ npm run build
→ php artisan test
```

The repository currently has no `package-lock.json`, therefore the workflow uses `npm install` instead of `npm ci`.

Commit containing the workflow fix:

```text
ab13bbf80e1cea108c0fe837a15d9f81c77cd6ae
```

The reported `AdminWebDriverVerificationFlowTest` and `AdminWebFlowTest` failures were consequences of the missing Vite manifest, not failed business assertions.

## Admin reports and audit logs

Implemented routes:

```text
GET /admin/reports
GET /admin/reports/export/bookings
GET /admin/reports/export/payments
GET /admin/reports/export/drivers
GET /admin/reports/export/withdrawals
GET /admin/audit-logs
GET /admin/audit-logs/{auditLog}
```

Reports reuse the canonical CSV export controller with cursor streaming, UTF-8 BOM, no-store, nosniff, period validation, and formula-injection neutralization.

Audit logs support event, actor, subject, and date filtering plus formatted before/after detail.

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

Previously reported on the failing run:

```text
OpenAPI lint: successful
MySQL concurrency suite: successful
SQLite feature suite: failed because Vite manifest was absent
```

A new run has been triggered by the workflow fix. Do not claim it passes until GitHub Actions confirms it.

## API documentation

Canonical contract: `docs/openapi.yaml`.

Web-only routes are not part of OpenAPI. Canonical OpenAPI still needs dashboard, CSV response, exact schemas, and remaining admin API coverage.

## Next recommended work

1. Confirm the SQLite feature suite is green after the Vite build fix.
2. Fix any subsequent application-level test failure if one appears.
3. Complete canonical OpenAPI coverage.
4. Start customer web, then Flutter driver integration.

## Response format rule

After backend changes respond in this exact order:

1. **Changes**
2. **Endpoint changes**
3. **Cara pull changes**
4. **cURL Postman**
5. **Expected result cURL**
