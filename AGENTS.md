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

## Shared booking lifecycle

- `App\Services\BookingLifecycleService` is the canonical implementation for booking transitions.
- Both API and Admin Web must call this service rather than duplicating status/reward logic.
- It enforces strict transitions, paid-booking requirements, accepted-assignment requirements, cancellation propagation, row locking, completion reward idempotency, and point-ledger creation.
- Completion retries the database transaction up to three times.

## Admin web

- Session routes: `/admin/login`, `/admin`, `/admin/logout`.
- Dashboard, payment verification, and booking operations are implemented.
- Booking routes support list/detail, status transition, assignment offer/cancel, and participant allocation.
- Participant allocation only accepts assignments with status `accepted`, enforces booking ownership and vehicle capacity, and uses one allocation per participant.
- Booking completion from Admin Web now uses `BookingLifecycleService`, so driver rewards match the API flow.
- Tests: `AdminWebFlowTest`, `AdminWebPaymentFlowTest`, `AdminWebBookingFlowTest`, and `AdminWebBookingLifecycleFlowTest`.

## Production operations

- Queue health: `php artisan queue:health` and `--json`.
- Application health: `php artisan app:health` and `--json`.
- Supervisor: `deploy/supervisor/offroad-booking-worker.conf`.
- Deploy script: `deploy/scripts/deploy.sh`.
- Backup script: `deploy/scripts/backup.sh`.
- Runbooks: `docs/QUEUE_PRODUCTION.md`, `docs/PRODUCTION_DEPLOYMENT.md`.

## Verification status

- CI was confirmed green before the shared lifecycle/admin allocation changes.
- Do not claim `AdminWebBookingLifecycleFlowTest` or the lifecycle refactor passes until the newest CI run is confirmed.
- GitHub Actions remains the primary autonomous validator.

## Next progress list

1. Inspect and fix any lifecycle/allocation CI failure.
2. Build driver and vehicle verification pages.
3. Build withdrawal, reports, and audit pages.
4. Finish canonical OpenAPI dashboard/CSV/admin schemas.
5. Start customer web and Flutter driver integration.
