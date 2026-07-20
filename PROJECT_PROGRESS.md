# Offroad Booking — Project Progress Checkpoint

Last updated: 2026-07-20 (Asia/Jakarta)
Branch: `main`
Repository: `allifiz/offroad-booking-api`
Local path: `C:\Projects\offroad-booking-api`

## Current status

- Backend core MVP: approximately 99%.
- Backend production readiness: approximately 96–97%.
- Laravel admin web: authentication, dashboard, payment verification, booking operations, shared lifecycle completion, and participant allocation implemented.

## Shared booking lifecycle

Canonical service:

```text
app/Services/BookingLifecycleService.php
```

Responsibilities:

- strict booking transition validation
- paid-booking requirements
- accepted-assignment requirements
- row locking and transaction retry
- cancellation propagation to active assignments
- completion reward distribution
- idempotent point-ledger creation

Both API and Admin Web now use the same service for status transitions.

## Admin booking operations

Routes:

```text
GET   /admin/bookings
GET   /admin/bookings/{booking}
PATCH /admin/bookings/{booking}/status
POST  /admin/bookings/{booking}/assignments
PATCH /admin/bookings/{booking}/assignments/{assignment}/cancel
PUT   /admin/bookings/{booking}/participant-allocations
```

Implemented:

- booking list/detail and filters
- safe status transitions through the shared service
- booking completion from Admin Web with driver rewards
- assignment offer and cancellation
- participant allocation to accepted assignments
- capacity enforcement
- moving a participant between accepted assignments without duplicate allocation
- final bookings cannot be allocated

Test:

```text
tests/Feature/AdminWebBookingLifecycleFlowTest.php
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

CI was confirmed green before the shared lifecycle and allocation changes. Do not claim the newest lifecycle/allocation test passes until the workflow result is confirmed.

## API documentation

Canonical contract: `docs/openapi.yaml`.

Web-only routes are not part of OpenAPI. Canonical OpenAPI still needs dashboard, CSV response, exact schemas, and remaining admin API coverage.

## Next recommended work

1. Inspect and fix any lifecycle/allocation CI failure.
2. Implement driver and vehicle verification pages.
3. Implement withdrawal, reports, and audit pages.
4. Complete canonical OpenAPI coverage.
5. Start customer web and Flutter driver integration.

## Response format rule

After backend changes respond in this exact order:

1. **Changes**
2. **Endpoint changes**
3. **Cara pull changes**
4. **cURL Postman**
5. **Expected result cURL**
