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

## Admin web foundation

- Routes:
  - `GET /admin/login`
  - `POST /admin/login`
  - `GET /admin`
  - `POST /admin/logout`
- Uses Laravel session authentication; only active users with role `admin` may log in.
- `EnsureAdminWeb` returns a web `403` for authenticated non-admin users.
- Dashboard uses direct database queries, not internal HTTP calls to the API.
- Current dashboard includes period filtering, booking/payment cards, operational queues, and recent bookings.
- Blade pages:
  - `resources/views/admin/auth/login.blade.php`
  - `resources/views/admin/dashboard.blade.php`
- Test: `AdminWebFlowTest`.

## Production operations

- Queue health: `php artisan queue:health` and `--json`.
- Application health: `php artisan app:health` and `--json`.
- Supervisor: `deploy/supervisor/offroad-booking-worker.conf`.
- Deploy script: `deploy/scripts/deploy.sh`.
- Backup script: `deploy/scripts/backup.sh`.
- Runbooks: `docs/QUEUE_PRODUCTION.md`, `docs/PRODUCTION_DEPLOYMENT.md`.

## Admin reporting API

- `GET /api/v1/admin/dashboard`.
- CSV exports for bookings, payments, drivers, and withdrawals.
- Guides: `docs/ADMIN_DASHBOARD.md`, `docs/CSV_REPORTS.md`.

## Verification status

- CI was confirmed green before the admin web foundation changes.
- Do not claim `AdminWebFlowTest` passes until the new CI run is confirmed.
- GitHub Actions remains the primary autonomous validator.

## Next progress list

1. Inspect and fix any admin web CI failure.
2. Build admin payment verification list/detail/actions.
3. Build booking operations, driver/vehicle verification, withdrawals, reports, and audit pages.
4. Finish canonical OpenAPI dashboard/CSV/admin schemas.
5. Start customer web and Flutter driver integration.
