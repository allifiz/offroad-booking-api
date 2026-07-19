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
- Driver-owned vehicles are always created with ownership `driver`, verification `pending`, and availability `unavailable`.
- Vehicle identity/capacity or media-content changes reset verification to pending and availability to unavailable.
- Vehicles with offered or accepted assignments cannot be deleted.
- Admin offers assignments; drivers accept or reject them.
- Assignment conflicts use accepted assignments on the same tour date.
- Assignment creation requires a paid booking.
- Booking transitions are strict: pending → confirmed/cancelled, confirmed → ongoing/cancelled, ongoing → completed, completed/cancelled final.
- Participant allocation targets accepted assignments from the same booking and may not exceed vehicle capacity.
- Completing a booking awards each accepted driver configurable points once per booking.
- Withdrawal creation moves available points to held; rejection releases them; paid removes them from held.
- Withdrawal balance mutation must lock the driver-profile row in MySQL so parallel requests cannot spend the same points.
- Sensitive model create/update/delete operations are automatically audited.
- Operational notifications are stored in the database and dispatched through Laravel queue after transaction commit.
- Notification ownership is isolated; users can only read their own inbox entries.

## Implemented progress

### Foundation and actors

- Laravel 13 + MySQL/MariaDB, Sanctum, role middleware, tour packages, vehicles.
- Driver registration, verification, dashboard, availability, documents, vehicle CRUD/media, and document re-upload.
- Customer registration/profile, bookings, participants, and ownership isolation.

### Booking transaction flow

- Payment submission/admin verification, paid assignment guard, assignment response, strict booking state machine.
- Travel groups and participant-to-vehicle allocation with ownership and capacity validation.

### Points and withdrawal

- Booking completion credits accepted drivers once per booking.
- Driver point summary, ledger, withdrawal request/list.
- Admin strict withdrawal processing: pending → approved/rejected, approved → paid.
- Withdrawal request logic lives in `WithdrawalService` and uses a row lock plus a retryable transaction.
- The API controller and the concurrency worker command use the same service, avoiding divergent financial logic.

### Audit logs

- Central `AuditObserver` records created, updated, and deleted events for sensitive models.
- Admin can paginate/filter logs and view detail; audit endpoints are read-only.

### Notifications and queues

- `OperationalNotification` implements `ShouldQueue`, uses the database channel, and dispatches after commit.
- Automatic notifications cover payment status, booking status, assignment offer/response, driver verification, vehicle verification, and withdrawal status.
- Authenticated admin/customer/driver users share one notification inbox API.
- Users can filter unread entries, mark one entry read, or mark all entries read.

### Critical feature tests

- `DriverWithdrawalFlowTest`
- `BookingStateAndRewardFlowTest`
- `PaymentFlowTest`
- `DriverAssignmentResponseFlowTest`
- `ParticipantAllocationFlowTest`
- `DriverVehicleCrudFlowTest`
- `VehicleMediaFlowTest`
- `AuditLogFlowTest`
- `NotificationFlowTest`
- `tests/Integration/MySql/ConcurrentWithdrawalTest.php` launches two separate Artisan worker processes against the same MySQL database and asserts exactly one withdrawal succeeds.
- Standard feature tests use SQLite memory; row-lock concurrency uses the dedicated MySQL suite.

## MySQL concurrency test setup

- Dedicated PHPUnit config: `phpunit.mysql.xml`.
- Default database: `offroad_booking_test` on `127.0.0.1:3306`, user `root`, empty password.
- The test executes `migrate:fresh`; never point this configuration at development or production data.
- Internal worker command: `php artisan withdrawal:attempt`.

## Current relevant endpoints

```text
GET   /api/v1/notifications
PATCH /api/v1/notifications/read-all
PATCH /api/v1/notifications/{notification}/read
GET   /api/v1/admin/audit-logs
GET   /api/v1/admin/audit-logs/{auditLog}
POST  /api/v1/driver/withdrawals
```

All protected endpoints require Sanctum and the corresponding role where applicable.

## Latest relevant commits

- `89c5df69849a86451eafd675ae50c1bbcded4eb6` — two-process MySQL concurrent withdrawal integration test.
- `33b33ce0676be9795384dc40d5a3c82a909ae09b` — dedicated MySQL PHPUnit configuration.
- `b13741a5f012e9f9ed8adbcd09be7e728681445b` — internal withdrawal concurrency worker command.
- `b876e9bc8dcbbd2c187914501fe4a2f6db80e496` — route API withdrawal creation through the shared service.
- `9a24bf9770fb04f5139563eb74ab6f3735221c2e` — locked withdrawal service.
- `038c50f470af8899a4313505529d8b4ad81607a8` — notification inbox feature tests.
- `a92900d5a7a52a3a95a4faabf29aba28a81c4830` — audit log feature tests.

## Verification status and limitations

- Runtime tests were not executed in this environment because the GitHub connector has no PHP or MySQL runtime.
- The concurrency test is intentionally excluded from the default SQLite suite.
- `phpunit.mysql.xml` calls `migrate:fresh`, so its database must be an isolated disposable test database.
- Windows is supported because the test uses Symfony Process instead of `pcntl_fork`.
- Existing withdrawal feature tests should be rerun after the service refactor.
- Run locally:

```powershell
php artisan optimize:clear
php artisan migrate
php artisan test --filter=DriverWithdrawalFlowTest
php artisan test
php artisan test --configuration=phpunit.mysql.xml
```

## Next progress list

### Priority 1 — Verification

- run/fix the standard full suite
- run/fix the dedicated MySQL concurrency suite

### Priority 2 — Production hardening

- production queue configuration and worker supervision
- rate limiting and OpenAPI documentation
- reports, backup, deployment, and client integration

## Recommended immediate continuation

```text
Run/fix standard and MySQL test suites
→ Add rate limiting
→ Add OpenAPI documentation
→ Prepare deployment and backup
```
