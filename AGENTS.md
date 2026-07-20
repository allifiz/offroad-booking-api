# AGENTS.md

## Project identity

- Project: Offroad Booking Web App
- Repository: `allifiz/offroad-booking-api`
- Backend/API and web clients: Laravel 13
- Database: MySQL/MariaDB
- API authentication: Laravel Sanctum
- Admin web authentication: Laravel session
- Driver client: Flutter native
- Main branch: `main`
- Local path: `C:\Projects\offroad-booking-api`

## Mandatory workflow

1. Inspect models, migrations, controllers, routes, tests, queue behavior, deployment scripts, frontend assets, and API documentation before changing behavior.
2. Apply changes directly to `main`, unless the user requests another branch.
3. Never expose real secrets or claim tests pass unless CI/runtime confirms them.
4. Update this file and `PROJECT_PROGRESS.md` after project changes.
5. Keep `docs/openapi.yaml` synchronized with API endpoint and payload changes.
6. After backend changes respond in this order: Changes, Endpoint changes, Cara pull changes, cURL Postman, Expected result cURL.

## Implemented system

- Complete customer, booking, payment, driver, vehicle, assignment, allocation, reward, withdrawal, audit, and notification API flows.
- Risk-based rate limiting, queued notifications, MySQL concurrency protection, reporting, CSV export, health checks, backup/deploy scripts, and autonomous CI.
- GitHub Actions jobs: OpenAPI lint, SQLite suite, and MySQL concurrency suite.

## Shared services

- `BookingLifecycleService` is canonical for booking transitions, cancellation propagation, row locking, completion rewards, and ledger idempotency.
- `WithdrawalService` is canonical for withdrawal requests and admin transitions.
- API and Admin Web must not duplicate booking or withdrawal balance logic.

## Admin web

- Session authentication, dashboard, payment verification, booking operations, participant allocation, driver/vehicle verification, withdrawal operations, reports, and audit logs are implemented.
- Session-protected CSV downloads reuse `Api\V1\Admin\ReportExportController`.
- Guest redirects are configured explicitly through `redirectGuestsTo()` and must resolve to `admin.login`.
- Root `/` intentionally redirects to `/admin`; tests must assert the redirect instead of expecting a 200 response.
- Booking list rendering parses `tour_date` defensively and casts decimal totals before formatting.
- Admin Blade views use Vite assets and therefore CI must build `public/build/manifest.json` before rendering feature tests.

## CI requirements

- SQLite feature job uses PHP 8.4 and Node.js 22.
- Run `npm install --ignore-scripts` followed by `npm run build` before `php artisan test`.
- The repository currently has no `package-lock.json`, so CI must not use `npm ci` until a lockfile is committed.
- MySQL concurrency tests do not render Blade and do not require a frontend build.

## Production operations

- Queue health: `php artisan queue:health` and `--json`.
- Application health: `php artisan app:health` and `--json`.
- Supervisor: `deploy/supervisor/offroad-booking-worker.conf`.
- Deploy script: `deploy/scripts/deploy.sh`.
- Backup script: `deploy/scripts/backup.sh`.
- Runbooks: `docs/QUEUE_PRODUCTION.md`, `docs/PRODUCTION_DEPLOYMENT.md`.

## Verification status

- The latest SQLite run passed most suites but exposed three web-test regressions: unauthenticated redirect configuration, outdated root-route expectation, and booking list rendering.
- Fix commits: `f09bc7ce24a784569792e298c51e9917c70b6a58`, `388d7016e8e087fcf728e80a19c4c04d66c9786e`, and `0780d82c251d88bcd568306c77062692103d22c3`.
- Do not claim the newest workflow passes until GitHub Actions confirms it.

## Next progress list

1. Confirm the rebuilt SQLite suite is green and fix any remaining test failure.
2. Finish canonical OpenAPI dashboard/CSV/admin schemas.
3. Start customer web.
4. Start Flutter driver integration.
