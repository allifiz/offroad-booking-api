# AGENTS.md

## Project identity

- Project: Offroad Booking Web App
- Repository: `allifiz/offroad-booking-api`
- Backend/API: Laravel 13
- Database: MySQL/MariaDB
- Authentication: Laravel Sanctum
- Admin and customer clients: Laravel web
- Driver client: Flutter native
- Main branch: `main`
- Local path: `C:\Projects\offroad-booking-api`

## Mandatory workflow

1. Inspect models, migrations, controllers, routes, tests, queue behavior, deployment scripts, and API documentation before changing behavior.
2. Apply backend changes directly to `main`, unless the user requests another branch.
3. Never expose real secrets or claim tests pass unless CI/runtime confirms them.
4. Update this file and `PROJECT_PROGRESS.md` after project changes.
5. Keep `docs/openapi.yaml` synchronized with endpoint and payload changes.
6. After backend changes respond in this order: Changes, Endpoint changes, Cara pull changes, cURL Postman, Expected result cURL.

## Implemented system

- Customer registration/profile, bookings, participants, payments, and ownership isolation.
- Driver registration/profile/verification, documents, vehicles/media, assignments, points, and withdrawals.
- Strict booking state transitions, travel groups, allocation, and completion rewards.
- Audit logs, queued notifications, notification inbox, and risk-based rate limits.
- MySQL row locking and concurrent withdrawal integration coverage.
- Autonomous GitHub Actions: OpenAPI lint, SQLite suite, and MySQL concurrency suite.

## Production queue

- `OperationalNotification` queue: `notifications`.
- 5 tries, 30-second timeout, fail on timeout, backoff `[10, 60, 300, 900]`.
- Queue health:
  - `php artisan queue:health`
  - `php artisan queue:health --json`
- Supervisor: `deploy/supervisor/offroad-booking-worker.conf`.
- Guide: `docs/QUEUE_PRODUCTION.md`.

## Admin reporting

- Dashboard: `GET /api/v1/admin/dashboard`.
- CSV exports:
  - `GET /api/v1/admin/reports/export/bookings`
  - `GET /api/v1/admin/reports/export/payments`
  - `GET /api/v1/admin/reports/export/drivers`
  - `GET /api/v1/admin/reports/export/withdrawals`
- CSV output is streamed, UTF-8 BOM, no-store, nosniff, and formula-prefix neutralized.
- Guides: `docs/ADMIN_DASHBOARD.md`, `docs/CSV_REPORTS.md`.

## Production deployment and monitoring

- Application health:
  - `php artisan app:health`
  - `php artisan app:health --json`
- Checks database connectivity, writable default storage, and queue tables.
- Deploy script: `deploy/scripts/deploy.sh`.
- Backup script: `deploy/scripts/backup.sh`.
- Backups include compressed MySQL dump, public storage archive, protected `.env` copy, and SHA-256 checksums.
- Default backup retention: 14 days.
- Deployment/recovery runbook: `docs/PRODUCTION_DEPLOYMENT.md`.
- Deployment must run backup first, migrate with `--force`, rebuild caches, restart workers, and pass `app:health` before reopening.

## Critical tests

- `DriverWithdrawalFlowTest`
- `BookingStateAndRewardFlowTest`
- `PaymentFlowTest`
- `DriverAssignmentResponseFlowTest`
- `ParticipantAllocationFlowTest`
- `DriverVehicleCrudFlowTest`
- `VehicleMediaFlowTest`
- `AuditLogFlowTest`
- `NotificationFlowTest`
- `RateLimitFlowTest`
- `QueueHealthFlowTest`
- `AdminDashboardFlowTest`
- `AdminReportExportFlowTest`
- `ApplicationHealthFlowTest`
- `tests/Integration/MySql/ConcurrentWithdrawalTest.php`

## Verification status

- CI was green before the newest CSV/deployment changes.
- Current CI must be checked before claiming the new report and health tests pass.
- GitHub Actions remains the primary autonomous validator.

## Next progress list

1. Fix any current CI failure.
2. Expand canonical OpenAPI for dashboard, CSV reports, exact schemas, and remaining admin CRUD endpoints.
3. Add external alert delivery and scheduled backup verification.
4. Start frontend Laravel admin/customer and Flutter driver integration.
