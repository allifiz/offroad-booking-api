# Offroad Booking — Project Progress Checkpoint

Last updated: 2026-07-20 (Asia/Jakarta)
Branch: `main`
Repository: `allifiz/offroad-booking-api`
Local path: `C:\Projects\offroad-booking-api`

## Current status

- Backend core MVP: approximately 99%.
- Backend production readiness: approximately 97%.
- Laravel admin web: authentication, dashboard, payment verification, booking operations, lifecycle completion, participant allocation, driver/vehicle verification, and withdrawal processing implemented.

## Shared lifecycle services

Canonical services:

```text
app/Services/BookingLifecycleService.php
app/Services/WithdrawalService.php
```

`WithdrawalService` now handles:

- driver withdrawal requests
- pending to approved/rejected transitions
- approved to paid transition
- row locking on withdrawal and driver profile
- transaction retry up to three times
- held-point release after rejection
- held-point debit after payment
- HOLD, RELEASE, and DEBIT point-ledger entries

Both API and Admin Web use the same withdrawal transition implementation.

## Admin withdrawal operations

Routes:

```text
GET   /admin/withdrawals
GET   /admin/withdrawals/{withdrawal}
PATCH /admin/withdrawals/{withdrawal}
```

Implemented:

- withdrawal queue with status filter
- search by driver name or email
- payout detail with bank and account data
- driver available and held balance display
- approve pending withdrawal
- reject pending withdrawal with mandatory reason
- mark approved withdrawal as paid
- processor and processed timestamp recording
- safe balance mutation through `WithdrawalService`

Files:

```text
app/Http/Controllers/Web/Admin/WithdrawalController.php
resources/views/admin/withdrawals/index.blade.php
resources/views/admin/withdrawals/show.blade.php
tests/Feature/AdminWebWithdrawalFlowTest.php
```

## Existing production operations

```bash
php artisan app:health
php artisan app:health --json
php artisan queue:health
php artisan queue:health --json
```

Deployment and backup:

```text
deploy/scripts/deploy.sh
deploy/scripts/backup.sh
deploy/supervisor/offroad-booking-worker.conf
docs/PRODUCTION_DEPLOYMENT.md
```

## Autonomous CI

Workflow: `.github/workflows/backend-tests.yml`.

CI was confirmed green before the admin withdrawal and WithdrawalService refactor. Do not claim the new web withdrawal test passes until the latest workflow result is confirmed.

## API documentation

Canonical contract: `docs/openapi.yaml`.

Web-only routes are not part of OpenAPI. Canonical OpenAPI still needs dashboard, CSV response, exact schemas, and remaining admin API coverage.

## Next recommended work

1. Inspect and fix any withdrawal CI failure.
2. Implement reports and audit pages.
3. Complete canonical OpenAPI coverage.
4. Start customer web and Flutter driver integration.

## Response format rule

After backend changes respond in this exact order:

1. **Changes**
2. **Endpoint changes**
3. **Cara pull changes**
4. **cURL Postman**
5. **Expected result cURL**
