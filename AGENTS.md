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

## Required response structure

After every backend change, respond in this exact order:

1. **Changes**
2. **Endpoint changes**
3. **Cara pull changes**
4. **cURL Postman**
5. **Expected result cURL**

Use Indonesian, include ready-to-run PowerShell, importable cURL, expected HTTP status/JSON, migration requirements, test status, and latest commit SHA.

## Product decisions

- Actors: admin, driver, customer.
- Driver and vehicle registration start pending/unavailable.
- Admin verifies drivers and driver-owned vehicles.
- Admin offers assignments; drivers accept or reject them.
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
- Driver/vehicle schedule conflict checks.
- Assignment starts as `offered`; admin can cancel it.
- Assignment requires `booking.payment_status = paid`.
- `ongoing` and `completed` require payment `paid` and an accepted assignment.

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

## New payment endpoints

```text
GET   /api/v1/customer/payments
POST  /api/v1/customer/bookings/{booking}/payments
GET   /api/v1/customer/payments/{payment}
GET   /api/v1/admin/payments
GET   /api/v1/admin/payments/{payment}
PATCH /api/v1/admin/payments/{payment}/verification
```

All endpoints require Sanctum and their respective `customer` or `admin` role.

## Latest relevant commits

- `6c15b591c5fe3f46efc42cd6910d56634e4ded62` — expose customer/admin payment routes.
- `0295e0d910f96dd5a0860dfc17bae667d7a9351d` — admin payment verification.
- `71753163f7c3b6e3055572f8f46d40d604a4eb51` — customer payment submission/history.
- `bfa4cfdfc02141e32cce14a07f4c51286b8e3fcf` — payments migration.
- `57bdb505a8c95a9cd8b019345c17a65ad3838d11` — paid booking guards.

## Verification status and limitations

- Runtime tests were not executed in this environment because the repository could not be cloned and no Laravel runtime/database was available.
- Run locally after pulling:

```powershell
php artisan optimize:clear
php artisan migrate
php artisan storage:link
php artisan route:list --path=api/v1
php artisan test
```

- Verify MySQL supports the migration and that `storage/app/public` is writable.
- Automated feature tests specifically for payment submission/verification remain to be added if not already covered locally.

## Next progress list

### Priority 1 — Driver assignment response API

- driver assignment list/detail
- accept assignment
- reject with required reason
- only assigned driver may respond
- prevent repeated responses
- set `responded_at`
- define availability behavior

### Priority 2 — Driver dashboard/API

- profile and verification status
- update allowed fields
- rejected document reasons and re-upload
- availability toggle with rules
- manage driver-owned vehicles

### Priority 3 — Booking state-machine hardening

```text
pending → confirmed | cancelled
confirmed → ongoing | cancelled
ongoing → completed
completed/cancelled → final
```

Prevent direct skips, backward transitions, and illegal cancellation.

### Priority 4 — Travel groups and participant allocation

- driver-origin and website-origin groups
- leader/member management
- participant-to-vehicle allocation
- capacity enforcement

### Priority 5 — Points, withdrawal, and production hardening

- point ledger and trip awards
- held/available balances and withdrawal processing
- admin reports, notifications, queues, audit logs, rate limiting, API docs, backup, deployment, and client integration

## Recommended immediate continuation

```text
Driver assignment response API
→ Driver dashboard
→ Booking state-machine hardening
```
