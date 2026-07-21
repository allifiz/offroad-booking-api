# Offroad Booking — Project Progress Checkpoint

Last updated: 2026-07-21 (Asia/Jakarta)  
Branch: `main`  
Repository: `allifiz/offroad-booking-api`  
Local path: `C:\Projects\offroad-booking-api`

## Final backend status

**BACKEND MVP: COMPLETED ✅**

| Area | Status |
|---|---|
| Backend core MVP | 100% complete |
| REST API customer | Complete |
| REST API driver | Complete |
| Admin Web functional scope | Complete |
| Shared Admin Web layout | Complete |
| Reports and audit logs | Complete |
| Automated feature tests | Complete |
| MySQL concurrency coverage | Complete |
| OpenAPI lint | Green |
| GitHub Actions backend workflow | Green |
| Flutter integration readiness | Ready to start |
| Driver point reward policy | Pending product decision; MVP placeholder active |

The backend feature-development phase is closed. Further backend work should be limited to verified defects, Flutter integration requirements, security hardening, infrastructure integration, or explicitly approved product changes.

## Completed customer scope

- Registration, login, logout, token authentication, and profile management.
- Tour package discovery and detail.
- Booking creation, participant data, status lifecycle, and cancellation behavior.
- Payment submission and proof upload.
- Booking and payment history.
- Travel group support.
- Notifications and account-status enforcement.

## Completed driver scope

- Driver onboarding and profile management.
- Document submission and admin verification.
- Driver-owned and company vehicle support.
- Assignment offers and assignment lifecycle.
- Participant-to-vehicle allocation.
- Trip completion and idempotent driver rewards.
- Point ledger, balance protection, and withdrawal lifecycle.
- Concurrency-safe balance and booking operations.

## Pending product decision: driver point rewards

Canonical decision note:

```text
docs/POINT_REWARD_DECISION_PENDING.md
```

The current point implementation is operational for MVP testing but is **not the final incentive policy**.

Current temporary defaults:

```text
Completed trip reward   = 100 points
Rupiah per point        = Rp1.000
Minimum withdrawal      = 100 points
```

Current behavior:

- reward is a fixed amount for every completed trip;
- reward is issued only when a booking transitions to `completed`;
- assignment offers do not expose estimated reward points or nominal rupiah value;
- drivers cannot see a point estimate before accepting an assignment;
- no reward quote or snapshot is currently stored on the assignment;
- reward does not vary by package, distance, duration, participants, vehicle, or another business factor.

The project owner has not yet approved the final formula, award timing, visibility before acceptance, guarantee level, conversion rate, withdrawal threshold, override behavior, or cancellation/reversal rules.

Until that decision exists:

- preserve current backend behavior;
- do not treat `100`, `1000`, or `100` as permanent business constants;
- do not duplicate these values in Flutter;
- do not show a promised reward on an assignment card;
- use existing point summary and ledger endpoints only for earned balances;
- read `docs/POINT_REWARD_DECISION_PENDING.md` before modifying assignment, completion, ledger, or withdrawal behavior.

Endpoint groups likely affected by a future change:

```text
POST  /api/v1/admin/bookings/{booking}/driver-assignments
GET   /api/v1/driver/assignments
GET   /api/v1/driver/assignments/{driverAssignment}
PATCH /api/v1/driver/assignments/{driverAssignment}/accept
PATCH /api/v1/driver/assignments/{driverAssignment}/reject
PATCH /api/v1/admin/bookings/{booking}/status
GET   /api/v1/driver/points/summary
GET   /api/v1/driver/points/ledger
GET   /api/v1/driver/withdrawals
POST  /api/v1/driver/withdrawals
GET   /api/v1/admin/withdrawals
GET   /api/v1/admin/withdrawals/{withdrawal}
PATCH /api/v1/admin/withdrawals/{withdrawal}
GET   /api/v1/admin/dashboard
GET   /api/v1/admin/reports/export/drivers
GET   /api/v1/admin/reports/export/withdrawals
GET   /api/v1/admin/audit-logs
GET   /api/v1/admin/audit-logs/{auditLog}
```

The detailed rationale for each endpoint, historical snapshot requirements, OpenAPI changes, migration/backfill concerns, idempotency requirements, and Flutter wording rules is documented in the canonical decision note.

## Completed Admin Web scope

- Dashboard with operational metrics, recent bookings, and queue shortcuts.
- Customer management and account suspension/token revocation.
- Tour package CRUD.
- Travel group management.
- Booking status, driver assignment, cancellation, and participant allocation.
- Payment verification and rejection handling.
- Driver and vehicle verification.
- Vehicle CRUD.
- Withdrawal approval, rejection, and paid transitions.
- CSV reports for bookings, payments, drivers, and withdrawals.
- Filterable audit log index and before/after detail.
- Shared responsive layout, desktop sidebar, mobile menu, active navigation, global success messages, and validation errors.

## Canonical domain services

- `BookingLifecycleService` owns booking transitions, cancellation propagation, row locking, completion rewards, and reward-ledger idempotency.
- `WithdrawalService` owns withdrawal requests and admin status transitions.
- API and Admin Web use shared business logic rather than duplicating lifecycle or balance rules.

## API contract

Canonical contract:

```text
docs/openapi.yaml
```

The contract is the source of truth for Flutter networking and must remain synchronized with:

- endpoint paths and methods;
- authentication requirements;
- request and response schemas;
- enum values;
- validation errors;
- pagination metadata;
- upload requirements and limits.

Web-only Admin routes are intentionally outside the mobile OpenAPI contract.

## CI verification

Workflow:

```text
.github/workflows/backend-tests.yml
```

Confirmed green jobs:

```text
OpenAPI lint
SQLite feature suite
MySQL concurrency suite
```

Frontend build requirement for Blade feature tests:

```bash
npm install --ignore-scripts
npm run build
php artisan test
```

The repository currently has no `package-lock.json`, so CI must continue using `npm install` rather than `npm ci` until a lockfile is committed.

## Production operations available

Health commands:

```bash
php artisan app:health
php artisan app:health --json
php artisan queue:health
php artisan queue:health --json
```

Deployment assets:

```text
deploy/scripts/deploy.sh
deploy/scripts/backup.sh
deploy/supervisor/offroad-booking-worker.conf
docs/QUEUE_PRODUCTION.md
docs/PRODUCTION_DEPLOYMENT.md
```

## Remaining operational track

These items do not block Flutter development, but must be completed before public production launch:

1. Provision staging and production infrastructure.
2. Configure production database, cache, queue, mail, filesystem, HTTPS, trusted proxies, and secrets.
3. Enable monitoring, error tracking, structured logs, and operational alerts.
4. Schedule database and uploaded-file backups.
5. Perform and document a restore test.
6. Run migration dry runs, production smoke tests, and rollback drills.
7. Review upload security, rate limits, session cookies, CORS, and sensitive audit data.
8. Finalize the driver reward and point-conversion product policy before presenting assignment earnings as guaranteed values.

## Flutter handoff

The backend is ready to support native Flutter development for both customer and driver roles.

Recommended Flutter foundation:

```text
Dio
Riverpod
freezed / json_serializable
flutter_secure_storage
go_router
```

Recommended first customer milestone:

1. Environment and API client setup.
2. Login, registration, token refresh/revocation handling, and logout.
3. Package list and package detail.
4. Booking creation and booking history.
5. Booking detail and payment proof upload.

Recommended driver milestone:

1. Driver onboarding and document upload.
2. Vehicle management.
3. Assignment offer list and response.
4. Trip status workflow.
5. Point balance and withdrawal request.

Flutter development should use a staging API and must not rely on production data.

For rewards, Flutter must not hardcode the current point values or show a promised assignment reward. It should consume an approved future API reward contract after the pending product decision is finalized.

## Next active phase

```text
Primary: Flutter customer and driver applications
Parallel: staging and production hardening
Pending product decision: driver reward/point policy
Backend feature phase: closed
```

## Response format rule

After backend changes, respond in this exact order:

1. **Changes**
2. **Endpoint changes**
3. **Cara pull changes**
4. **cURL Postman**
5. **Expected result cURL**
