# Offroad Booking — Project Progress Checkpoint

Last updated: 2026-07-20 (Asia/Jakarta)
Branch: `main`
Repository: `allifiz/offroad-booking-api`
Local path: `C:\Projects\offroad-booking-api`

## Current status

- Backend core MVP: approximately 99%.
- Backend production readiness: approximately 96%.
- Laravel admin web: foundation implemented.

Backend includes the complete booking/payment/assignment/allocation/reward/withdrawal flow, audit logs, notifications, rate limiting, queue hardening, reporting, CSV exports, health checks, deployment, backup, and recovery tooling.

## Laravel admin web foundation

Routes:

```text
GET  /admin/login
POST /admin/login
GET  /admin
POST /admin/logout
```

Implemented:

- Laravel session login for active admin users.
- Session regeneration after login and invalidation after logout.
- Separate `admin.web` middleware for browser-safe authorization behavior.
- Responsive Blade login page.
- Responsive admin shell/sidebar.
- Dashboard period filter.
- Booking, booking-value, payment, and operational-queue cards.
- Recent booking table.
- Direct database reads rather than internal API-over-HTTP calls.
- Feature coverage in `tests/Feature/AdminWebFlowTest.php`.

Files:

```text
app/Http/Middleware/EnsureAdminWeb.php
app/Http/Controllers/Web/Admin/AuthController.php
app/Http/Controllers/Web/Admin/DashboardController.php
resources/views/admin/auth/login.blade.php
resources/views/admin/dashboard.blade.php
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

CI was confirmed green immediately before the admin web changes. Do not claim `AdminWebFlowTest` passes until the new workflow result is confirmed.

## API documentation

Canonical contract: `docs/openapi.yaml`.

The canonical OpenAPI still needs dashboard, CSV response, exact schema, and remaining admin CRUD coverage. Web-only routes are not part of the OpenAPI contract.

## Next recommended work

1. Inspect and fix any admin web CI failure.
2. Implement admin payment verification page and actions.
3. Implement booking operations and assignment/allocation pages.
4. Implement driver, vehicle, withdrawal, reports, and audit pages.
5. Start customer web and Flutter driver integration.

## Response format rule

After backend changes respond in this exact order:

1. **Changes**
2. **Endpoint changes**
3. **Cara pull changes**
4. **cURL Postman**
5. **Expected result cURL**
