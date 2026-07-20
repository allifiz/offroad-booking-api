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

## Shared services

- `BookingLifecycleService` is canonical for booking transitions, cancellation propagation, row locking, completion rewards, and ledger idempotency.
- `WithdrawalService` is canonical for withdrawal requests and admin transitions.
- API and Admin Web must not duplicate booking or withdrawal balance logic.

## Admin web

- Session authentication, dashboard, payment verification, booking operations, participant allocation, driver/vehicle verification, and withdrawal operations are implemented.
- Reports page: `GET /admin/reports`.
- Session-protected CSV downloads reuse `Api\V1\Admin\ReportExportController`, preserving cursor streaming, UTF-8 BOM, no-store, nosniff, period validation, and formula-injection neutralization.
- Audit pages: `GET /admin/audit-logs` and `GET /admin/audit-logs/{auditLog}`.
- Audit filters cover event, actor name/email, subject type/id, and date range. Detail shows actor/request context plus formatted before/after JSON.
- Tests include `AdminWebReportsAuditFlowTest` plus existing admin web suites.

## Production operations

- Queue health: `php artisan queue:health` and `--json`.
- Application health: `php artisan app:health` and `--json`.
- Supervisor: `deploy/supervisor/offroad-booking-worker.conf`.
- Deploy script: `deploy/scripts/deploy.sh`.
- Backup script: `deploy/scripts/backup.sh`.
- Runbooks: `docs/QUEUE_PRODUCTION.md`, `docs/PRODUCTION_DEPLOYMENT.md`.

## Verification status

- CI was confirmed green before the reports/audit web changes.
- Do not claim `AdminWebReportsAuditFlowTest` passes until the newest CI run is confirmed.
- GitHub Actions remains the primary autonomous validator.

## Next progress list

1. Inspect and fix any reports/audit CI failure.
2. Finish canonical OpenAPI dashboard/CSV/admin schemas.
3. Start customer web.
4. Start Flutter driver integration.
