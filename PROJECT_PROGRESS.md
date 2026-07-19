# Offroad Booking API — Project Progress Checkpoint

Last updated: 2026-07-20 (Asia/Jakarta)
Branch: `main`
Repository: `allifiz/offroad-booking-api`
Local path: `C:\Projects\offroad-booking-api`

## Current backend status

Estimated progress:

- Core functional MVP: approximately 92–94%
- Production readiness: approximately 78–82%

The end-to-end core flow is implemented:

```text
customer creates booking
→ uploads payment proof
→ admin approves/rejects payment
→ booking is confirmed
→ admin offers driver assignment
→ driver accepts/rejects
→ admin allocates participants to accepted vehicles within capacity
→ booking starts and completes
→ accepted drivers receive points once
→ driver requests withdrawal
→ admin approves/rejects/marks paid
```

## Implemented modules

### Foundation

- Laravel 13
- MySQL/MariaDB
- Laravel Sanctum
- Role middleware: admin, driver, customer
- Main branch workflow: direct to `main`

### Customer and booking

- Customer registration and profile
- Tour package public API and admin CRUD
- Booking creation/list/detail
- Booking participants
- Ownership isolation
- Strict booking state machine
- Travel groups
- Participant-to-vehicle allocation
- Capacity validation and cross-booking isolation

### Payment

- Customer payment proof submission
- Admin approval/rejection
- Resubmission after rejection
- Duplicate pending payment protection
- Booking payment-status synchronization

### Driver

- Driver multipart registration
- Driver profile and availability
- Driver verification
- Driver document verification/reupload
- Driver-owned vehicle CRUD
- Vehicle document upload/replacement
- Vehicle photo upload, reorder, and delete
- Vehicle re-verification when sensitive data/media changes
- Ownership isolation
- Active-assignment deletion guard

### Assignment

- Admin offers assignments
- Driver accepts/rejects
- Rejection reason validation
- Same-date driver conflict protection
- Same-date vehicle conflict protection
- Different-date assignment allowed

### Points and withdrawal

- Configurable trip reward
- Point ledger
- Available and held balances
- Withdrawal request/list
- Admin approve/reject/mark paid
- HOLD, RELEASE, and DEBIT ledger behavior
- `WithdrawalService` with database transaction and `lockForUpdate()`
- Dedicated MySQL concurrent-withdrawal integration test setup

Default configuration:

- 100 points per completed trip
- 1 point = Rp1,000
- minimum withdrawal = 100 points

### Audit logs

- `audit_logs` migration and model
- Centralized `AuditObserver`
- Automatic create/update/delete audit events
- Actor, subject, old/new values, IP, user-agent, URL, method
- Sensitive fields and stored file paths excluded
- Admin read-only list/detail endpoints

### Notifications and queue

- Database notifications migration
- Queued `OperationalNotification`
- Dispatch after transaction commit
- Notifications for payment, booking, assignment, verification, and withdrawal changes
- Shared authenticated notification inbox
- Mark one/read all
- Cross-user ownership isolation

## Critical tests added

- `tests/Feature/DriverWithdrawalFlowTest.php`
- `tests/Feature/BookingStateAndRewardFlowTest.php`
- `tests/Feature/PaymentFlowTest.php`
- `tests/Feature/DriverAssignmentResponseFlowTest.php`
- `tests/Feature/ParticipantAllocationFlowTest.php`
- `tests/Feature/DriverVehicleCrudFlowTest.php`
- `tests/Feature/VehicleMediaFlowTest.php`
- `tests/Feature/AuditLogFlowTest.php`
- `tests/Feature/NotificationFlowTest.php`
- `tests/Integration/MySql/ConcurrentWithdrawalTest.php`

MySQL concurrency support files:

- `app/Services/WithdrawalService.php`
- internal `withdrawal:attempt` Artisan command
- `phpunit.mysql.xml`

## Important migrations added

- `2026_07_20_000001_create_audit_logs_table.php`
- `2026_07_20_000002_create_notifications_table.php`

## Current verification status

Runtime tests were not executed by the AI environment because the GitHub connector has no local PHP/MySQL runtime.

Known user-run result before later changes:

- `PaymentFlowTest`: 4 passed, 1 failed due to a fixture mismatch
- Fixture was fixed afterward, but the updated test result has not yet been confirmed

Therefore, do not claim the full suite passes until the user runs it locally.

## First actions for the next session

Run:

```powershell
cd C:\Projects\offroad-booking-api
git switch main
git pull origin main
php artisan optimize:clear
php artisan migrate
php artisan test
```

Then run MySQL concurrency test separately against the dedicated test database:

```powershell
php artisan test tests/Integration/MySql/ConcurrentWithdrawalTest.php --configuration=phpunit.mysql.xml --stop-on-failure
```

Dedicated database expected by default:

```text
offroad_booking_test
host 127.0.0.1
port 3306
user root
password empty
```

Never point `phpunit.mysql.xml` to development or production because the test performs `migrate:fresh`.

## Next recommended work

1. Run and fix the full test suite.
2. Run and fix the MySQL concurrent-withdrawal integration test.
3. Add rate limiting for login, registration, uploads, withdrawal, and sensitive admin endpoints.
4. Add OpenAPI documentation.
5. Configure production queue worker/supervision.
6. Add reporting/dashboard metrics.
7. Prepare backup, deployment, monitoring, and client integration.

## Response format rule

After every backend change, answer in this exact order:

1. **Changes**
2. **Endpoint changes**
3. **Cara pull changes**
4. **cURL Postman**
5. **Expected result cURL**

Use Indonesian, PowerShell-ready commands, complete cURL flow where relevant, migration/test status, and latest commit SHA.

## Latest checkpoint commit before this file

`f96853e69e4ee707cd51e58940e2a1e6a17794b8`
