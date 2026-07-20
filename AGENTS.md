# AGENTS.md

## Project identity

- Project: Offroad Booking Web App
- Repository: `allifiz/offroad-booking-api`
- Backend/API and web clients: Laravel 13
- Database: MySQL/MariaDB
- API authentication: Laravel Sanctum
- Admin web authentication: Laravel session
- Driver client: Flutter native
- Main branch: `main`
- Local path: `C:\Projects\offroad-booking-api`

## Mandatory workflow

1. Inspect models, migrations, controllers, routes, tests, queue behavior, deployment scripts, frontend assets, and API documentation before changing behavior.
2. Apply changes directly to `main`, unless the user requests another branch.
3. Never expose real secrets or claim tests pass unless CI/runtime confirms them.
4. Update this file and `PROJECT_PROGRESS.md` after project changes.
5. Keep `docs/openapi.yaml` synchronized with API endpoint and payload changes.
6. After backend changes respond in this order: Changes, Endpoint changes, Cara pull changes, cURL Postman, Expected result cURL.

## Implemented system

- Complete customer, booking, payment, driver, vehicle, assignment, allocation, reward, withdrawal, audit, and notification API flows.
- Risk-based rate limiting, queued notifications, MySQL concurrency protection, reporting, CSV export, health checks, backup/deploy scripts, and autonomous CI.
- GitHub Actions jobs: OpenAPI lint, SQLite suite, and MySQL concurrency suite.

## Admin web

- Session routes: `/admin/login`, `/admin`, `/admin/logout`.
- Only active admin users may log in; authenticated non-admin users receive web `403`.
- Dashboard uses direct database queries and includes period metrics, recent bookings, and operational queues.
- Payment operations:
  - `GET /admin/payments`
  - `GET /admin/payments/{payment}`
  - `PATCH /admin/payments/{payment}`
- Payment list supports status filtering and booking/customer search.
- Pending payments can be approved or rejected; rejection requires a reason.
- Payment and booking payment statuses update in one database transaction.
- Customers receive queued operational notifications after approval/rejection.
- Views: `resources/views/admin/payments/index.blade.php` and `show.blade.php`.
- Tests: `AdminWebFlowTest`, `AdminWebPaymentFlowTest`.

## Production operations

- Queue health: `php artisan queue:health` and `--json`.
- Application health: `php artisan app:health` and `--json`.
- Supervisor: `deploy/supervisor/offroad-booking-worker.conf`.
- Deploy script: `deploy/scripts/deploy.sh`.
- Backup script: `deploy/scripts/backup.sh`.
- Runbooks: `docs/QUEUE_PRODUCTION.md`, `docs/PRODUCTION_DEPLOYMENT.md`.

## Verification status

- CI was confirmed green before the admin payment web changes.
- Do not claim `AdminWebPaymentFlowTest` passes until the new CI run is confirmed.
- GitHub Actions remains the primary autonomous validator.

## Next progress list

1. Inspect and fix any admin payment web CI failure.
2. Build booking operations pages and actions.
3. Build driver/vehicle verification, withdrawals, reports, and audit pages.
4. Finish canonical OpenAPI dashboard/CSV/admin schemas.
5. Start customer web and Flutter driver integration.
