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
5. Never expose real access tokens.
6. Never claim tests passed unless they were executed.
7. Update this file with every backend/project change.
8. cURL delivery must always be a complete test flow from prerequisites to verification. Include all role logins, prerequisite creation/approval/payment/assignment steps, the main action, success verification, and important failure regressions. Do not provide an isolated endpoint when it cannot be tested without earlier state.

## Required response structure

After every backend change, respond in this exact order:

1. **Changes**
2. **Endpoint changes**
3. **Cara pull changes**
4. **cURL Postman**
5. **Expected result cURL**

Use Indonesian, include ready-to-run PowerShell, importable full-flow cURL, expected HTTP status/JSON, migration requirements, test status, and latest commit SHA.

## Product decisions

- Actors: admin, driver, customer.
- Driver and vehicle registration start pending/unavailable.
- Admin verifies drivers and driver-owned vehicles.
- Admin offers assignments; drivers accept or reject them.
- Driver/vehicle availability does not automatically change when an assignment is accepted.
- Availability indicates willingness/readiness to receive work; schedule conflicts are determined by accepted assignments on the same tour date.
- Multiple offers may exist for the same driver/date, but only one conflicting assignment may be accepted.
- Assignment creation requires a paid booking.
- Booking cannot become ongoing/completed without paid payment and an accepted assignment.
- Participant allocation per vehicle remains flexible and controlled by admin.
- Travel groups can originate from a driver or website.
- Driver points use a ledger; withdrawal holds balance while pending.

## Implemented progress

### Foundation and authentication

- Laravel 13 + MySQL/MariaDB.
- Health endpoint.
- Sanctum login/current-user/logout.
- Role middleware for admin, driver, and customer.

### Master data

- Public tour-package list/detail.
- Admin tour-package CRUD.
- Admin vehicle CRUD.

### Driver registration and verification

- Driver profile, documents, vehicles, vehicle documents, and photos.
- Multipart driver registration.
- New drivers and vehicles default pending/unavailable.
- Admin driver list/detail/approve/reject.
- Admin driver-vehicle approve/reject.
- Rejection reason required when rejected.

### Driver dashboard/API

- Driver can view own user/profile data, verification status, rejection reason, documents, vehicles, vehicle documents, and photos.
- Driver can update allowed profile fields: name, phone, address, and date of birth.
- Driver can manually switch between `available` and `unavailable`.
- Suspended drivers cannot change availability.
- Only approved drivers may switch to `available`; pending/rejected drivers may remain or switch to `unavailable`.
- Availability toggles do not cancel or modify accepted assignments.
- Driver can list owned vehicles and view owned vehicle details.
- Vehicle ownership isolation returns `404` for another driver's vehicle.
- Re-upload of rejected driver/vehicle documents is not yet implemented.

### Customer and booking

- Customer registration with Sanctum token.
- Customer profile show/update.
- Customer booking creation, participants, history, filters, and detail.
- Server-side booking code, participant count, and total calculation.
- Active package, future date, participant limits, and single-leader validation.
- Customer booking ownership isolation.

### Admin booking and assignment

- Admin booking list/detail/filter/status update.
- Final booking states cannot be changed.
- Cancelling a booking cancels active assignments.
- Admin assigns approved/available driver and driver-owned vehicle.
- Assignment starts as `offered`; admin can cancel it.
- Assignment requires `booking.payment_status = paid`.
- `ongoing` and `completed` require payment `paid` and an accepted assignment.
- Admin offer creation no longer blocks another offer solely because the same driver/vehicle has an offered assignment on that date.

### Driver assignment response

- Driver can list/filter assignments assigned to their own user ID.
- Driver can view owned assignment detail with booking, package, participants, vehicle, customer, and offer metadata.
- Driver can accept only an owned `offered` assignment.
- Driver can reject only an owned `offered` assignment and must provide a rejection reason.
- Accepted/rejected responses set `responded_at`.
- Repeated response attempts are rejected.
- Other drivers receive `404` for inaccessible assignments.
- Acceptance revalidates driver and vehicle approval/availability.
- Acceptance rejects driver or vehicle conflicts against other `accepted` assignments on the same tour date.
- Accepting an assignment does not automatically change driver or vehicle availability.

### Payment workflow

- `payments` table stores booking, customer, amount, method, proof path, status, rejection reason, submission, and review metadata.
- Customer can list and view owned payments.
- Customer can upload JPG/JPEG/PNG/PDF proof up to 5 MB for an owned non-final unpaid/failed booking.
- Payment proof is stored on the `public` disk under `payment-proofs`.
- Duplicate pending submissions are rejected.
- Submission creates a pending payment and changes booking payment status to `pending` transactionally.
- Admin can list/filter and inspect payments.
- Admin can approve or reject only pending payments.
- Rejection requires a reason.
- Approval changes payment and booking payment status to `paid` transactionally.
- Rejection changes payment and booking payment status to `failed` transactionally, allowing customer resubmission.

## Current expected end-to-end flow

```text
customer creates booking
→ customer uploads payment proof
→ booking/payment pending
→ admin approves payment
→ booking paid
→ admin assigns driver and vehicle
→ assignment offered
→ driver accepts
→ booking ongoing
→ booking completed
```

## Current relevant endpoints

```text
GET   /api/v1/driver/profile
PATCH /api/v1/driver/profile
PATCH /api/v1/driver/availability
GET   /api/v1/driver/vehicles
GET   /api/v1/driver/vehicles/{vehicle}
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

All endpoints require Sanctum and their respective role.

## Latest relevant commits

- `579544fc19180903597b77da2137c76ee6890206` — expose driver dashboard routes.
- `cdf146bab2bb5a8f98fd8e711d0b45172e97dddc` — driver profile, availability, and owned-vehicle dashboard API.
- `a9acd8a5be926042ee565eda69b289e2e701a782` — expose driver assignment response routes.
- `132d4d315047909e8d98d8d976df04fe2817daa2` — defer schedule conflicts until assignment acceptance.
- `6ea879c5aa9c424937b72f83d63be2f893c3ea80` — driver assignment list/detail/accept/reject.
- `6c15b591c5fe3f46efc42cd6910d56634e4ded62` — expose customer/admin payment routes.
- `0295e0d910f96dd5a0860dfc17bae667d7a9351d` — admin payment verification.
- `71753163f7c3b6e3055572f8f46d40d604a4eb51` — customer payment submission/history.
- `bfa4cfdfc02141e32cce14a07f4c51286b8e3fcf` — payments migration.

## Verification status and limitations

- Runtime tests were not executed in this environment because no local Laravel runtime/database was available.
- No migration was added for driver dashboard/API.
- Automated feature tests specifically for driver dashboard remain to be added if not already covered locally.
- Run locally after pulling:

```powershell
php artisan optimize:clear
php artisan route:list --path=api/v1/driver
php artisan test
```

## Next progress list

### Priority 1 — Re-upload rejected documents

- driver document ownership and rejected-only validation
- replace rejected driver document file and reset verification to pending
- vehicle document ownership and rejected-only validation
- replace rejected vehicle document file and reset verification to pending
- preserve review history or explicitly define replacement behavior

### Priority 2 — Booking state-machine hardening

```text
pending → confirmed | cancelled
confirmed → ongoing | cancelled
ongoing → completed
completed/cancelled → final
```

Prevent direct skips, backward transitions, and illegal cancellation.

### Priority 3 — Travel groups and participant allocation

- driver-origin and website-origin groups
- leader/member management
- participant-to-vehicle allocation
- capacity enforcement

### Priority 4 — Points, withdrawal, and production hardening

- point ledger and trip awards
- held/available balances and withdrawal processing
- admin reports, notifications, queues, audit logs, rate limiting, API docs, backup, deployment, and client integration

## Recommended immediate continuation

```text
Rejected document re-upload
→ Booking state-machine hardening
→ Travel groups and participant allocation
```
