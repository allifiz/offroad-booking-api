# Postman End-to-End Manual Verification

Last verified: 2026-07-21 (Asia/Jakarta)  
Branch: `main`  
Test source: `docs/POSTMAN_END_TO_END_TEST.md`

## Verification status

**MANUAL POSTMAN END-TO-END FLOW: PASSED ✅**

The complete flow documented in `docs/POSTMAN_END_TO_END_TEST.md` has been executed manually against the current Laravel backend and a real local MySQL/MariaDB database.

All documented requests completed successfully without backend errors, and the resulting records remained consistently connected from the beginning to the end of the flow.

## Verified flow

The following connected scenario was executed successfully:

1. API health check.
2. Seeded admin login.
3. Active tour package creation.
4. New customer registration.
5. Tour package retrieval.
6. Booking creation with the same customer and package.
7. Booking detail retrieval.
8. Payment proof upload.
9. Admin payment review.
10. Payment approval.
11. Booking confirmation.
12. New driver registration with profile photo, driver documents, vehicle document, and vehicle photo.
13. Admin retrieval of the same driver profile.
14. Driver document verification.
15. Driver profile verification.
16. Driver vehicle and vehicle-document verification.
17. Driver login.
18. Driver availability update.
19. Admin assignment offer using the same booking, driver, and vehicle.
20. Driver assignment retrieval.
21. Driver assignment acceptance.
22. Booking transition to ongoing.
23. Booking transition to completed.
24. Completion reward ledger creation.
25. Driver point summary and ledger retrieval.
26. Withdrawal request using the points produced by the completed trip.
27. Admin withdrawal review and status transitions.
28. Final customer, driver, booking, payment, assignment, point, and withdrawal state verification.
29. Token logout flows.

## Data consistency result

The test used one continuous dataset rather than unrelated example records. IDs and tokens returned by each response were reused in later requests.

Verified relationships included:

```text
customer -> booking -> participants
booking -> tour package
booking -> payment -> admin reviewer
booking -> driver assignment -> driver user
assignment -> driver profile -> driver vehicle
completed booking -> point ledger -> driver balance
point balance -> withdrawal -> admin processor
```

This confirms that the current API flow is operational and that its persisted database relationships behave as expected for the tested happy path.

## Point and reward exception

The point mechanism was technically verified as working according to the current implementation:

```text
accepted assignment
-> completed booking
-> completion reward credited
-> point ledger created
-> points usable for withdrawal
```

However, **the product policy behind the point values is not approved as final**.

The following values and UX behavior remain temporary MVP decisions:

```text
100 points per completed trip
Rp1,000 per point
100-point minimum withdrawal
reward not displayed before driver accepts assignment
```

Therefore:

- the point flow is marked **technically passed**;
- the point policy is marked **pending product decision**;
- passing this E2E test must not be interpreted as approval of the current reward amount, conversion rate, minimum withdrawal, or assignment reward disclosure behavior;
- agents must read `docs/POINT_REWARD_DECISION_PENDING.md` before modifying assignment, reward, ledger, or withdrawal behavior.

## Scope and limitations

This checkpoint confirms the manually tested happy path against the current local backend and database state. It does not replace:

- automated CI;
- concurrency tests;
- negative and abuse-case testing;
- staging verification;
- production infrastructure testing;
- final point-policy approval.

## Agent instruction

When evaluating project readiness, agents may state:

```text
Automated backend CI: green
Manual Postman E2E happy path: passed
Database relationship continuity: verified
Point implementation: technically passed
Point product policy: pending decision
```

Agents must not state that the point policy is final until `docs/POINT_REWARD_DECISION_PENDING.md` is superseded by an approved decision document.
