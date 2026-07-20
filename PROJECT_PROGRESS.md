# Offroad Booking API — Project Progress Checkpoint

Last updated: 2026-07-20 (Asia/Jakarta)
Branch: `main`
Repository: `allifiz/offroad-booking-api`
Local path: `C:\Projects\offroad-booking-api`

## Current backend status

Estimated progress:

- Core functional MVP: approximately 96–97%
- Production readiness: approximately 90–92%

The complete booking, payment, assignment, allocation, completion reward, withdrawal, audit, notification, and rate-limit flows are implemented.

## Production queue hardening

Implemented:

- Existing Laravel database queue migrations for `jobs`, `job_batches`, and `failed_jobs` confirmed.
- Operational notifications run on the `notifications` queue after transaction commit.
- Retry policy: 5 tries.
- Timeout: 30 seconds with fail-on-timeout.
- Backoff: 10, 60, 300, and 900 seconds.
- `.env.example` includes queue connection, retry-after, failed-job driver, and health thresholds.
- Queue health config: `config/queue_health.php`.
- Queue health command:
  - `php artisan queue:health`
  - `php artisan queue:health --json`
- Supervisor template: `deploy/supervisor/offroad-booking-worker.conf`.
- Production operations guide: `docs/QUEUE_PRODUCTION.md`.
- Queue policy and health tests: `tests/Feature/QueueHealthFlowTest.php`.

Recommended production worker:

```bash
php artisan queue:work database --queue=notifications,default --sleep=3 --tries=5 --timeout=30 --max-time=3600
```

`DB_QUEUE_RETRY_AFTER` defaults to 120 seconds and must remain greater than the 30-second worker timeout.

## Autonomous CI

Workflow: `.github/workflows/backend-tests.yml`.

Previously confirmed green:

- OpenAPI lint
- SQLite feature suite
- MySQL concurrent-withdrawal suite

The queue-hardening commits trigger a new CI run. Do not claim the new queue tests pass until GitHub reports the result.

## API documentation

Canonical contract: `docs/openapi.yaml`.

Current coverage includes public, authentication, notifications, customer flow, driver flow, and core admin operations.

## Next recommended work

1. Inspect and fix any queue-hardening CI failure.
2. Add admin reporting/dashboard metrics.
3. Expand exact OpenAPI schemas and remaining admin endpoints.
4. Prepare deployment, backup, monitoring, and frontend/Flutter integration.

## Response format rule

After backend changes respond in this exact order:

1. **Changes**
2. **Endpoint changes**
3. **Cara pull changes**
4. **cURL Postman**
5. **Expected result cURL**
