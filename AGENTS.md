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
- Backend tests and OpenAPI lint run autonomously through GitHub Actions on every push or pull request to `main`.
- OpenAPI 3.0.3 at `docs/openapi.yaml` is the canonical API contract.

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
- OpenAPI specification documents public, auth, notification, customer, driver, and core admin operations.
- `docs/README.md` explains Redoc preview, Swagger UI preview, linting, and Postman import.

### Autonomous CI

Workflow: `.github/workflows/backend-tests.yml`.

Jobs:

- `OpenAPI lint` using `@redocly/cli` and `redocly.yaml`.
- `SQLite feature suite` on PHP 8.4; creates `database/database.sqlite` before Laravel bootstrap.
- `MySQL concurrency suite` on PHP 8.4 with MySQL 8.4.

CI preparation does not call `php artisan optimize:clear` before database configuration because Laravel's cache clear may access the configured database cache store.
The MySQL suite calls `vendor/bin/phpunit -c phpunit.mysql.xml` directly so PHPUnit receives one configuration file only.
MySQL constraint and index names on long tables must be explicitly shortened to stay under MySQL's 64-character identifier limit.

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
- `tests/Integration/MySql/ConcurrentWithdrawalTest.php`

## Verification status and limitations

- GitHub Actions is the primary autonomous runtime validator.
- OpenAPI lint and SQLite feature suite were confirmed passing after CI bootstrap fixes.
- MySQL concurrency suite previously failed during migration because an automatically generated foreign-key name exceeded MySQL's 64-character limit; explicit short constraint names were added.
- Do not claim the MySQL concurrency suite passes until the next workflow result is available.
- Standard suite uses SQLite memory; row-lock concurrency uses the dedicated MySQL suite.

## Latest relevant commits

- `8c7b4e66bfccecfb0a261aae1475fea3547fb2b8` — run MySQL suite directly with the dedicated PHPUnit configuration.
- `6f184729d969067611c9a5085066fab0de6a9179` — shorten participant-allocation MySQL constraint and index names.
- `d0504a94fc49f40ce2b1880673bcb08fc7a9961a` — configure Redocly rules for the current OpenAPI contract.
- `c478749e12c4a5df456d275abc3ed156fcba6ed6` — create SQLite database and avoid premature database cache clearing in CI.
- `7f088a9eb02ec75f66d0a0140bc28e2932deb336` — align spec to OpenAPI 3.0.3.
- `f6504349b594d9dbf7c8db421e97f85bab212ba3` — run CI with PHP 8.4.

## Next progress list

1. Inspect the next MySQL concurrency workflow result and fix any remaining MySQL-specific migration/test issue.
2. Expand OpenAPI to remaining admin endpoints and exact response schemas.
3. Configure production queue worker/supervision.
4. Add reporting/dashboard metrics.
5. Prepare backup, deployment, monitoring, and client integration.
