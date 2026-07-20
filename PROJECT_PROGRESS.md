# Offroad Booking API — Project Progress Checkpoint

Last updated: 2026-07-20 (Asia/Jakarta)
Branch: `main`
Repository: `allifiz/offroad-booking-api`
Local path: `C:\Projects\offroad-booking-api`

## Current backend status

Estimated progress:

- Core functional MVP: approximately 95–96%
- Production readiness: approximately 86–88%

The end-to-end core flow is implemented:

```text
customer creates booking
→ uploads payment proof
→ admin approves/rejects payment
→ booking is confirmed
→ admin offers driver assignment
→ driver accepts/rejects
→ admin allocates participants within capacity
→ booking starts and completes
→ accepted drivers receive points once
→ driver requests withdrawal
→ admin approves/rejects/marks paid
```

## Implemented modules

- Laravel 13, MySQL/MariaDB, Sanctum, and role middleware.
- Customer registration/profile, bookings, participants, payments, and ownership isolation.
- Driver multipart registration/profile/availability, verification, documents, and vehicle CRUD/media.
- Strict booking state machine, assignment offer/response, conflict protection, travel groups, and participant allocation.
- Point ledger, completion rewards, available/held balances, withdrawal HOLD/RELEASE/DEBIT flow.
- `WithdrawalService` with transaction, retry, and MySQL `lockForUpdate()`.
- Audit logs for sensitive model mutations.
- Queued database notifications after transaction commit.
- Shared notification inbox with read actions and ownership isolation.
- Risk-based rate limiting for login, registration, reads, writes, uploads, withdrawals, and admin mutations.

## OpenAPI documentation

Canonical contract:

```text
docs/openapi.yaml
```

Current coverage:

- Public health and tour-package endpoints.
- Sanctum login/profile/logout.
- Customer and driver registration.
- Notification inbox.
- Customer profile, booking, and payment flow.
- Driver profile, availability, vehicle/media, assignments, points, and withdrawals.
- Core admin audit, payment verification, booking status, assignment, participant allocation, and withdrawal processing.
- Reusable Bearer auth, success/error responses, validation errors, ownership-style 404, conflicts, and `429` rate-limit headers.

Usage guide:

```text
docs/README.md
```

It documents Redoc preview, Swagger UI through Docker, Redocly lint, and Postman import.

## Autonomous CI

Workflow:

```text
.github/workflows/backend-tests.yml
```

Jobs:

1. `OpenAPI lint` using `@redocly/cli`.
2. `SQLite feature suite` using PHP 8.3.
3. `MySQL concurrency suite` using MySQL 8.4.

Runs on push to `main`, pull requests targeting `main`, and manual dispatch.

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

## Important migrations

- `2026_07_20_000001_create_audit_logs_table.php`
- `2026_07_20_000002_create_notifications_table.php`

OpenAPI, rate limiting, and CI require no new database migration.

## Verification status

GitHub Actions is now the primary autonomous validator. Do not claim jobs pass until a workflow result is available through GitHub.

Local fallback:

```powershell
cd C:\Projects\offroad-booking-api
git switch main
git pull origin main
php artisan optimize:clear
php artisan migrate
php artisan test
php artisan test --configuration=phpunit.mysql.xml
npx --yes @redocly/cli@latest lint docs/openapi.yaml
```

## Latest OpenAPI and CI commits

- `799d9be90d821965d4d8b764531a1f4ded47a857` — add OpenAPI 3.1 specification.
- `536a6e05b620824aa14db5e0d89ea13521ef7359` — add OpenAPI lint job to CI.
- `f96ac069d5815b7f305a62d046574268c2094306` — add documentation usage guide.
- `065a75f16f1c8153348b5c31b41d34bb4cf43128` — update autonomous project rules.
- `a03fdc0c4c79c3eba83da676a152691d9c109c8a` — add autonomous backend test workflow.

## Next recommended work

1. Inspect and fix CI failures once results are exposed.
2. Expand exact OpenAPI response schemas and all remaining admin CRUD/list endpoints.
3. Configure production queue worker and process supervision.
4. Add reporting/dashboard metrics.
5. Prepare backup, deployment, monitoring, and frontend/Flutter integration.

## Response format rule

After every backend change, answer in this exact order:

1. **Changes**
2. **Endpoint changes**
3. **Cara pull changes**
4. **cURL Postman**
5. **Expected result cURL**
