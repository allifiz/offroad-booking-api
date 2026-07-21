# Driver Point Reward — Pending Product Decision

Status: **PENDING PRODUCT DECISION**  
Last reviewed: 2026-07-21 (Asia/Jakarta)

## Important instruction for AI agents

The current driver point/reward behavior is an **MVP placeholder**, not a finalized commercial or product decision.

Do not assume that the current values, calculation method, award timing, or assignment response contract are permanent. Do not expand or redesign this behavior without an explicit product decision from the project owner.

## Current temporary behavior

The backend currently uses values from `config/offroad.php`:

```php
'points_per_completed_trip' => (int) env('OFFROAD_POINTS_PER_COMPLETED_TRIP', 100),
'rupiah_per_point' => (int) env('OFFROAD_RUPIAH_PER_POINT', 1000),
'minimum_withdrawal_points' => (int) env('OFFROAD_MINIMUM_WITHDRAWAL_POINTS', 100),
```

Default MVP assumptions:

```text
Completed trip reward      = 100 points
Nominal value per point    = Rp1.000
Minimum withdrawal         = 100 points
```

Although the values are environment-configurable, the **reward model itself is effectively hardcoded**:

- every accepted driver on a completed booking receives the same fixed point amount;
- points are awarded only when the booking transitions to `completed`;
- assignment offers do not expose estimated reward points or estimated rupiah value;
- the driver cannot see a reward estimate before accepting an assignment;
- there is no per-package, per-distance, per-duration, per-vehicle, per-participant, or dynamic reward calculation;
- there is no persisted reward quote/snapshot attached to an assignment.

## Why this remains pending

The project owner has not yet approved:

- whether points should be fixed or calculated dynamically;
- whether reward depends on package, route, distance, duration, vehicle, participants, or another factor;
- whether the driver should see the reward before accepting;
- whether the displayed amount is guaranteed or only estimated;
- when a reward becomes locked, earned, available, or reversible;
- the final rupiah conversion rate;
- the final minimum withdrawal threshold;
- whether administrators may override reward values;
- how cancellations, reassignment, partial completion, disputes, refunds, or multi-driver trips affect rewards.

Therefore, the current implementation is valid for MVP testing, but it must not be presented as the final incentive policy.

## Canonical implementation locations

Agents must inspect these locations before changing reward behavior:

```text
config/offroad.php
app/Services/BookingLifecycleService.php
app/Services/WithdrawalService.php
app/Http/Controllers/Api/V1/DriverPointController.php
app/Http/Controllers/Api/V1/DriverAssignmentController.php
app/Http/Controllers/Api/V1/Admin/DriverAssignmentController.php
docs/openapi.yaml
```

Relevant persistence may include:

```text
driver_profiles.available_points
driver_profiles.held_points
point_ledgers
withdrawals
driver_assignments
bookings
```

## Endpoints directly affected by a future reward-policy change

### Assignment creation and visibility

```text
POST /api/v1/admin/bookings/{booking}/driver-assignments
GET  /api/v1/driver/assignments
GET  /api/v1/driver/assignments/{driverAssignment}
PATCH /api/v1/driver/assignments/{driverAssignment}/accept
PATCH /api/v1/driver/assignments/{driverAssignment}/reject
```

Reason:

- If drivers must see points before accepting, assignment responses need reward fields.
- If the reward is guaranteed, the calculated value should likely be persisted as a snapshot when the offer is created.
- If the value can change after an offer, the API must distinguish `estimated`, `quoted`, `locked`, and `earned` rewards.
- Accept/reject behavior may need to record agreement to a reward quote.

### Booking lifecycle and reward earning

```text
PATCH /api/v1/admin/bookings/{booking}/status
GET   /api/v1/admin/bookings/{booking}
GET   /api/v1/customer/bookings/{booking}
```

Reason:

- Reward issuance currently occurs when a booking transitions to `completed`.
- Changing award timing or eligibility changes `BookingLifecycleService` and its idempotency rules.
- Booking detail responses may need reward state for operational traceability.
- Cancellation, refund, reassignment, or dispute rules may require reward reversal or adjustment ledgers.

### Driver balances and ledger

```text
GET /api/v1/driver/points/summary
GET /api/v1/driver/points/ledger
```

Reason:

- Any new reward type, pending balance, locked reward, adjustment, reversal, bonus, or expiry changes the balance and ledger contract.
- Flutter must not infer money values from points unless the API contract explicitly guarantees the conversion rate.

### Withdrawal lifecycle

```text
GET   /api/v1/driver/withdrawals
POST  /api/v1/driver/withdrawals
GET   /api/v1/admin/withdrawals
GET   /api/v1/admin/withdrawals/{withdrawal}
PATCH /api/v1/admin/withdrawals/{withdrawal}
```

Reason:

- A conversion-rate or minimum-threshold change affects request validation and displayed nominal amounts.
- New balance categories may alter which points can be withdrawn.
- Historical withdrawals must retain the rate and nominal amount applied at request time; they must not be recalculated from a later configuration value.

### Dashboard, reports, and audit output

```text
GET /api/v1/admin/dashboard
GET /api/v1/admin/reports/export/drivers
GET /api/v1/admin/reports/export/withdrawals
GET /api/v1/admin/audit-logs
GET /api/v1/admin/audit-logs/{auditLog}
```

Reason:

- Operational metrics and exports may need quoted, earned, held, reversed, paid, or expired reward values.
- Reward-policy changes are financially relevant and should remain auditable.

## Required contract work when the decision is finalized

A future implementation must consider all of the following together:

1. Document the approved reward formula and lifecycle.
2. Decide whether reward quotes are estimated or guaranteed.
3. Persist a reward snapshot where historical consistency is required.
4. Define assignment response fields such as:

```json
{
  "reward": {
    "status": "estimated",
    "points": 100,
    "rupiah_per_point": 1000,
    "nominal_amount": 100000,
    "awarded_when": "booking_completed"
  }
}
```

5. Define ledger types and reversal/adjustment behavior.
6. Define withdrawal conversion-rate snapshot behavior.
7. Update migrations/models/resources/services/controllers.
8. Update `docs/openapi.yaml` and Postman E2E documentation.
9. Add feature and MySQL concurrency tests.
10. Preserve idempotency so a completed booking cannot award the same reward twice.
11. Provide a migration/backfill strategy for existing assignments, ledgers, and withdrawals.
12. Coordinate Flutter UI wording so estimates are not shown as guaranteed earnings.

## Current Flutter guidance

Until a product decision is approved:

- Flutter may display the driver's current point balance and ledger from existing endpoints.
- Flutter should **not** display a promised reward on assignment cards because the backend does not currently provide one.
- Flutter should not duplicate `100 points`, `Rp1.000 per point`, or any reward formula as application constants.
- Flutter must use future reward fields from the API once the contract is approved.

## Decision status summary

```text
Current implementation: valid MVP placeholder
Product decision: pending
Reward shown before accept: not implemented
Fixed 100-point reward: not final policy
Rp1.000 conversion: not final policy
Minimum 100-point withdrawal: not final policy
Safe action for agents: preserve current behavior unless explicitly instructed
```
