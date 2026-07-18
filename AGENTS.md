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

## Mandatory agent workflow

1. Read this file before inspecting or changing backend behavior.
2. Inspect the current repository state, models, enums, migrations, controllers, routes, and tests before implementing changes.
3. Apply requested backend changes directly to `main`, unless the user explicitly requests a branch or pull request.
4. Preserve existing enum values and relationships unless a migration is genuinely required.
5. Do not invent company-owned vehicle flows. Operational vehicles belong to drivers.
6. `driver_id` means `users.id`. Vehicle ownership follows:

   ```text
   vehicles.driver_profile_id
   → driver_profiles.id
   → driver_profiles.user_id
   → users.id
   ```

7. Never expose or repeat a real access token supplied by the user. Recommend revoking an exposed token.
8. Keep responses in Indonesian with a casual but technically clear tone.
9. Never claim tests passed when they were not executed.

## Mandatory AGENTS.md maintenance

`AGENTS.md` is the canonical handoff and latest-progress document for this repository.

Every backend or project change must also update this file in the same work session. A change is not considered fully completed until `AGENTS.md` reflects the new state.

After every change, update all relevant sections, including:

- implemented progress
- newly added or changed endpoints
- business rules and state guards
- migrations, seeders, uploads, storage, or configuration changes
- bug fixes and regression protections
- latest relevant commit SHA
- known limitations or tests that were not executed
- next-progress list
- recommended immediate priority

Rules:

- Remove items from the pending roadmap when they are completed.
- Move completed scope into **Implemented progress**.
- Reorder priorities when the project state changes.
- Record important product decisions that future agents must preserve.
- Do not turn this file into a raw changelog; keep it as an accurate current-state guide.
- Documentation-only changes to this file do not require recursively adding another history entry.

## Required response structure

After every backend change, respond using these headings in this exact order. Do not rename, reorder, or omit them:

1. **Changes**
2. **Endpoint changes**
3. **Cara pull changes**
4. **cURL Postman**
5. **Expected result cURL**

### Changes

Include:

- actual behavior implemented
- important files or backend areas affected
- validation, authorization, and business rules
- migration, seeder, storage, test, or documentation changes
- commit SHA
- limitations and tests not executed
- confirmation that `AGENTS.md` was updated when applicable

Do not merely say “done”.

### Endpoint changes

List every new, modified, or removed endpoint using method and path:

```text
POST  /api/v1/example
PATCH /api/v1/example/{id}
```

Also state:

- required role
- Sanctum requirement
- important request fields
- important state requirements

When no endpoint changed, explicitly say so.

### Cara pull changes

Use ready-to-run PowerShell commands for Windows, beginning with:

```powershell
cd C:\Projects\offroad-booking-api
git switch main
git pull origin main
```

Then add only commands that are relevant:

```powershell
composer install
php artisan optimize:clear
php artisan migrate
php artisan db:seed
php artisan storage:link
php artisan route:list --path=api/v1
php artisan test
php artisan serve
```

Clearly state when there is no migration. Never suggest destructive commands such as `migrate:fresh` without explicit necessity and a warning.

### cURL Postman

All cURL examples must be importable through:

```text
Postman → Import → Raw text
```

Requirements:

- complete local URL, normally `http://127.0.0.1:8000`
- `Accept: application/json`
- `Content-Type: application/json` for JSON bodies
- protected endpoints use `Authorization: Bearer {{admin_token}}`, `{{customer_token}}`, or `{{driver_token}}`
- complete body
- normal success flow
- important validation and authorization failures
- regression cURL for a bug that was fixed

Never reuse a real token from chat.

### Expected result cURL

For every important request, provide:

- expected HTTP status
- representative JSON matching the implemented controller
- key state/database change

Use expected status codes consistently:

- `200` successful read/update
- `201` successful creation
- `401` unauthenticated
- `403` role/authorization denial when implemented that way
- `404` inaccessible or missing ownership-scoped resource
- `422` validation or illegal state transition

## Current product decisions

- Actors: admin, driver, customer.
- Driver and vehicle registration start as `pending` and unavailable.
- Admin verifies drivers and driver-owned vehicles.
- Operational vehicles are driver-owned; company vehicles are outside the intended flow.
- Admin offers assignments; drivers accept or reject them.
- Booking assignment requires payment to be complete.
- Booking cannot start or complete unless payment is `paid` and an assignment is `accepted`.
- Participant allocation per vehicle is flexible and controlled by admin.
- Travel groups can originate from a driver or the website.
- Driver points use a ledger.
- Withdrawal holds balance while pending.

## Implemented progress

### Foundation and authentication

- Laravel 13 and MySQL/MariaDB initialized.
- API health endpoint implemented.
- Sanctum login, current-user, and logout endpoints implemented.
- Role middleware implemented for admin, driver, and customer.

### Master data

- Public tour-package listing and detail.
- Admin tour-package CRUD.
- Admin vehicle CRUD.

### Driver registration and verification

- Driver profile, documents, vehicles, vehicle documents, and photos.
- Multipart driver registration.
- New drivers and vehicles default to pending/unavailable.
- Admin driver list/detail/approve/reject.
- Admin driver-vehicle approve/reject.
- Rejection reason required when rejected.

### Customer and booking

- Customer registration with Sanctum token.
- Customer profile show/update.
- Customer booking creation and participants.
- Server-side booking code, participant count, and total calculation.
- Active package, future date, min/max participant, and single-leader validation.
- First participant becomes leader when none is selected.
- Customer booking history/filter/detail.
- Customer booking ownership isolation.

### Admin booking operations

- Booking list/detail with filters for status, payment status, tour date, booking code, and customer.
- Admin booking status update.
- Final states `completed` and `cancelled` cannot be changed.
- Cancelling a booking cancels offered or accepted assignments.

### Driver assignment

- Admin assigns approved/available driver and approved/available driver-owned vehicle.
- Driver and vehicle conflicts are checked for the same tour date.
- Cancelled/completed bookings cannot receive assignments.
- Assignment starts as `offered`.
- Admin can cancel an assignment.
- Assignment statuses: `offered`, `accepted`, `rejected`, `cancelled`.

### Mandatory booking guards already patched

Do not remove these rules:

1. Assignment creation requires `booking.payment_status = paid`.
2. `ongoing` requires payment `paid` and at least one accepted assignment.
3. `completed` requires payment `paid` and at least one accepted assignment.
4. Offered, rejected, or cancelled assignments do not authorize ongoing/completed.

Current expected flow:

```text
pending + unpaid
→ payment paid
→ admin assigns driver and vehicle
→ assignment offered
→ driver accepts
→ booking ongoing
→ booking completed
```

## Known local test data

Local examples only; never hardcode these IDs:

- Driver profile ID: `1`
- Driver user ID: `12`
- Driver name: `Driver Andi`
- Driver status: `available`
- Driver verification: `approved`
- Vehicle ID: `1`
- Vehicle belongs to driver profile ID `1`
- Vehicle status: `available`
- Vehicle verification: `approved`

Always query current database data before testing.

## Latest relevant commits

- `709b9e141b4ca60be2fcc565bbd44a04fa9610e3` — admin driver and vehicle verification.
- `0db70c9712db9d43bf103bb8835ffff7d24852b8` — customer booking leader edge-case fix.
- `f47a28453ce71973a373770c1ad97cc00bbdb975` — admin booking and assignment routes/features.
- `3a2bebef714d71c249e0886ba2c69bf6a1b4549c` — accepted assignment required for ongoing/completed.
- `57bdb505a8c95a9cd8b019345c17a65ad3838d11` — paid booking required before assignment and trip progress.
- `8917bbfa453cfe6a53028c31fe16db0acb88f170` — initial project handoff guide.
- `e9d1cc61dea6e1bf0c62db0f2878ca4b4de5a68f` — required response structure clarified.

Always check `git log`; newer commits may exist. Add the latest functional commit here whenever progress changes.

## Next progress list

### Priority 1 — Payment workflow

Highest priority because payment guards exist but payment still requires manual SQL.

Recommended scope:

- customer submits payment/upload proof
- payment or invoice record
- customer payment status/history
- admin pending-payment list/detail
- admin approve/reject with rejection reason
- approval updates booking payment status to `paid`
- legal payment-state transitions
- tests for assignment-before-payment rejection

### Priority 2 — Driver assignment response API

- driver assignment list/detail
- accept assignment
- reject with required reason
- only assigned driver may respond
- prevent double responses
- set `responded_at`
- define availability behavior after acceptance/rejection
- tests for offered versus accepted booking progression

### Priority 3 — Driver dashboard/API

- profile and verification status
- update allowed profile fields
- rejected document status/reason
- re-upload rejected documents
- availability toggle with rules
- list/manage driver-owned vehicles
- vehicle verification status

### Priority 4 — Booking state-machine hardening

Recommended transitions:

```text
pending → confirmed | cancelled
confirmed → ongoing | cancelled
ongoing → completed
completed → final
cancelled → final
```

Add protection against direct pending → completed, backward transitions, and invalid cancellation.

### Priority 5 — Travel groups and participant allocation

- driver-origin and website-origin groups
- leader/member management
- attach bookings/participants
- flexible allocation to vehicles
- capacity enforcement

### Priority 6 — Points and withdrawal

- driver point ledger
- award after completed trips
- available and held balances
- withdrawal request
- admin approval/rejection/processing
- release or deduct held balance

### Priority 7 — Admin operations and production hardening

- customer management
- dashboard and reports
- notifications and queues
- audit logs
- policies and authorization refinement
- rate limiting
- API documentation
- comprehensive automated tests
- backup and deployment
- Flutter and Laravel frontend integration

## Recommended immediate continuation

Continue with:

```text
Payment workflow
→ Driver assignment response API
→ Driver dashboard
→ Booking state-machine hardening
```

This creates an end-to-end flow without manual SQL:

```text
customer creates booking
→ customer submits payment
→ admin verifies payment
→ admin assigns driver and vehicle
→ driver accepts
→ trip starts
→ trip completes
```

## Verification expectations

Future agents should test or provide tests for:

- authentication and role restrictions
- resource ownership
- validation failures
- payment-required assignment guard
- accepted-assignment-required trip guard
- final-state protection
- driver/vehicle schedule conflicts
- customer booking isolation
- repeated assignment responses
- invalid booking/payment transitions

When runtime access is unavailable, state that tests were not executed and instruct the user to run:

```powershell
php artisan optimize:clear
php artisan migrate
php artisan route:list
php artisan test
```
