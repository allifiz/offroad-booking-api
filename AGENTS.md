# AGENTS.md

## Project identity

- Project: Offroad Booking Web App
- Repository: `allifiz/offroad-booking-api`
- Backend/API: Laravel 13
- Database: MySQL/MariaDB via XAMPP
- Authentication: Laravel Sanctum
- Admin and customer clients: Laravel web
- Driver client: Flutter native
- Main working branch: `main`
- Local backend path: `C:\Projects\offroad-booking-api`

## Mandatory workflow

1. Inspect current models, enums, migrations, controllers, routes, and tests before changing behavior.
2. Apply backend changes directly to `main`, unless the user requests another branch.
3. Preserve existing enums and relationships unless a migration is required.
4. Operational vehicles belong to drivers through `vehicles.driver_profile_id`.
5. Never expose real access tokens or claim tests passed unless executed.
6. Update this file with every backend/project change.
7. cURL delivery must be a complete test flow from prerequisite setup and all role logins through the main action, success verification, and important regression failures.

## Required response structure

After every backend change, respond in this exact order:

1. **Changes**
2. **Endpoint changes**
3. **Cara pull changes**
4. **cURL Postman**
5. **Expected result cURL**

Use Indonesian, ready-to-run PowerShell, importable full-flow cURL, expected HTTP status/JSON, migration requirements, test status, and latest commit SHA.

## Product decisions

- Actors: admin, driver, customer.
- Driver and vehicle registration start pending/unavailable.
- Admin verifies drivers, vehicles, driver documents, and vehicle documents.
- Admin offers assignments; drivers accept or reject them.
- Driver/vehicle availability does not automatically change when an assignment is accepted.
- Availability indicates readiness to receive work; conflicts use accepted assignments on the same tour date.
- Multiple offers may exist for the same driver/date, but only one conflicting assignment may be accepted.
- Assignment creation requires a paid booking.
- Booking transitions are strict: pending → confirmed/cancelled, confirmed → ongoing/cancelled, ongoing → completed, completed/cancelled final.
- Confirming requires paid payment; starting/completing requires paid payment and accepted assignment.
- Travel groups may originate from `driver` or `website`; admin controls operational grouping in MVP.
- Participant allocation targets accepted assignments from the same booking and may not exceed vehicle capacity.
- Completing a booking awards each accepted driver a configurable number of points once per booking.
- Default MVP values: 100 points per completed trip, 1 point = Rp1.000, minimum withdrawal 100 points. Values are configurable via environment.
- Withdrawal creation moves points from available to held.
- Rejected withdrawal releases held points back to available.
- Approved withdrawal remains held until marked paid.
- Paid withdrawal removes points from held and records a debit ledger entry.

## Implemented progress

### Foundation and actors

- Laravel 13 + MySQL/MariaDB, Sanctum, role middleware, tour packages, vehicles.
- Driver registration, verification, dashboard, availability, documents, vehicles, and document re-upload.
- Customer registration/profile, bookings, participants, and ownership isolation.

### Booking transaction flow

- Payment submission/admin verification, paid assignment guard, assignment response, strict booking state machine.
- Travel groups and participant-to-vehicle allocation with ownership and capacity validation.

### Points and withdrawal

- Existing `point_ledgers`, `withdrawals`, and driver available/held point balances are used.
- Booking completion credits every accepted driver once per booking using `PointLedgerType::CREDIT`.
- Reward amount is configured by `OFFROAD_POINTS_PER_COMPLETED_TRIP` (default 100).
- Driver can view point summary and paginated ledger.
- Driver can list withdrawals and submit a withdrawal when available balance is sufficient.
- Withdrawal amount is calculated server-side using `OFFROAD_RUPIAH_PER_POINT` (default 1000).
- Minimum withdrawal is configured by `OFFROAD_MINIMUM_WITHDRAWAL_POINTS` (default 100).
- Pending withdrawal records a HOLD ledger entry and moves available points to held.
- Admin can list/detail withdrawals and process strict transitions: pending → approved/rejected, approved → paid.
- Rejection records RELEASE and returns held points to available.
- Paid records DEBIT and removes points from held.
- Balance mutations use database transactions and row locks.

### Critical feature tests

- `tests/Feature/DriverWithdrawalFlowTest.php` covers withdrawal request, insufficient balance, HOLD, RELEASE, DEBIT, and strict withdrawal transitions.
- `tests/Feature/BookingStateAndRewardFlowTest.php` covers illegal booking skips, unpaid confirmation, accepted-assignment requirement, completion reward, repeated completion rejection, and existing-ledger reward idempotency.
- `tests/Feature/PaymentFlowTest.php` covers proof submission, pending booking synchronization, duplicate pending rejection, admin approval, rejection reason validation, resubmission after failure, and repeated verification prevention.
- `tests/Feature/DriverAssignmentResponseFlowTest.php` covers owned accept/reject, rejection reason validation, repeated response prevention, cross-driver ownership isolation, same-date driver conflict, same-date vehicle conflict, and different-date acceptance.
- Payment test fixtures that create a payment directly must synchronize `bookings.payment_status` with the created payment status, matching the real API transaction.
- Booking completion assertions verify driver available balance and exactly one booking CREDIT ledger entry.
- Payment tests use fake public storage and verify the submitted proof path exists.
- Tests use SQLite in-memory through the existing `phpunit.xml` configuration and Laravel `RefreshDatabase`.

## Current expected end-to-end flow

```text
customer creates booking
→ uploads payment proof
→ admin approves payment and confirms booking
→ admin assigns driver/vehicle
→ driver accepts
→ admin starts and completes booking
→ accepted driver receives points once
→ driver submits withdrawal
→ available points move to held
→ admin approves then marks paid, or rejects and releases points
```

## Current relevant endpoints

```text
POST  /api/v1/customer/bookings/{booking}/payments
PATCH /api/v1/admin/payments/{payment}/verification
PATCH /api/v1/admin/bookings/{booking}/status
GET   /api/v1/driver/assignments
GET   /api/v1/driver/assignments/{driverAssignment}
PATCH /api/v1/driver/assignments/{driverAssignment}/accept
PATCH /api/v1/driver/assignments/{driverAssignment}/reject
GET   /api/v1/driver/points/summary
GET   /api/v1/driver/points/ledger
GET   /api/v1/driver/withdrawals
POST  /api/v1/driver/withdrawals
GET   /api/v1/admin/withdrawals
GET   /api/v1/admin/withdrawals/{withdrawal}
PATCH /api/v1/admin/withdrawals/{withdrawal}
```

## Latest relevant commits

- `4161ee4f890c8df71dc7dc92a963443607ce208c` — assignment accept/reject, ownership, repeated response, and date-conflict feature tests.
- `13b7f7b7f2842cafa04212ac5254b0f1a8250c79` — synchronize payment test fixture booking status with directly-created payment status.
- `7927e3e548593ad53d4aefb29823fbcba59f2528` — payment proof, approval, rejection, resubmission, and repeated-verification feature tests.
- `7ca1a01876bd928e801d792258cb85228640abbf` — booking state-machine and completion reward idempotency feature tests.
- `41ee6aecfb0b538c2f61def9288ed97c33830de2` — withdrawal feature tests for hold, release, debit, balance validation, and strict transitions.

## Verification status and limitations

- User ran `PaymentFlowTest`: 4 passed and one duplicate-pending test failed because its direct fixture created a pending payment while leaving the booking unpaid.
- The payment fixture was corrected; the updated payment test still needs to be rerun locally.
- The assignment response tests were added but not executed in this environment because the GitHub connector has no PHP runtime.
- No migration was required for the assignment tests.
- Participant allocation/capacity and true concurrent withdrawal tests remain to be added.
- Run locally:

```powershell
php artisan optimize:clear
php artisan migrate
php artisan test --filter=PaymentFlowTest
php artisan test --filter=DriverAssignmentResponseFlowTest
php artisan test --filter=DriverWithdrawalFlowTest
php artisan test --filter=BookingStateAndRewardFlowTest
php artisan test
```

## Next progress list

### Priority 1 — Remaining critical feature tests

- participant allocation and vehicle capacity
- concurrent withdrawal protection

### Priority 2 — Production hardening

- audit logs
- notifications and queues
- rate limiting and API documentation
- reports, backup, deployment, and client integration

## Recommended immediate continuation

```text
Run/fix assignment tests locally
→ Add participant allocation/capacity tests
→ Audit logs and notifications
```
