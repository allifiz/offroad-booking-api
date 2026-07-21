# AGENTS.md

## Project identity

- Project: Offroad Booking Platform
- Repository: `allifiz/offroad-booking-api`
- Backend/API and Admin Web: Laravel 13
- Database: MySQL/MariaDB
- API authentication: Laravel Sanctum
- Admin authentication: Laravel session
- Mobile clients: Flutter native for customer and driver
- Main branch: `main`
- Local path: `C:\Projects\offroad-booking-api`

## Project phase

**Backend MVP is complete.**

The Laravel REST API, operational Admin Web, automated tests, OpenAPI contract, reports, audit logs, health checks, deployment assets, and core production operations have been implemented. GitHub Actions has been confirmed green for the completed backend codebase.

The documented Postman end-to-end happy path has also been executed manually against the current backend and a real local MySQL/MariaDB database. All documented requests completed successfully and the persisted records remained connected through customer, booking, payment, driver, assignment, completion reward, and withdrawal flows. See `docs/POSTMAN_E2E_VERIFICATION.md`.

The next product-development phase is Flutter application development and staging/production infrastructure integration. Do not reopen completed backend scope unless a mobile integration issue, verified defect, security requirement, or approved product change requires it.

## Mandatory workflow

1. Inspect models, migrations, controllers, routes, tests, shared services, queue behavior, frontend assets, deployment scripts, and `docs/openapi.yaml` before changing backend behavior.
2. Apply changes directly to `main`, unless the user explicitly requests another branch.
3. Never expose real secrets or claim tests pass unless CI/runtime confirms them.
4. Update this file, `README.md`, and `PROJECT_PROGRESS.md` when project status or architecture materially changes.
5. Keep `docs/openapi.yaml` synchronized with every API endpoint, payload, enum, authentication, pagination, and error-contract change.
6. Preserve backward compatibility for Flutter clients unless a versioned breaking change is explicitly approved.
7. Read `docs/POINT_REWARD_DECISION_PENDING.md` before changing assignment, completion reward, point ledger, conversion, or withdrawal behavior.
8. After backend changes, respond in this order: Changes, Endpoint changes, Cara pull changes, cURL Postman, Expected result cURL.

## Completed backend scope

- Customer authentication, profile, package discovery, bookings, participants, payments, travel groups, and notifications.
- Driver onboarding, document verification, vehicles, assignment offers, participant allocation, trip lifecycle, rewards, point ledger, and withdrawals.
- Admin dashboard and full operational management for customers, packages, travel groups, bookings, payments, drivers, vehicles, withdrawals, reports, and audit logs.
- Shared responsive Admin Web layout with desktop sidebar, mobile navigation, active route state, global flash messages, and validation errors.
- Risk-based rate limiting, queued notifications, concurrency-safe booking and withdrawal operations, idempotent rewards, audit trails, CSV export, health checks, backup/deploy scripts, and automated CI.
- GitHub Actions jobs for OpenAPI lint, SQLite feature tests, and MySQL concurrency tests.

## Manual acceptance verification

- Test definition: `docs/POSTMAN_END_TO_END_TEST.md`.
- Verification record: `docs/POSTMAN_E2E_VERIFICATION.md`.
- Result: complete documented happy path passed manually against the current backend and a real local database.
- Verified continuity: customer -> booking -> payment -> driver assignment -> completed trip -> point ledger -> withdrawal.
- This manual result supplements, but does not replace, automated CI, concurrency tests, negative tests, staging tests, or production tests.
- Point mechanics technically passed according to the current implementation, but the product policy remains pending and must not be described as final.

## Point policy status

- Current values are temporary MVP placeholders, not an approved final business policy.
- Current defaults include 100 points per completed trip, Rp1,000 per point, and a 100-point minimum withdrawal.
- Reward is currently credited only when a booking becomes `completed` and is not exposed as an estimated reward before assignment acceptance.
- Preserve current behavior until an explicit product decision is approved.
- Canonical pending-decision note: `docs/POINT_REWARD_DECISION_PENDING.md`.

## Shared domain services

- `BookingLifecycleService` is canonical for booking transitions, cancellation propagation, row locking, completion rewards, and ledger idempotency.
- `WithdrawalService` is canonical for withdrawal requests and admin transitions.
- API and Admin Web must not duplicate booking lifecycle, withdrawal balance, reward, or ledger logic.
- New Flutter requirements must consume existing services through API controllers rather than introducing mobile-specific domain logic.

## Admin Web rules

- All authenticated admin pages use `resources/views/layouts/admin.blade.php` and the shared navigation partial.
- Guest redirects must resolve to `admin.login` through `redirectGuestsTo()`.
- Root `/` intentionally redirects to `/admin`.
- Admin Blade views use Vite assets; feature-test environments must build `public/build/manifest.json` before rendering them.
- Session-protected CSV downloads reuse the canonical report export implementation.
- Preserve role authorization: guests redirect to login and authenticated non-admin users receive HTTP 403.

## Flutter integration rules

- Treat `docs/openapi.yaml` as the canonical mobile API contract.
- Use staging API environments for Flutter development; never point normal development builds at production data.
- Store Sanctum tokens using secure device storage.
- Handle revoked tokens, suspended/inactive users, validation errors, pagination, enum values, upload limits, offline failures, retries, and idempotent actions explicitly.
- Prefer additive backend changes. Breaking response changes require API versioning or an agreed migration plan.
- Keep customer and driver roles separated at navigation and authorization layers while sharing reusable networking, auth, error, and storage infrastructure.
- Do not hardcode the current reward values in Flutter because the point policy is still pending.

## CI requirements

- SQLite feature job uses PHP 8.4 and Node.js 22.
- Run `npm install --ignore-scripts`, then `npm run build`, before `php artisan test` when Blade views are rendered.
- The repository currently has no `package-lock.json`; do not use `npm ci` until a lockfile is committed.
- MySQL concurrency tests do not render Blade and do not require a frontend build.
- Do not merge backend behavior changes unless relevant tests and the full GitHub Actions workflow are green.

## Production operations

- Application health: `php artisan app:health` and `php artisan app:health --json`.
- Queue health: `php artisan queue:health` and `php artisan queue:health --json`.
- Supervisor config: `deploy/supervisor/offroad-booking-worker.conf`.
- Deploy script: `deploy/scripts/deploy.sh`.
- Backup script: `deploy/scripts/backup.sh`.
- Runbooks: `docs/QUEUE_PRODUCTION.md` and `docs/PRODUCTION_DEPLOYMENT.md`.

## Completion status

- Backend MVP: **completed**.
- REST API contract: **completed for MVP**.
- Admin Web functional scope: **completed**.
- Shared Admin Web layout: **completed**.
- Automated backend CI: **green**.
- Manual Postman E2E happy path: **passed**.
- Database relationship continuity: **verified**.
- Point implementation: **technically passed**.
- Point product policy: **pending decision**.
- Next active phase: **Flutter customer and driver applications**.
- Separate operational track: staging, production hardening, monitoring, backup verification, and deployment.
