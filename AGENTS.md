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
- Changing vehicle identity/capacity, adding/replacing a vehicle document, or adding/deleting a vehicle photo resets vehicle verification to pending and availability to unavailable.
- Vehicle document type is unique per vehicle; uploading the same type replaces the previous file after the new file is safely stored.
- Vehicle photos use types: `front`, `back`, `left`, `right`, `interior`, or `other`.
- Reordering photos does not reset verification because it does not change media content.
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
- Driver can upload or replace one document per document type.
- Document replacement resets document and vehicle verification and deletes the previous public file after success.
- Driver can upload vehicle photos, reorder owned photos, and delete owned photos.
- Adding or deleting a photo resets vehicle verification; reordering alone does not.
- Registration vehicle photos now write to the real `vehicle_photos.type` column instead of the invalid `photo_type` key.

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
- `DriverVehicleCrudFlowTest`
- Tests use SQLite in-memory and `RefreshDatabase`; true locking/concurrency must be validated with MySQL.

## Current relevant endpoints

```text
GET    /api/v1/driver/vehicles
POST   /api/v1/driver/vehicles
GET    /api/v1/driver/vehicles/{vehicle}
PATCH  /api/v1/driver/vehicles/{vehicle}
DELETE /api/v1/driver/vehicles/{vehicle}
POST   /api/v1/driver/vehicles/{vehicle}/documents
POST   /api/v1/driver/vehicles/{vehicle}/documents/{vehicleDocument}/reupload
POST   /api/v1/driver/vehicles/{vehicle}/photos
PUT    /api/v1/driver/vehicles/{vehicle}/photos/order
DELETE /api/v1/driver/vehicles/{vehicle}/photos/{vehiclePhoto}
```

All protected endpoints require Sanctum and the corresponding role.

## Latest relevant commits

- `13218a9fdc1671854f983c87354d1209a3dbf931` — expose driver vehicle document/photo management routes.
- `9d0a01e0112e2f95fa6c7fe213fddf7dd3ff77ad` — fix registration vehicle photo column mapping.
- `29ebabad2bbbf858db3c111524dc7973a0e77e01` — driver vehicle document upload/replacement and photo upload/delete/order management.
- `0d7a59596637c25f4ac450604972ccbdabd6136c` — driver vehicle CRUD feature tests.
- `89eb5d2ee2a08fbed92d08154a0b4f6c5a5338c3` — expose driver vehicle CRUD routes.

## Verification status and limitations

- Runtime tests were not executed in this environment because the GitHub connector has no PHP runtime.
- No migration was required for vehicle document/photo management.
- Vehicle media feature tests remain to be added and run locally.
- Existing driver registration tests should be rerun because the photo field mapping was corrected.
- Run locally:

```powershell
php artisan optimize:clear
php artisan route:list --path=api/v1/driver/vehicles
php artisan test --filter=DriverVehicleCrudFlowTest
php artisan test
```

## Next progress list

### Priority 1 — Vehicle media verification

- add feature tests for document replacement, file cleanup, photo ownership, reorder, deletion, and verification reset
- run/fix full current test suite

### Priority 2 — Test execution and concurrency

- concurrent withdrawal protection using MySQL test database

### Priority 3 — Production hardening

- audit logs
- notifications and queues
- rate limiting and OpenAPI documentation
- reports, backup, deployment, and client integration

## Recommended immediate continuation

```text
Run vehicle media and full test suite
→ Add vehicle media feature tests
→ Audit logs and notifications
```
