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

1. Inspect current models, enums, migrations, controllers, routes, and tests before changing behavior.
2. Apply backend changes directly to `main`, unless the user requests another branch.
3. Preserve existing enums and relationships unless a migration is required.
4. Operational vehicles belong to drivers through `vehicles.driver_profile_id`.
5. Never expose real access tokens or claim tests passed unless executed.
6. Update this file with every backend/project change.
7. cURL delivery must be a complete test flow from prerequisite setup and all role logins through the main action, success verification, and important regression failures.

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

- Runtime tests were not executed in this environment because the GitHub connector has no PHP or MySQL runtime.
- Standard suite uses SQLite memory; row-lock concurrency uses the dedicated MySQL suite.
- Rate-limit tests were added but still need local execution.
- Run locally:

```powershell
php artisan optimize:clear
php artisan migrate
php artisan test --filter=RateLimitFlowTest
php artisan test
php artisan test --configuration=phpunit.mysql.xml
```

## Latest relevant commits

- `e20b6c716142e4420a43be4453ebbee3e3fbe7f7` — public rate-limit feature tests.
- `8b2b92e8cef1f2a5c16ea640ae103eaf0794cb45` — apply rate-limit middleware to routes.
- `615d775963c0a6ab5c372221af0794c17b4b9747` — configure named API rate limiters.
- `89c5df69849a86451eafd675ae50c1bbcded4eb6` — MySQL concurrent withdrawal test.
- `038c50f470af8899a4313505529d8b4ad81607a8` — notification feature tests.
- `a92900d5a7a52a3a95a4faabf29aba28a81c4830` — audit-log feature tests.

## Next progress list

1. Run/fix standard full suite.
2. Run/fix dedicated MySQL concurrency suite.
3. Add OpenAPI documentation.
4. Configure production queue worker/supervision.
5. Add reporting/dashboard metrics.
6. Prepare backup, deployment, monitoring, and client integration.
