# Offroad Booking API — Project Progress Checkpoint

Last updated: 2026-07-20 (Asia/Jakarta)
Branch: `main`
Repository: `allifiz/offroad-booking-api`
Local path: `C:\Projects\offroad-booking-api`

## Current backend status

Estimated progress:

- Core functional MVP: approximately 94–95%
- Production readiness: approximately 82–85%

The core end-to-end flow is implemented:

```text
customer creates booking
→ uploads payment proof
→ admin approves/rejects payment
→ booking is confirmed
→ admin offers driver assignment
→ driver accepts/rejects
→ admin allocates participants within vehicle capacity
→ booking starts and completes
→ accepted drivers receive points once
→ driver requests withdrawal
→ admin approves/rejects/marks paid
```

## Implemented modules

### Foundation and actors

- Laravel 13 + MySQL/MariaDB
- Laravel Sanctum
- Role middleware: admin, driver, customer
- Customer registration/profile
- Driver multipart registration/profile/availability
- Driver, vehicle, and document verification

### Booking operations

- Public/admin tour packages
- Customer booking/list/detail/participants
- Payment proof submission and admin verification
- Strict booking state machine
- Assignment offer and driver accept/reject
- Same-date driver/vehicle conflict protection
- Travel groups
- Participant-to-vehicle allocation
- Capacity and cross-booking isolation

### Driver vehicle management

- Driver-owned vehicle create/update/delete
- Ownership isolation
- Sensitive-change re-verification
- Active-assignment deletion guard
- Vehicle document upload/replacement
- Vehicle photo upload/reorder/delete
- Storage cleanup

### Points and withdrawal

- Configurable completion reward
- Point ledger
- Available and held balance
- Withdrawal HOLD/RELEASE/DEBIT flow
- Admin approve/reject/paid transitions
- `WithdrawalService` with transaction, retry, and `lockForUpdate()`
- Dedicated two-process MySQL concurrent-withdrawal integration test

### Audit logs

- Centralized model observer
- Actor, subject, old/new values, request context
- Sensitive fields and stored paths excluded
- Admin read-only list/detail endpoints

### Notifications and queues

- Database notifications
- Queued `OperationalNotification`
- Dispatch after transaction commit
- Payment, booking, assignment, verification, and withdrawal events
- Shared authenticated inbox
- Unread filter, mark one read, mark all read

### API rate limiting

Configured named limiters:

- `auth-login`: 5 requests/minute per normalized email + IP
- `public-registration`: 3 requests/hour per IP, shared by driver/customer registration
- `authenticated-read`: 120 requests/minute per user/IP
- `customer-write`: 20 requests/minute per user/IP
- `driver-write`: 30 requests/minute per user/IP
- `file-upload`: 10 requests/minute per user/IP
- `withdrawal-request`: 3 requests/hour per user/IP
- `admin-write`: 60 requests/minute per user/IP

Rate middleware is applied based on route risk. Login brute force and shared registration-IP limits are covered by `RateLimitFlowTest`.

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
- `tests/Feature/RateLimitFlowTest.php`
- `tests/Integration/MySql/ConcurrentWithdrawalTest.php`

## Important migrations

- `2026_07_20_000001_create_audit_logs_table.php`
- `2026_07_20_000002_create_notifications_table.php`

Rate limiting requires no migration.

## Verification status

The AI environment cannot execute PHP/MySQL runtime tests. Do not claim the full suite passes until run locally.

Run standard suite:

```powershell
cd C:\Projects\offroad-booking-api
git switch main
git pull origin main
php artisan optimize:clear
php artisan migrate
php artisan test --filter=RateLimitFlowTest
php artisan test
```

Run MySQL concurrency separately:

```powershell
php artisan test tests/Integration/MySql/ConcurrentWithdrawalTest.php --configuration=phpunit.mysql.xml --stop-on-failure
```

Dedicated MySQL database default:

```text
offroad_booking_test
127.0.0.1:3306
root
empty password
```

Never point `phpunit.mysql.xml` at development or production because it runs `migrate:fresh`.

## Next recommended work

1. Run and fix the standard full test suite.
2. Run and fix the dedicated MySQL concurrency suite.
3. Add OpenAPI documentation.
4. Configure production queue worker and supervision.
5. Add reports/dashboard metrics.
6. Prepare backup, deployment, monitoring, and frontend/Flutter integration.

## Response format rule

After every backend change, answer in this exact order:

1. **Changes**
2. **Endpoint changes**
3. **Cara pull changes**
4. **cURL Postman**
5. **Expected result cURL**

Use Indonesian, PowerShell-ready commands, complete cURL flow where relevant, migration/test status, and latest commit SHA.

## Latest rate-limit commits

- `615d775963c0a6ab5c372221af0794c17b4b9747` — configure named API rate limiters
- `8b2b92e8cef1f2a5c16ea640ae103eaf0794cb45` — apply risk-based route middleware
- `e20b6c716142e4420a43be4453ebbee3e3fbe7f7` — add rate-limit feature tests
- `f0aa9b83687a6f41948e21b979deaab2428c46a1` — update AGENTS.md progress
