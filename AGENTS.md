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
- Driver-owned vehicles are always created with ownership `driver`, verification `pending`, and availability `unavailable`.
- Changing vehicle identity/capacity data resets verification to pending and availability to unavailable.
- Vehicles with offered or accepted assignments cannot be deleted.
- Admin offers assignments; drivers accept or reject them.
- Availability does not automatically change when an assignment is accepted.
- Conflicts use accepted assignments on the same tour date.
- Assignment creation requires a paid booking.
- Booking transitions are strict: pending → confirmed/cancelled, confirmed → ongoing/cancelled, ongoing → completed, completed/cancelled final.
- Participant allocation targets accepted assignments from the same booking and may not exceed vehicle capacity.
- Completing a booking awards each accepted driver configurable points once per booking.
- Withdrawal creation moves available points to held; rejection releases them; paid removes them from held.

## Implemented progress

### Foundation and actors

- Laravel 13 + MySQL/MariaDB, Sanctum, role middleware, tour packages, vehicles.
- Driver registration, verification, dashboard, availability, documents, and document re-upload.
- Customer registration/profile, bookings, participants, and ownership isolation.

### Driver-owned vehicle management

- Driver can list, create, view, update, and delete owned vehicles.
- Cross-driver vehicle access returns `404`.
- Create forces `ownership_type=driver`, `verification_status=pending`, and `status=unavailable`.
- Plate numbers remain globally unique.
- Changes to name, plate, brand, model, year, or capacity reset verification metadata and availability.
- Notes-only updates do not trigger re-verification.
- Delete is blocked while offered or accepted assignments exist.
- Vehicle has a `driverAssignments()` relationship for active-assignment guards.

### Booking transaction flow

- Payment submission/admin verification, paid assignment guard, assignment response, strict booking state machine.
- Travel groups and participant-to-vehicle allocation with ownership and capacity validation.

### Points and withdrawal

- Booking completion credits accepted drivers once per booking.
- Driver point summary, ledger, withdrawal request/list.
- Admin strict withdrawal processing: pending → approved/rejected, approved → paid.
- Balance mutations use transactions and row locks.

### Critical feature tests

- `DriverWithdrawalFlowTest`
- `BookingStateAndRewardFlowTest`
- `PaymentFlowTest`
- `DriverAssignmentResponseFlowTest`
- `ParticipantAllocationFlowTest`
- Tests use SQLite in-memory and `RefreshDatabase`; true locking/concurrency must be validated with MySQL.

## Current relevant endpoints

```text
GET    /api/v1/driver/vehicles
POST   /api/v1/driver/vehicles
GET    /api/v1/driver/vehicles/{vehicle}
PATCH  /api/v1/driver/vehicles/{vehicle}
DELETE /api/v1/driver/vehicles/{vehicle}
```

All protected endpoints require Sanctum and the corresponding role.

## Latest relevant commits

- `89eb5d2ee2a08fbed92d08154a0b4f6c5a5338c3` — expose driver vehicle CRUD routes.
- `265a29a16a81e58662c740cb1804f2acad55ec3b` — add vehicle-to-assignment relationship.
- `87191a79a587351820e56358122c076885831359` — implement driver-owned vehicle create/update/delete.
- `776fafd9f1ece0345ef658e28e656ade21c037ac` — participant allocation feature tests.
- `4161ee4f890c8df71dc7dc92a963443607ce208c` — assignment response/conflict tests.

## Verification status and limitations

- Runtime tests were not executed in this environment because the GitHub connector has no PHP runtime.
- No migration was required for driver vehicle CRUD.
- Driver vehicle document/photo creation APIs and CRUD feature tests remain to be added.
- Run locally:

```powershell
php artisan optimize:clear
php artisan route:list --path=api/v1/driver/vehicles
php artisan test
```

## Next progress list

### Priority 1 — Driver vehicle completeness

- driver vehicle CRUD feature tests
- vehicle document upload/create management
- vehicle photo upload/delete/order management

### Priority 2 — Test execution and concurrency

- run/fix full current test suite
- concurrent withdrawal protection using MySQL test database

### Priority 3 — Production hardening

- audit logs
- notifications and queues
- rate limiting and OpenAPI documentation
- reports, backup, deployment, and client integration

## Recommended immediate continuation

```text
Run driver vehicle CRUD and full test suite
→ Add vehicle document/photo management
→ Audit logs and notifications
```
