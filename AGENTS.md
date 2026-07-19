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
- Operational notifications are stored in the database and dispatched through Laravel queue after transaction commit.
- Notification ownership is isolated; users can only read their own inbox entries.

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

- Central `AuditObserver` records created, updated, and deleted events for sensitive models.
- Admin can paginate/filter logs and view detail; audit endpoints are read-only.

### Notifications and queues

- `OperationalNotification` implements `ShouldQueue`, uses the database channel, and dispatches after commit.
- Automatic notifications cover payment status, booking status, assignment offer/response, driver verification, vehicle verification, and withdrawal status.
- Authenticated admin/customer/driver users share one notification inbox API.
- Users can filter unread entries, mark one entry read, or mark all entries read.

### Critical feature tests

- `DriverWithdrawalFlowTest`
- `BookingStateAndRewardFlowTest`
- `PaymentFlowTest`
- `DriverAssignmentResponseFlowTest`
- `ParticipantAllocationFlowTest`
- `DriverVehicleCrudFlowTest`
- `VehicleMediaFlowTest`
- `AuditLogFlowTest`
- `NotificationFlowTest` covers inbox listing, unread count, per-notification read, read-all, and cross-user ownership denial.
- Tests use SQLite in-memory and `RefreshDatabase`; true locking/concurrency must be validated with MySQL.

## Current relevant endpoints

```text
GET   /api/v1/notifications
PATCH /api/v1/notifications/read-all
PATCH /api/v1/notifications/{notification}/read
GET   /api/v1/admin/audit-logs
GET   /api/v1/admin/audit-logs/{auditLog}
```

All protected endpoints require Sanctum and the corresponding role where applicable.

## Latest relevant commits

- `038c50f470af8899a4313505529d8b4ad81607a8` — notification inbox feature tests.
- `19e113ac41de7c00b25b369a89570d4aba1e5e1d` — expose authenticated notification routes.
- `c417e616db360e5c9da0a3e0b3ec864c5d4620c7` — notification inbox controller.
- `a45474736b186e65fa97f944680daae00f4cb8f8` — register operational notification observers.
- `3252550c9dc10a13002bc6cf77bf4e890398edd6` — operational state-change observer.
- `2d561bedcb8b0891e4ba08e01802fe96db75d9dc` — queued database notification class.
- `880a3439a14257ff7827b218390180d98c45aba7` — notifications table migration.

## Verification status and limitations

- Notification runtime tests were not executed in this environment because the GitHub connector has no PHP runtime.
- The feature requires the new `notifications` migration.
- `phpunit.xml` uses `QUEUE_CONNECTION=sync`, so queued notifications execute synchronously during tests.
- Production must run a queue worker when `QUEUE_CONNECTION` is asynchronous, for example database or Redis.
- Run locally:

```powershell
php artisan optimize:clear
php artisan migrate
php artisan route:list --path=api/v1/notifications
php artisan test --filter=NotificationFlowTest
php artisan test
```

## Next progress list

### Priority 1 — Verification and concurrency

- run/fix `NotificationFlowTest`, `AuditLogFlowTest`, and the full test suite
- validate concurrent withdrawal protection using a MySQL test database

### Priority 2 — Production hardening

- production queue configuration and worker supervision
- rate limiting and OpenAPI documentation
- reports, backup, deployment, and client integration

## Recommended immediate continuation

```text
Run/fix notification and full test suite
→ Validate concurrent withdrawal with MySQL
→ Add rate limiting and OpenAPI documentation
```
