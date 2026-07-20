# Offroad Booking — Project Progress Checkpoint

Last updated: 2026-07-20 (Asia/Jakarta)
Branch: `main`
Repository: `allifiz/offroad-booking-api`
Local path: `C:\Projects\offroad-booking-api`

## Current status

- Backend core MVP: approximately 99%.
- Backend production readiness: approximately 96%.
- Laravel admin web: foundation plus payment verification implemented.

Backend includes the complete booking/payment/assignment/allocation/reward/withdrawal flow, audit logs, notifications, rate limiting, queue hardening, reporting, CSV exports, health checks, deployment, backup, and recovery tooling.

## Laravel admin web

Authentication and dashboard routes:

```text
GET  /admin/login
POST /admin/login
GET  /admin
POST /admin/logout
```

Payment routes:

```text
GET   /admin/payments
GET   /admin/payments/{payment}
PATCH /admin/payments/{payment}
```

Implemented:

- Laravel session login for active admin users.
- Browser-safe `admin.web` authorization middleware.
- Responsive Blade login and dashboard.
- Payment list with status filter and booking/customer search.
- Payment detail with transaction, customer, package, proof, and reviewer information.
- Pending payment approval and rejection.
- Rejection reason validation.
- Atomic payment and booking payment-status updates.
- Queued customer notification after approval or rejection.
- Dashboard payment links now open the operational payment queue.

Files:

```text
app/Http/Controllers/Web/Admin/PaymentController.php
resources/views/admin/payments/index.blade.php
resources/views/admin/payments/show.blade.php
tests/Feature/AdminWebPaymentFlowTest.php
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

CI was confirmed green immediately before the admin payment web changes. Do not claim `AdminWebPaymentFlowTest` passes until the latest workflow result is confirmed.

## API documentation

Canonical contract: `docs/openapi.yaml`.

Web-only routes are not part of OpenAPI. Canonical OpenAPI still needs dashboard, CSV response, exact schema, and remaining admin API coverage.

## Next recommended work

1. Inspect and fix any admin payment web CI failure.
2. Implement booking operations and assignment/allocation pages.
3. Implement driver and vehicle verification pages.
4. Implement withdrawal, reports, and audit pages.
5. Start customer web and Flutter driver integration.

## Response format rule

After backend changes respond in this exact order:

1. **Changes**
2. **Endpoint changes**
3. **Cara pull changes**
4. **cURL Postman**
5. **Expected result cURL**
