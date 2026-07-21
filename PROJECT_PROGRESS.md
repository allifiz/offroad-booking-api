# Offroad Booking — Project Progress Checkpoint

Last updated: 2026-07-21 (Asia/Jakarta)
Branch: `main`
Repository: `allifiz/offroad-booking-api`
Local path: `C:\Projects\offroad-booking-api`

## Current status

- Backend core MVP: approximately 99%.
- Backend production readiness: approximately 97%.
- Laravel admin web: core operational modules implemented, including reports and audit logs.

## CI verification in progress

A draft pull request from `agent/verify-backend-ci` is being used to trigger and verify the complete `Backend Tests` workflow without writing directly to `main`.

Required jobs:

```text
OpenAPI lint
SQLite feature suite
MySQL concurrency suite
```

Do not mark the backend CI green until all three jobs complete successfully on the current codebase.

## Latest SQLite run

After the Vite manifest fix, most feature tests passed. Three web regressions remained:

```text
AdminWebBookingFlowTest
AdminWebFlowTest
ExampleTest
```

### Guest redirect fix

Laravel authentication middleware previously expected a global route named `login`, while the project only defines `admin.login`.

`bootstrap/app.php` now explicitly configures:

```php
$middleware->redirectGuestsTo(fn (Request $request): string => route('admin.login'));
```

Commit:

```text
f09bc7ce24a784569792e298c51e9917c70b6a58
```

### Root route test fix

The root route intentionally redirects:

```text
/ → /admin
```

The old skeleton `ExampleTest` expected HTTP 200. It now asserts the intended redirect.

Commit:

```text
0780d82c251d88bcd568306c77062692103d22c3
```

### Booking list render hardening

The booking list now parses `tour_date` through Carbon and casts `total_amount` to float before formatting. This avoids a view-level 500 when SQLite hydration returns values in a different runtime shape.

Commit:

```text
388d7016e8e087fcf728e80a19c4c04d66c9786e
```

## CI frontend requirements

SQLite feature tests render Blade pages using Laravel `@vite`, so the workflow must generate:

```text
public/build/manifest.json
```

The job now runs:

```text
setup Node.js 22
→ npm install --ignore-scripts
→ npm run build
→ php artisan test
```

Workflow commit:

```text
ab13bbf80e1cea108c0fe837a15d9f81c77cd6ae
```

The repository currently has no `package-lock.json`, therefore CI uses `npm install` instead of `npm ci`.

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

Latest reported state before these three fixes:

```text
OpenAPI lint: successful
MySQL concurrency suite: successful
SQLite feature suite: failed with three web tests
```

A new workflow run is required. Do not claim it passes until GitHub Actions confirms it.

## API documentation

Canonical contract: `docs/openapi.yaml`.

Web-only routes are not part of OpenAPI. Canonical OpenAPI still needs dashboard, CSV response, exact schemas, and remaining admin API coverage.

## Next recommended work

1. Confirm the SQLite feature suite is green after the redirect, root-test, and booking-render fixes.
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
