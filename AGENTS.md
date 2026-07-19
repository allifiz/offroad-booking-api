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
- Booking cannot become ongoing/completed without paid payment and an accepted assignment.
- Participant allocation remains flexible and controlled by admin.
- Travel groups can originate from a driver or website.
- Driver points use a ledger; withdrawal holds balance while pending.
- Re-upload replaces the rejected document file, deletes the old public-disk file after success, resets verification to pending, and clears old reviewer/rejection metadata.

## Implemented progress

### Foundation, authentication, and master data

- Laravel 13 + MySQL/MariaDB, health endpoint, Sanctum login/current-user/logout, and role middleware.
- Public tour-package list/detail, admin tour-package CRUD, and admin vehicle CRUD.

### Driver registration, verification, and dashboard

- Multipart driver registration with profile, documents, driver-owned vehicle, vehicle documents, and photos.
- Admin driver and vehicle approve/reject.
- Driver profile/status/detail/update, manual availability, and owned-vehicle list/detail.
- Driver document and vehicle document verification by admin.
- Rejection reason is required when an individual document is rejected.
- Driver may re-upload only an owned rejected document.
- Re-upload accepts JPG/JPEG/PNG/PDF up to 5 MB, optionally updates document number/expiry, replaces the file, resets status to pending, and clears review metadata.
- Document and vehicle ownership isolation returns `404`.

### Customer, booking, payment, and assignments

- Customer registration/profile and owned booking create/list/detail.
- Server-side booking code, participant count, total calculation, package/date/participant/leader validation.
- Customer payment proof submission/history/detail; admin payment verification.
- Paid booking guard before assignment.
- Admin booking list/detail/status and driver/vehicle assignment.
- Driver assignment list/detail/accept/reject with repeated-response and accepted-date conflict guards.
- Ongoing/completed require paid payment and accepted assignment.

## Current expected end-to-end flow

```text
customer creates booking
→ customer uploads payment proof
→ admin approves payment
→ admin assigns driver and vehicle
→ driver accepts
→ booking ongoing
→ booking completed
```

Document correction flow:

```text
admin rejects individual document with reason
→ driver sees rejection in profile/vehicle detail
→ driver uploads replacement
→ document becomes pending and old review metadata is cleared
→ admin approves or rejects again
```

## Current relevant endpoints

```text
GET   /api/v1/driver/profile
PATCH /api/v1/driver/profile
PATCH /api/v1/driver/availability
GET   /api/v1/driver/vehicles
GET   /api/v1/driver/vehicles/{vehicle}
POST  /api/v1/driver/documents/{driverDocument}/reupload
POST  /api/v1/driver/vehicles/{vehicle}/documents/{vehicleDocument}/reupload
PATCH /api/v1/admin/driver-documents/{driverDocument}/verification
PATCH /api/v1/admin/driver-vehicles/{vehicle}/documents/{vehicleDocument}/verification
GET   /api/v1/driver/assignments
GET   /api/v1/driver/assignments/{driverAssignment}
PATCH /api/v1/driver/assignments/{driverAssignment}/accept
PATCH /api/v1/driver/assignments/{driverAssignment}/reject
GET   /api/v1/customer/payments
POST  /api/v1/customer/bookings/{booking}/payments
GET   /api/v1/customer/payments/{payment}
GET   /api/v1/admin/payments
GET   /api/v1/admin/payments/{payment}
PATCH /api/v1/admin/payments/{payment}/verification
```

All protected endpoints require Sanctum and the corresponding role.

## Latest relevant commits

- `8cbd4bf56685537cba15e86e0373bde89e4278e8` — expose admin document verification and driver re-upload routes.
- `a1562e452f1fb1561784f1960dd1d0e4b919516c` — admin individual document verification.
- `22dc8cacd6d64c97a592dea2790f332882dd738a` — rejected driver/vehicle document re-upload.
- `579544fc19180903597b77da2137c76ee6890206` — driver dashboard routes.
- `cdf146bab2bb5a8f98fd8e711d0b45172e97dddc` — driver profile, availability, and owned vehicles.
- `a9acd8a5be926042ee565eda69b289e2e701a782` — driver assignment response routes.
- `6c15b591c5fe3f46efc42cd6910d56634e4ded62` — payment routes.

## Verification status and limitations

- Runtime tests were not executed in this environment because no local Laravel runtime/database was available.
- No migration was added for document verification/re-upload.
- Automated feature tests specifically for document verification/re-upload remain to be added.
- Run locally:

```powershell
php artisan optimize:clear
php artisan storage:link
php artisan route:list --path=api/v1
php artisan test
```

## Next progress list

### Priority 1 — Booking state-machine hardening

```text
pending → confirmed | cancelled
confirmed → ongoing | cancelled
ongoing → completed
completed/cancelled → final
```

Prevent direct skips, backward transitions, and illegal cancellation.

### Priority 2 — Travel groups and participant allocation

- driver-origin and website-origin groups
- leader/member management
- participant-to-vehicle allocation
- capacity enforcement

### Priority 3 — Points, withdrawal, and production hardening

- point ledger and trip awards
- held/available balances and withdrawal processing
- reports, notifications, queues, audit logs, rate limiting, API docs, backup, deployment, and client integration

## Recommended immediate continuation

```text
Booking state-machine hardening
→ Travel groups and participant allocation
→ Points and withdrawal
```
