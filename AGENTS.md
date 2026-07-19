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
- A booking may belong to one travel group through `bookings.travel_group_id`.
- Participant vehicle allocation is controlled by admin and targets accepted driver assignments from the same booking.
- One booking participant may have only one current vehicle allocation.
- Allocated participant count may not exceed the assigned vehicle capacity.
- Driver points use a ledger; withdrawal holds balance while pending.
- Re-upload replaces rejected files, deletes old public files after success, resets status to pending, and clears review metadata.

## Implemented progress

### Foundation and actors

- Laravel 13 + MySQL/MariaDB, health endpoint, Sanctum login/current-user/logout, role middleware.
- Public tour packages, admin package/vehicle CRUD.
- Driver registration, verification, dashboard, availability, documents, vehicles, and document re-upload.
- Customer registration/profile, bookings, participants, and ownership isolation.

### Booking transaction flow

- Payment submission and admin verification.
- Paid guard before assignment.
- Admin driver assignment; driver list/detail/accept/reject.
- Accepted-date conflicts and manual availability rules.
- Strict booking status state machine.

### Travel groups and participant allocation

- Existing `travel_groups`, `travel_group_members`, and `bookings.travel_group_id` structures are used.
- Admin can create/list/detail travel groups with `driver` or `website` source.
- Group creation supports leader, members, member limit, and notes.
- Duplicate member IDs are collapsed; leader is inserted as a member and marked `is_leader`.
- Admin can attach a non-final booking to a travel group.
- Group member limit is enforced against total booking participant counts when attaching bookings.
- New `booking_participant_vehicle_allocations` table maps one participant to one accepted assignment.
- Participant and assignment must belong to the same booking.
- Only accepted assignments may receive participants.
- Vehicle capacity is enforced on allocation.
- Reallocation uses update-or-create, moving a participant from one assignment to another.
- Admin can view allocations and unallocated participants for a booking.

## Current expected end-to-end flow

```text
customer creates booking
→ uploads payment proof
→ admin approves payment
→ admin confirms booking
→ admin assigns one or more drivers/vehicles
→ drivers accept
→ admin creates/chooses travel group and attaches booking
→ admin allocates every booking participant to accepted assignments within vehicle capacity
→ admin starts booking
→ admin completes booking
```

## Current relevant endpoints

```text
GET  /api/v1/admin/travel-groups
POST /api/v1/admin/travel-groups
GET  /api/v1/admin/travel-groups/{travelGroup}
POST /api/v1/admin/travel-groups/{travelGroup}/bookings
GET  /api/v1/admin/bookings/{booking}/participant-allocations
PUT  /api/v1/admin/bookings/{booking}/participant-allocations
```

All protected endpoints require Sanctum and the corresponding role.

## Latest relevant commits

- `d77fbae4bc93fdd8664cf8e92c3277e1be4dd57d` — expose travel group and participant allocation routes.
- `0b5a19467b1b0e3abd8da8d086966ff716e17edc` — link participants to vehicle allocations.
- `3c58f843c61b4829bada4cde18306751d56809ee` — travel group and participant allocation API.
- `670c23e1ca0fcc9973b7a68e186242f465ca1ce2` — participant allocation model.
- `1cbb8950a321bd05f45927d8fabd97dc64b46755` — participant vehicle allocation migration.
- `65f6db18419a6de046e73623eef231073a732e7c` — strict booking state machine.

## Verification status and limitations

- Runtime tests were not executed because no local Laravel runtime/database was available here.
- A migration was added and must be run locally.
- Automated feature tests for travel groups, capacity, cross-booking ownership, and reallocation remain to be added.
- Run locally:

```powershell
php artisan optimize:clear
php artisan migrate
php artisan route:list --path=api/v1/admin
php artisan test
```

## Next progress list

### Priority 1 — Points and withdrawal

- point ledger and trip completion awards
- available/held balances
- withdrawal request, approve, reject, paid

### Priority 2 — Production hardening

- reports, notifications, queues, audit logs, rate limiting, API docs, backup, deployment, and client integration

## Recommended immediate continuation

```text
Points and withdrawal
→ Production hardening
```
