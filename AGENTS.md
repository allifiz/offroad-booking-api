# AGENTS.md

## Project identity

- Project: Offroad Booking Web App
- Repository: `allifiz/offroad-booking-api`
- Backend/API: Laravel 13
- Database: MySQL/MariaDB via XAMPP
- Authentication: Laravel Sanctum
- Admin and customer clients: Laravel web
- Driver client: Flutter native
- Main working branch: `main`
- Local backend path: `C:\Projects\offroad-booking-api`

## Mandatory workflow

1. Inspect current models, migrations, controllers, routes, tests, queue behavior, and API documentation before changing behavior.
2. Apply backend changes directly to `main`, unless the user requests another branch.
3. Never expose real access tokens or claim tests pass unless CI/runtime confirms them.
4. Update this file and `PROJECT_PROGRESS.md` with project changes.
5. Keep `docs/openapi.yaml` synchronized with endpoint and payload changes.
6. After backend changes respond in this order: Changes, Endpoint changes, Cara pull changes, cURL Postman, Expected result cURL.

## Product decisions

- Actors: admin, driver, customer.
- Driver and vehicle registration start pending/unavailable.
- Assignment creation requires a paid booking; drivers accept or reject offers.
- Booking state transitions are strict.
- Participant allocation may not exceed accepted vehicle capacity.
- Booking completion awards accepted drivers once per booking.
- Withdrawal mutations lock the driver profile row and move points through HOLD/RELEASE/DEBIT.
- Sensitive model mutations are audited.
- Operational notifications are queued after transaction commit.
- API rate limiting is risk-based.
- OpenAPI lint and all backend tests run autonomously in GitHub Actions.
- Dashboard period metrics use record `created_at`; driver and vehicle metrics are current snapshots.

## Implemented progress

### Core flow

- Customer registration/profile, bookings, participants, payments, ownership isolation.
- Driver registration/profile/verification, documents, vehicles/media, assignments.
- Travel groups and participant-to-vehicle allocation.
- Points, completion rewards, withdrawals, audit logs, notifications, and rate limiting.

### Production queue hardening

- Queue database tables already exist in `0001_01_01_000002_create_jobs_table.php`.
- `OperationalNotification` uses queue `notifications`, dispatches after commit, and has 5 tries, a 30-second timeout, fail-on-timeout, and backoff `[10, 60, 300, 900]`.
- `config/queue_health.php` defines pending, stale, and failed job thresholds.
- `php artisan queue:health` and `php artisan queue:health --json` expose queue status.
- Supervisor config: `deploy/supervisor/offroad-booking-worker.conf`.
- Operations guide: `docs/QUEUE_PRODUCTION.md`.
- `DB_QUEUE_RETRY_AFTER` must remain greater than the worker timeout.

### Admin dashboard metrics

- Endpoint: `GET /api/v1/admin/dashboard`.
- Optional `date_from` and `date_to`; default latest 30 days; maximum 366 days.
- Period metrics: bookings by status, participants, gross booking value, payments by status, paid/pending/refunded amounts, withdrawals by status, requested points, paid and pending withdrawal amounts.
- Current snapshot metrics: driver verification/availability/available and held points; vehicle verification/availability.
- Daily trend is zero-filled for every day in the selected period and includes bookings, gross booking value, and paid revenue.
- Customer and driver roles receive `403`.
- Documentation: `docs/ADMIN_DASHBOARD.md`.

### Autonomous CI

Workflow: `.github/workflows/backend-tests.yml`.

Confirmed before dashboard changes:

- OpenAPI lint: passing.
- SQLite feature suite: passing.
- MySQL concurrency suite: passing.

### Critical tests

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
- `tests/Integration/MySql/ConcurrentWithdrawalTest.php`

## Verification status

- Existing CI was green before admin dashboard changes.
- Dashboard and queue-hardening changes are committed; the current CI run must be checked before claiming their new tests pass.
- GitHub Actions remains the primary autonomous validator.

## Next progress list

1. Inspect and fix any dashboard/queue CI failure.
2. Add downloadable CSV reports for bookings, payments, drivers, and withdrawals.
3. Expand OpenAPI exact response schemas and remaining admin CRUD endpoints.
4. Prepare backup, deployment, monitoring, and frontend/Flutter integration.
