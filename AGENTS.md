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

## Agent operating rules

1. Read this file before changing backend behavior.
2. Apply requested backend changes directly to `main` unless the user explicitly asks for a branch or pull request.
3. Inspect existing models, enums, migrations, controllers, routes, and tests before adding new structures.
4. Preserve existing enum values and database relationships unless a migration is explicitly needed.
5. Do not invent company-owned vehicle flows. The current product decision is that operational vehicles belong to drivers.
6. Use `driver_id` to mean the driver's `users.id`. A vehicle belongs to the driver through `vehicles.driver_profile_id -> driver_profiles.id -> driver_profiles.user_id`.
7. After backend changes, always provide the response in this exact order:
   - **Changes**
   - **Endpoint changes**
   - **Cara pull changes**
   - **cURL Postman**
   - **Expected result cURL**
8. cURL examples must be importable through Postman → Import → Raw text.
9. Auth examples must use:

   ```text
   Authorization: Bearer YOUR_TOKEN
   ```

10. Pull instructions must include relevant commands such as `git pull`, `composer install`, `php artisan optimize:clear`, migrations, seeders, storage linking, route inspection, and tests when applicable.
11. Expected results must include HTTP status and representative JSON.
12. Never expose or repeat real access tokens found in user messages. Recommend revoking exposed tokens.
13. Keep responses in Indonesian with a casual but technically clear tone.

## Required response structure

This section is mandatory for every agent that completes a backend change. Do not replace the headings, change their order, or omit a section just because it is empty.

### 1. Changes

Explain exactly what was changed, including:

- files or backend areas affected
- business rules added or changed
- validation and authorization behavior
- migrations, seeders, tests, or documentation added
- commit SHA when the change was pushed to GitHub
- any limitation, assumption, or test that could not be executed

Do not merely say “done”. Describe the actual behavior now implemented.

### 2. Endpoint changes

List every new, changed, or removed endpoint using method and path, for example:

```text
POST  /api/v1/customer/bookings
PATCH /api/v1/admin/bookings/{booking}/status
```

Also state:

- required role
- whether Sanctum authentication is required
- important request fields
- important state or business-rule requirements

When no endpoint changed, explicitly write that there is no endpoint change.

### 3. Cara pull changes

Provide ready-to-run PowerShell commands for the user's Windows environment. Start with:

```powershell
cd C:\Projects\offroad-booking-api
git switch main
git pull origin main
```

Then include only relevant commands, such as:

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

Rules:

- mention clearly when there is no migration
- include migration or seeder commands when required
- include `storage:link` for upload/public-storage changes when relevant
- include route inspection for new routes
- include test commands
- never instruct the user to run destructive commands such as `migrate:fresh` unless explicitly necessary and clearly warned

### 4. cURL Postman

Provide complete cURL commands that can be pasted into:

```text
Postman → Import → Raw text
```

Every cURL must include:

- complete local URL, normally `http://127.0.0.1:8000`
- `Accept: application/json`
- `Content-Type: application/json` when sending JSON
- `Authorization: Bearer {{token_variable}}` for protected endpoints
- complete request body

Use placeholders or Postman variables such as:

```text
{{admin_token}}
{{customer_token}}
{{driver_token}}
```

Never repeat a real token supplied by the user. When a real token appears in chat, tell the user to revoke it and use a replacement.

Include cURL for:

- the normal success flow
- important validation failures
- authorization or ownership failures when relevant
- regression cases for the bug that was just fixed

Use actual IDs only when they are clearly described as local examples. Never hardcode local IDs into application code.

### 5. Expected result cURL

For every important cURL, show:

- expected HTTP status, for example `HTTP 201 Created`
- representative JSON response
- the key database or state change that should occur

Examples must match the actual response structure implemented by the controller. Do not fabricate fields that the API does not return.

For validation failures, show HTTP `422` and the expected `errors` object. For authentication failures, show HTTP `401`. For inaccessible resources hidden by ownership rules, show HTTP `404` when that is the implemented behavior.

### Additional answer behavior

- Be direct and practical; the user is actively testing through Postman and phpMyAdmin.
- When a bug is reported, first confirm the intended business rule, patch it, and provide a regression cURL.
- When the user's database state prevents testing, provide safe SQL to inspect or reset only the necessary records.
- Prefer transactions for multi-table reset queries.
- Distinguish `users.id`, `driver_profiles.id`, `vehicles.id`, `bookings.id`, and `driver_assignments.id` clearly.
- Never claim automated tests passed when they were not executed.
- After documenting a backend change, state the best next project priority only when useful; do not bury the required five response sections.

## Current product decisions

- Actors: admin, driver, customer.
- Driver registration and vehicle registration begin as `pending` and unavailable.
- Admin verifies drivers and driver-owned vehicles.
- Vehicles used operationally are driver-owned; company vehicles are not part of the intended flow.
- Assignment is offered by admin and must be accepted or rejected by the driver.
- A booking cannot be assigned before payment is complete.
- A booking cannot start or complete before payment is complete and a driver assignment is accepted.
- Driver points will use a ledger.
- Withdrawal will hold balance while pending.
- Participant distribution per vehicle remains flexible and is decided by admin.
- Travel groups can originate from a driver or from the website.

## Implemented progress

### Foundation

- Laravel 13 project initialized.
- MySQL/MariaDB configured.
- API health endpoint available.
- Laravel Sanctum authentication implemented.
- Login, current user, and logout endpoints implemented.
- Role middleware implemented for admin, driver, and customer access.

### Master data

- Public tour-package listing and detail endpoints implemented.
- Admin tour-package CRUD implemented.
- Admin vehicle CRUD implemented.

### Driver registration and verification

- Driver profile structure implemented.
- Driver document structure implemented.
- Driver vehicle structure implemented.
- Driver vehicle document and photo structures implemented.
- Multipart driver registration implemented.
- New driver defaults to `verification_status = pending` and `status = unavailable`.
- New driver vehicle defaults to `verification_status = pending` and `status = unavailable`.
- Admin driver list, detail, approve, and reject endpoints implemented.
- Admin driver-vehicle approve and reject endpoint implemented.
- Rejection reason is required when rejecting.

### Customer and booking

- Customer registration with automatic Sanctum token implemented.
- Customer profile show and update implemented.
- Customer booking creation implemented.
- Booking participants implemented.
- Booking code generation implemented.
- Participant count and total price are calculated server-side.
- Active-package validation implemented.
- Future-date validation implemented.
- Minimum and maximum participant validation implemented.
- Only one group leader is allowed; the first participant becomes leader when none is selected.
- Customer booking history, filtering, and detail implemented.
- Customers can only access their own bookings.

### Admin booking operations

- Admin booking list and detail implemented.
- Filters implemented for booking status, payment status, tour date, booking code, and customer identity.
- Admin booking status update implemented.
- Final booking states (`completed`, `cancelled`) cannot be changed.
- Cancelling a booking cancels active offered or accepted assignments.

### Driver assignment

- Admin can assign a driver and driver-owned vehicle to a booking.
- Driver must be approved and available.
- Vehicle must be approved, available, and owned by the selected driver.
- Driver and vehicle schedule conflicts are checked on the booking tour date.
- Cancelled or completed bookings cannot receive assignments.
- Assignment begins with status `offered`.
- Admin can cancel an assignment.
- Assignment states are `offered`, `accepted`, `rejected`, and `cancelled`.

### Patched booking guards

The following guards are already implemented and must not be removed:

1. Driver assignment creation requires `booking.payment_status = paid`.
2. Booking status `ongoing` requires:
   - `payment_status = paid`
   - at least one assignment with `status = accepted`
3. Booking status `completed` requires:
   - `payment_status = paid`
   - at least one assignment with `status = accepted`
4. An `offered`, `rejected`, or `cancelled` assignment does not authorize `ongoing` or `completed`.

Expected normal booking flow:

```text
pending + unpaid
→ payment paid
→ admin assigns driver and vehicle
→ assignment offered
→ driver accepts assignment
→ booking confirmed/ongoing
→ booking completed
```

## Known local test data from the latest working session

This data is only a local example and must not be hardcoded:

- Driver profile ID: `1`
- Driver user ID: `12`
- Driver name: `Driver Andi`
- Driver status: `available`
- Driver verification: `approved`
- Vehicle ID: `1`
- Vehicle belongs to driver profile ID `1`
- Vehicle status: `available`
- Vehicle verification: `approved`

Always query current data instead of assuming these IDs still exist.

## Latest relevant commits

- `709b9e141b4ca60be2fcc565bbd44a04fa9610e3` — merged admin driver and vehicle verification work.
- `0db70c9712db9d43bf103bb8835ffff7d24852b8` — customer booking leader edge-case fix.
- `f47a28453ce71973a373770c1ad97cc00bbdb975` — admin booking and assignment routes/features.
- `3a2bebef714d71c249e0886ba2c69bf6a1b4549c` — require accepted assignment for ongoing/completed.
- `57bdb505a8c95a9cd8b019345c17a65ad3838d11` — require paid booking before assignment and trip progress.
- `8917bbfa453cfe6a53028c31fe16db0acb88f170` — add project agent handoff guide.

Check `git log` because newer commits may exist.

## Next progress list

### Priority 1 — Payment workflow

Implement the real payment flow so testing no longer requires manual SQL updates.

Recommended scope:

- customer submits payment or uploads transfer proof
- payment record/invoice structure if not already sufficient
- admin lists pending payment verification
- admin approves or rejects payment
- approved payment updates `bookings.payment_status` to `paid`
- rejected payment records a reason and leaves the booking unpaid or failed according to the agreed flow
- prevent illegal payment transitions
- expose payment history/status to customer
- add feature tests for assignment-before-payment rejection

This is the highest priority because assignment now depends on `payment_status = paid`, but there is no proper API yet to complete payment.

### Priority 2 — Driver assignment response API

Implement driver-side endpoints:

- list offered assignments for authenticated driver
- assignment detail
- accept assignment
- reject assignment with required rejection reason
- ensure only the assigned driver can respond
- prevent responding twice
- set `responded_at`
- define availability behavior after accept/reject
- add tests for booking progression with offered versus accepted assignment

This removes the current need to change assignment status through SQL.

### Priority 3 — Driver dashboard/API

- show driver profile and verification status
- update allowed profile fields
- show rejected documents and reasons
- re-upload rejected documents
- toggle availability with business-rule validation
- list and manage driver-owned vehicles
- show vehicle verification status

### Priority 4 — Booking state machine hardening

Add explicit allowed transitions instead of accepting any non-final status change.

Recommended transitions:

```text
pending → confirmed | cancelled
confirmed → ongoing | cancelled
ongoing → completed
completed → final
cancelled → final
```

Consider whether `confirmed` should also require payment `paid`. The current hard guard is mandatory for assignment, ongoing, and completed.

Also add protections such as:

- cannot complete directly from pending
- cannot move ongoing back to pending
- cannot cancel after completed
- optionally require tour date conditions before ongoing/completed

### Priority 5 — Travel groups and participant allocation

- groups created from driver and website sources
- leader/member management
- attach bookings or participants to groups
- flexible admin allocation of participants to vehicles
- enforce vehicle capacities

### Priority 6 — Points and withdrawal

- driver point ledger
- award points after completed trips
- available and held balances
- withdrawal request
- admin approve/reject/process
- release held points on rejection
- deduct held points on completion

### Priority 7 — Admin operations and production hardening

- customer management
- dashboard statistics
- reports
- notifications and queues
- audit logs
- policies/authorization refinement
- rate limiting
- API documentation
- comprehensive automated tests
- backups and deployment
- Flutter driver integration
- Laravel admin/customer frontend integration

## Recommended immediate continuation

Continue with **Payment workflow first**, then **Driver assignment response API**.

Reasoning:

1. The backend already blocks assignment before payment, but there is no legitimate endpoint to mark payment as paid.
2. The backend already blocks trip progress before assignment acceptance, but there is no driver endpoint to accept or reject.
3. Completing these two flows creates a fully testable end-to-end path without manual SQL:

```text
customer creates booking
→ customer pays/uploads proof
→ admin verifies payment
→ admin assigns driver and vehicle
→ driver accepts
→ admin/driver progresses trip
→ booking completes
```

## Verification expectations for future changes

At minimum, future agents should test or provide tests for:

- authentication and role restrictions
- ownership restrictions
- validation failure responses
- payment-required assignment guard
- accepted-assignment-required trip guard
- final-state protection
- schedule conflict checks
- customer booking isolation
- repeated accept/reject attempts
- invalid state transitions

When runtime access is unavailable, clearly state that tests were not executed and instruct the user to run:

```powershell
php artisan optimize:clear
php artisan migrate
php artisan route:list
php artisan test
```
