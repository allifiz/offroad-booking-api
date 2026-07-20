# Offroad Booking — Project Progress Checkpoint

Last updated: 2026-07-20 (Asia/Jakarta)
Branch: `main`
Repository: `allifiz/offroad-booking-api`
Local path: `C:\Projects\offroad-booking-api`

## Current status

- Backend core MVP: approximately 99%.
- Backend production readiness: approximately 96%.
- Laravel admin web: dashboard, payment verification, and booking operations implemented.

Backend includes complete booking/payment/assignment/allocation/reward/withdrawal APIs, audit logs, notifications, rate limiting, queue hardening, reporting, CSV exports, health checks, deployment, backup, and recovery tooling.

## Laravel admin web

Authentication/dashboard:

```text
GET  /admin/login
POST /admin/login
GET  /admin
POST /admin/logout
```

Payment operations:

```text
GET   /admin/payments
GET   /admin/payments/{payment}
PATCH /admin/payments/{payment}
```

Booking operations:

```text
GET   /admin/bookings
GET   /admin/bookings/{booking}
PATCH /admin/bookings/{booking}/status
POST  /admin/bookings/{booking}/assignments
PATCH /admin/bookings/{booking}/assignments/{assignment}/cancel
```

Booking web features:

- status/payment filters and booking/customer search
- detail with customer, package, participants, and assignments
- safe transitions pending→confirmed/cancelled and confirmed→ongoing/cancelled
- paid-booking requirement for confirmation and assignment
- accepted-assignment requirement before ongoing
- approved/available driver and approved/available driver-owned vehicle validation
- assignment offer and cancellation
- completion intentionally remains on API until reward logic is centralized in a shared service

Files:

```text
app/Http/Controllers/Web/Admin/BookingController.php
resources/views/admin/bookings/index.blade.php
resources/views/admin/bookings/show.blade.php
tests/Feature/AdminWebBookingFlowTest.php
```

## Autonomous CI

Workflow: `.github/workflows/backend-tests.yml`.

CI was confirmed green immediately before booking web changes. Do not claim `AdminWebBookingFlowTest` passes until the newest workflow result is confirmed.

## Next recommended work

1. Inspect and fix any booking web CI failure.
2. Extract shared booking transition/reward service and enable safe web completion.
3. Implement participant allocation and driver/vehicle verification pages.
4. Implement withdrawal, reports, and audit pages.
5. Complete OpenAPI and start customer web/Flutter driver integration.

## Response format rule

After backend changes respond in this exact order:

1. **Changes**
2. **Endpoint changes**
3. **Cara pull changes**
4. **cURL Postman**
5. **Expected result cURL**
