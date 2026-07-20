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

- Session login/dashboard/logout for active admin users.
- Payment list/detail/approve/reject operations with queued customer notifications.
- Booking operations:
  - `GET /admin/bookings`
  - `GET /admin/bookings/{booking}`
  - `PATCH /admin/bookings/{booking}/status`
  - `POST /admin/bookings/{booking}/assignments`
  - `PATCH /admin/bookings/{booking}/assignments/{assignment}/cancel`
- Booking list supports status, payment status, and booking/customer search.
- Web status transitions currently support pending→confirmed/cancelled and confirmed→ongoing/cancelled.
- Web completion is intentionally blocked until reward logic is centralized, preventing completion without idempotent point rewards.
- Assignment requires paid non-final booking, approved available driver, and approved available driver-owned vehicle.
- Tests: `AdminWebFlowTest`, `AdminWebPaymentFlowTest`, `AdminWebBookingFlowTest`.

## Production operations

- Queue health: `php artisan queue:health` and `--json`.
- Application health: `php artisan app:health` and `--json`.
- Supervisor: `deploy/supervisor/offroad-booking-worker.conf`.
- Deploy script: `deploy/scripts/deploy.sh`.
- Backup script: `deploy/scripts/backup.sh`.
- Runbooks: `docs/QUEUE_PRODUCTION.md`, `docs/PRODUCTION_DEPLOYMENT.md`.

## Verification status

- CI was confirmed green before the admin booking web changes.
- Do not claim `AdminWebBookingFlowTest` passes until the new CI run is confirmed.
- GitHub Actions remains the primary autonomous validator.

## Next progress list

1. Inspect and fix any admin booking web CI failure.
2. Centralize booking status/reward logic in a shared service, then enable web completion.
3. Build participant allocation and driver/vehicle verification web pages.
4. Build withdrawals, reports, and audit pages.
5. Finish canonical OpenAPI and start customer web/Flutter integration.
