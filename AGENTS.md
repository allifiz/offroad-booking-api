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

1. Inspect current models, enums, migrations, controllers, routes, tests, and API documentation before changing behavior.
2. Apply backend changes directly to `main`, unless the user requests another branch.
3. Preserve existing enums and relationships unless a migration is required.
4. Operational vehicles belong to drivers through `vehicles.driver_profile_id`.
5. Never expose real access tokens or claim tests passed unless executed.
6. Update this file and `PROJECT_PROGRESS.md` with project changes.
7. Keep `docs/openapi.yaml` synchronized with endpoint, payload, status-code, authentication, and rate-limit changes.
8. cURL delivery must be a complete test flow from prerequisite setup and all role logins through the main action, success verification, and important regression failures.

## Required response structure

After every backend change, respond in this exact order:

1. **Changes**
2. **Endpoint changes**
3. **Cara pull changes**
4. **cURL Postman**
5. **Expected result cURL**

Use Indonesian, ready-to-run PowerShell, importable full-flow cURL, expected HTTP status/JSON, migration requirements, test status, and latest commit SHA.

## Product decisions

- Actors: admin, driver, customer.
- Driver and vehicle registration start pending/unavailable.
- Admin verifies drivers, vehicles, driver documents, and vehicle documents.
- Driver-owned vehicles are created with ownership `driver`, verification `pending`, and availability `unavailable`.
- Vehicle identity/capacity or media-content changes reset verification to pending and availability to unavailable.
- Vehicles with offered or accepted assignments cannot be deleted.
- Admin offers assignments; drivers accept or reject them.
- Assignment conflicts use accepted assignments on the same tour date.
- Assignment creation requires a paid booking.
- Booking transitions are strict: pending → confirmed/cancelled, confirmed → ongoing/cancelled, ongoing → completed, completed/cancelled final.
- Participant allocation targets accepted assignments from the same booking and may not exceed vehicle capacity.
- Completing a booking awards each accepted driver configurable points once per booking.
- Withdrawal balance mutation locks the driver-profile row in MySQL so parallel requests cannot spend the same points.
- Sensitive model create/update/delete operations are automatically audited.
- Operational notifications are stored in the database and dispatched through Laravel queue after transaction commit.
- Notification ownership is isolated.
- Rate limiting is risk-based: login by email+IP, public registration by IP, authenticated reads by user, uploads by user, withdrawals by driver, and admin writes by admin.
- Backend tests and OpenAPI lint must run autonomously through GitHub Actions on every push or pull request to `main`.
- OpenAPI 3.1 at `docs/openapi.yaml` is the canonical API contract.

## Implemented progress

### Core transaction flow

- Customer registration/profile, tour packages, bookings, participants, payments, ownership isolation, and strict booking state machine.
- Driver registration, verification, dashboard, documents, vehicle CRUD/media, availability, and assignment response.
- Travel groups and participant-to-vehicle allocation with capacity and cross-booking isolation.
- Points, HOLD/RELEASE/DEBIT ledger behavior, withdrawal processing, and completion reward idempotency.

### Production hardening

- Central `AuditObserver` for sensitive models and admin read-only audit endpoints.
- Queued database notifications for payment, booking, assignment, verification, and withdrawal state changes.
- Shared authenticated notification inbox with unread filtering and read actions.
- `WithdrawalService` with transaction, row lock, retry, and dedicated two-process MySQL concurrency test.
- Named Laravel rate limiters:
  - `auth-login`: 5/minute per normalized email + IP
  - `public-registration`: 3/hour per IP
  - `authenticated-read`: 120/minute per user/IP
  - `customer-write`: 20/minute per user/IP
  - `driver-write`: 30/minute per user/IP
  - `file-upload`: 10/minute per user/IP
  - `withdrawal-request`: 3/hour per user/IP
  - `admin-write`: 60/minute per user/IP
- OpenAPI 3.1 specification documents public, auth, notification, customer, driver, and core admin operations.
- Reusable schemas cover Bearer auth, common errors, pagination, uploads, `429` headers, and main request payloads.
- `docs/README.md` explains Redoc preview, Swagger UI preview, linting, and Postman import.

### Autonomous CI

Workflow: `.github/workflows/backend-tests.yml`.

Jobs:

- `OpenAPI lint` using `@redocly/cli`.
- `SQLite feature suite` on PHP 8.3.
- `MySQL concurrency suite` with MySQL 8.4.

The workflow runs on pushes and pull requests targeting `main`, plus manual `workflow_dispatch`.

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
- `tests/Integration/MySql/ConcurrentWithdrawalTest.php`

## MySQL concurrency test setup

- Dedicated config: `phpunit.mysql.xml`.
- Default database: `offroad_booking_test` on `127.0.0.1:3306`, user `root`, empty password.
- Test executes `migrate:fresh`; never point it at development or production.
- Internal worker command: `php artisan withdrawal:attempt`.

## Verification status and limitations

- GitHub Actions is the primary autonomous runtime validator.
- Do not claim a job passes until its workflow result is available.
- Standard suite uses SQLite memory; row-lock concurrency uses the dedicated MySQL suite.
- OpenAPI is linted by Redocly on each workflow run.

Local fallback:

```powershell
php artisan optimize:clear
php artisan migrate
php artisan test
php artisan test --configuration=phpunit.mysql.xml
npx --yes @redocly/cli@latest lint docs/openapi.yaml
```

## Latest relevant commits

- `f96ac069d5815b7f305a62d046574268c2094306` — OpenAPI usage guide.
- `536a6e05b620824aa14db5e0d89ea13521ef7359` — add OpenAPI lint CI job.
- `799d9be90d821965d4d8b764531a1f4ded47a857` — add OpenAPI 3.1 contract.
- `a03fdc0c4c79c3eba83da676a152691d9c109c8a` — autonomous SQLite/MySQL CI.
- `e20b6c716142e4420a43be4453ebbee3e3fbe7f7` — public rate-limit tests.
- `89c5df69849a86451eafd675ae50c1bbcded4eb6` — MySQL concurrent withdrawal test.

## Next progress list

1. Inspect and fix GitHub Actions failures when results are available.
2. Expand OpenAPI to every remaining admin CRUD/list endpoint and exact response schemas.
3. Configure production queue worker/supervision.
4. Add reporting/dashboard metrics.
5. Prepare backup, deployment, monitoring, and client integration.
