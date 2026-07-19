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
- Vehicle identity/capacity or media-content changes reset verification to pending and availability to unavailable.
- Vehicles with offered or accepted assignments cannot be deleted.
- Admin offers assignments; drivers accept or reject them.
- Assignment conflicts use accepted assignments on the same tour date.
- Assignment creation requires a paid booking.
- Booking transitions are strict: pending → confirmed/cancelled, confirmed → ongoing/cancelled, ongoing → completed, completed/cancelled final.
- Participant allocation targets accepted assignments from the same booking and may not exceed vehicle capacity.
- Completing a booking awards each accepted driver configurable points once per booking.
- Withdrawal creation moves available points to held; rejection releases them; paid removes them from held.
- Sensitive model create/update/delete operations are automatically audited.
- Audit logs record actor, subject, old/new values, IP, user agent, URL, and request method.
- Password, token, and stored-file path fields are excluded from audit payloads.
- Audit logs are read-only through admin endpoints; no update/delete API is exposed.

## Implemented progress

### Foundation and actors

- Laravel 13 + MySQL/MariaDB, Sanctum, role middleware, tour packages, vehicles.
- Driver registration, verification, dashboard, availability, documents, vehicle CRUD/media, and document re-upload.
- Customer registration/profile, bookings, participants, and ownership isolation.

### Booking transaction flow

- Payment submission/admin verification, paid assignment guard, assignment response, strict booking state machine.
- Travel groups and participant-to-vehicle allocation with ownership and capacity validation.

### Points and withdrawal

- Booking completion credits accepted drivers once per booking.
- Driver point summary, ledger, withdrawal request/list.
- Admin strict withdrawal processing: pending → approved/rejected, approved → paid.
- Balance mutations use transactions and row locks.

### Audit logs

- `audit_logs` stores actor, event, polymorphic subject, old/new JSON, IP, user-agent, URL, method, and timestamps.
- Central `AuditObserver` records created, updated, and deleted events without duplicating controller logic.
- Observed models: Booking, participant allocation, driver assignment/document/profile, Payment, TravelGroup, Vehicle, VehicleDocument, VehiclePhoto, and Withdrawal.
- Admin can paginate and filter logs by event, actor, subject type/id, and date range.
- Admin can view one audit-log detail.

### Critical feature tests

- `DriverWithdrawalFlowTest`
- `BookingStateAndRewardFlowTest`
- `PaymentFlowTest`
- `DriverAssignmentResponseFlowTest`
- `ParticipantAllocationFlowTest`
- `DriverVehicleCrudFlowTest`
- `VehicleMediaFlowTest`
- `AuditLogFlowTest` covers automatic actor/request logging, old/new values, sensitive-field exclusion, admin filtering/detail, and non-admin denial.
- Tests use SQLite in-memory and `RefreshDatabase`; true locking/concurrency must be validated with MySQL.

## Current relevant endpoints

```text
GET /api/v1/admin/audit-logs
GET /api/v1/admin/audit-logs/{auditLog}
```

All protected endpoints require Sanctum and the corresponding role.

## Latest relevant commits

- `a92900d5a7a52a3a95a4faabf29aba28a81c4830` — audit log feature tests.
- `3c1ab30c2063b7447715d188c02d7707fb4d42a0` — expose admin audit-log endpoints.
- `02083801ae17ac68634f6cf46fb9bc82cf26917c` — admin audit-log list/detail controller.
- `a47d1f03e6135a34aa6d615653e3836f89df27aa` — register centralized observer for sensitive models.
- `0063fa5cbd4d82c3d805b2d38f72df8c12e7e552` — centralized audit observer.
- `e0a38994cd1d0aec06ff6dcd987b1bd5ef40f184` — audit log model.
- `ec4ab183754a2c9a1ceacc627dbd5dabf67e7a05` — audit logs migration.

## Verification status and limitations

- Audit-log runtime tests were not executed in this environment because the GitHub connector has no PHP runtime.
- The audit feature requires the new `audit_logs` migration.
- Audit logs currently track model-level create/update/delete; custom semantic event names such as `payment.approved` can be added later if reporting requires them.
- Run locally:

```powershell
php artisan optimize:clear
php artisan migrate
php artisan route:list --path=api/v1/admin/audit-logs
php artisan test --filter=AuditLogFlowTest
php artisan test
```

## Next progress list

### Priority 1 — Verification and concurrency

- run/fix `AuditLogFlowTest` and full test suite
- validate concurrent withdrawal protection using a MySQL test database

### Priority 2 — Production hardening

- notifications and queues
- rate limiting and OpenAPI documentation
- reports, backup, deployment, and client integration

## Recommended immediate continuation

```text
Run/fix AuditLogFlowTest and full suite
→ Validate concurrent withdrawal with MySQL
→ Add notifications and queues
```
