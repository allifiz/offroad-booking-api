# Offroad Booking — Project Progress Checkpoint

Last updated: 2026-07-24 (Asia/Jakarta)  
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
| Manual Postman E2E happy path | Passed |
| Real database relationship continuity | Verified |
| Point implementation | Technically passed |
| Point product policy | Pending decision |
| Flutter integration readiness | In progress |
| Driver push notification integration | Implemented |

The backend feature-development phase is closed. Further backend work should be limited to verified defects, Flutter integration requirements, security hardening, infrastructure integration, or explicitly approved product changes.

## Flutter integration note

The driver app now registers FCM device tokens against the backend and receives assignment-offer push notifications through the queued notification flow. Assignment creation still persists the canonical Laravel `notifications` record, while push delivery is handled separately through FCM for the assigned driver only.

## Manual Postman E2E acceptance

Test definition:

```text
docs/POSTMAN_END_TO_END_TEST.md
```

Verification record:

```text
docs/POSTMAN_E2E_VERIFICATION.md
```

The complete documented flow was executed manually against the current Laravel backend and a real local MySQL/MariaDB database. All requests completed successfully without backend errors.

The same response IDs and tokens were reused from the beginning to the end, verifying the persisted relationship chain:

```text
admin -> tour package
customer -> booking -> participants
booking -> payment -> admin verification
booking -> driver assignment -> driver -> vehicle
driver acceptance -> ongoing trip -> completed trip
completed trip -> point ledger -> driver balance
driver balance -> withdrawal -> admin processing
```

This manual acceptance supplements the green automated CI and confirms that the current happy path behaves as expected through Postman before Flutter integration.

## Point-policy exception

The reward and withdrawal mechanics were technically exercised successfully during the Postman flow:

```text
completed booking
-> 100 points credited
-> ledger record created
-> withdrawal requested
-> withdrawal processed
```

However, this result does **not** approve the current point values or UX as the final product policy.

Temporary MVP behavior still under discussion:

```text
100 points per completed trip
Rp1,000 per point
100-point minimum withdrawal
no estimated reward shown before assignment acceptance
```

Canonical pending-decision document:

```text
docs/POINT_REWARD_DECISION_PENDING.md
```

Any agent working on assignment, booking completion, reward, point ledger, conversion, reporting, or withdrawal behavior must read that document first. Flutter must not hardcode the current point values.

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
- Driver assignment push notifications through FCM for assigned drivers.

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
2. Login, registration, token revocation handling, and logout.
3. Package list and package detail.
4. Booking creation and booking history.
5. Booking detail and payment proof upload.

Recommended driver milestone:

1. Driver onboarding and document upload.
2. Vehicle management.
3. Assignment offer list and response.
4. Trip status workflow.
5. Point balance and withdrawal request, without hardcoding pending reward-policy values.

Flutter development should use a staging API and must not rely on production data.

## Next active phase

```text
Primary: Flutter customer and driver applications
Parallel: staging and production hardening
Backend feature phase: closed
Point policy: pending product decision
```

## Response format rule

After backend changes, respond in this exact order:

1. **Changes**
2. **Endpoint changes**
3. **Cara pull changes**
4. **cURL Postman**
5. **Expected result cURL**
