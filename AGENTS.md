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

## Implemented progress

### Core flow

- Customer registration/profile, bookings, participants, payments, ownership isolation.
- Driver registration/profile/verification, documents, vehicles/media, assignments.
- Travel groups and participant-to-vehicle allocation.
- Points, completion rewards, withdrawals, audit logs, notifications, and rate limiting.

### Production queue hardening

- Queue database tables already exist in `0001_01_01_000002_create_jobs_table.php`.
- `OperationalNotification` uses queue `notifications`, dispatches after commit, and has:
  - 5 tries
  - 30 second timeout
  - fail on timeout
  - backoff `[10, 60, 300, 900]`
- `config/queue_health.php` defines pending, stale, and failed job thresholds.
- `php artisan queue:health` provides human-readable queue status.
- `php artisan queue:health --json` provides machine-readable status and exits non-zero when unhealthy.
- Supervisor config: `deploy/supervisor/offroad-booking-worker.conf`.
- Operations guide: `docs/QUEUE_PRODUCTION.md`.
- `.env.example` documents database queue, retry-after, failed-job, and health threshold variables.
- `DB_QUEUE_RETRY_AFTER` must remain greater than the worker timeout.

### Autonomous CI

Workflow: `.github/workflows/backend-tests.yml`.

Confirmed jobs:

- OpenAPI lint: passing.
- SQLite feature suite: passing.
- MySQL concurrency suite: passing after shortening MySQL identifiers and invoking PHPUnit directly.

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
- `tests/Integration/MySql/ConcurrentWithdrawalTest.php`

## Verification status

- Existing CI was green before queue hardening.
- Queue hardening changes are committed and the new CI run must be checked before claiming `QueueHealthFlowTest` passes.
- GitHub Actions remains the primary autonomous validator.

## Next progress list

1. Inspect and fix any queue-hardening CI failure.
2. Add admin reporting/dashboard metrics.
3. Expand OpenAPI exact response schemas and remaining admin CRUD endpoints.
4. Prepare backup, deployment, monitoring, and frontend/Flutter integration.
