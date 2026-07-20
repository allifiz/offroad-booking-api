# Offroad Booking API — Project Progress Checkpoint

Last updated: 2026-07-20 (Asia/Jakarta)
Branch: `main`
Repository: `allifiz/offroad-booking-api`
Local path: `C:\Projects\offroad-booking-api`

## Current backend status

Estimated progress:

- Core functional MVP: approximately 97–98%
- Production readiness: approximately 92–94%

The complete booking, payment, assignment, allocation, completion reward, withdrawal, audit, notification, rate-limit, queue-hardening, and initial admin reporting flows are implemented.

## Production queue hardening

Implemented:

- Existing Laravel database queue migrations for `jobs`, `job_batches`, and `failed_jobs` confirmed.
- Operational notifications run on the `notifications` queue after transaction commit.
- Retry policy: 5 tries.
- Timeout: 30 seconds with fail-on-timeout.
- Backoff: 10, 60, 300, and 900 seconds.
- Queue health config and command.
- Supervisor template and production operations guide.
- Queue policy and health tests.

Recommended worker:

```bash
php artisan queue:work database --queue=notifications,default --sleep=3 --tries=5 --timeout=30 --max-time=3600
```

## Admin dashboard metrics

Endpoint:

```text
GET /api/v1/admin/dashboard
```

Query parameters:

- `date_from` optional
- `date_to` optional
- default latest 30 days
- maximum range 366 days

Metrics:

- bookings by status
- participant count
- gross booking value excluding cancelled bookings
- payments by `unpaid`, `pending`, `paid`, `refunded`, and `failed`
- paid revenue, pending amount, and refunded amount
- driver total, verification, availability, available points, and held points
- vehicle total, verification, and availability
- withdrawals by status, requested points, paid amount, and pending amount
- zero-filled daily trend for bookings, booking value, and paid revenue

Authorization:

- admin only
- customer/driver receive `403`

Files:

- `app/Http/Controllers/Api/V1/Admin/DashboardController.php`
- `tests/Feature/AdminDashboardFlowTest.php`
- `docs/ADMIN_DASHBOARD.md`

## Autonomous CI

Workflow: `.github/workflows/backend-tests.yml`.

Confirmed green before the latest dashboard changes:

- OpenAPI lint
- SQLite feature suite
- MySQL concurrent-withdrawal suite

The latest dashboard and queue commits trigger a new CI run. Do not claim their tests pass until GitHub reports the result.

## API documentation

Canonical contract: `docs/openapi.yaml`.

Operational dashboard documentation: `docs/ADMIN_DASHBOARD.md`.

## Next recommended work

1. Inspect and fix any dashboard/queue CI failure.
2. Add downloadable CSV reports for bookings, payments, drivers, and withdrawals.
3. Expand exact OpenAPI schemas and remaining admin endpoints.
4. Prepare deployment, backup, monitoring, and frontend/Flutter integration.

## Response format rule

After backend changes respond in this exact order:

1. **Changes**
2. **Endpoint changes**
3. **Cara pull changes**
4. **cURL Postman**
5. **Expected result cURL**
