# Offroad Booking — Project Progress Checkpoint

Last updated: 2026-07-20 (Asia/Jakarta)
Branch: `main`
Repository: `allifiz/offroad-booking-api`
Local path: `C:\Projects\offroad-booking-api`

## Current status

- Backend core MVP: approximately 99%.
- Backend production readiness: approximately 96–97%.
- Laravel admin web: authentication, dashboard, payment verification, booking operations, lifecycle completion, participant allocation, and driver/vehicle verification implemented.

## Shared booking lifecycle

Canonical service: `app/Services/BookingLifecycleService.php`.

Responsibilities:

- strict booking transition validation
- paid-booking and accepted-assignment requirements
- row locking and transaction retry
- cancellation propagation
- completion reward distribution
- idempotent point-ledger creation

Both API and Admin Web use the same service for status transitions.

## Admin driver and vehicle verification

Routes:

```text
GET   /admin/drivers
GET   /admin/drivers/{driverProfile}
PATCH /admin/drivers/{driverProfile}
PATCH /admin/drivers/{driverProfile}/vehicles/{vehicle}
```

Implemented:

- driver verification queue with status filters
- search by driver name, email, license number, or identity number
- driver profile and document review
- vehicle list and document metadata on driver detail
- driver approve/reject with mandatory rejection reason
- vehicle approve/reject with mandatory rejection reason
- approval activates driver/vehicle as `available`
- rejection forces driver/vehicle to `unavailable`
- verifier and verification timestamp are recorded
- nested vehicle ownership is checked before mutation

Files:

```text
app/Http/Controllers/Web/Admin/DriverVerificationController.php
resources/views/admin/drivers/index.blade.php
resources/views/admin/drivers/show.blade.php
tests/Feature/AdminWebDriverVerificationFlowTest.php
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

CI was confirmed green before the driver/vehicle verification web changes. Do not claim the new verification test passes until the latest workflow result is confirmed.

## API documentation

Canonical contract: `docs/openapi.yaml`.

Web-only routes are not part of OpenAPI. Canonical OpenAPI still needs dashboard, CSV response, exact schemas, and remaining admin API coverage.

## Next recommended work

1. Inspect and fix any driver/vehicle verification CI failure.
2. Implement withdrawal operations pages.
3. Implement reports and audit pages.
4. Complete canonical OpenAPI coverage.
5. Start customer web and Flutter driver integration.

## Response format rule

After backend changes respond in this exact order:

1. **Changes**
2. **Endpoint changes**
3. **Cara pull changes**
4. **cURL Postman**
5. **Expected result cURL**
