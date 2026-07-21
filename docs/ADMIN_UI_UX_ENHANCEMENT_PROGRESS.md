# Admin UI/UX Enhancement Progress

Last updated: 2026-07-21 (Asia/Jakarta)  
Branch: `main`  
Repository: `allifiz/offroad-booking-api`

## Status summary

| Area | Status |
|---|---|
| Backend business flow | Completed and manually verified |
| Current Admin Web functionality | Completed |
| Current Admin Web visual quality | Good baseline |
| Admin UX clarity | Needs enhancement |
| Full Admin UI redesign | Planned |
| Target Figma design review | Blocked by Figma access |
| Implementation against final Figma | Not started |

The current Admin Web is functionally complete, but the next Admin Web phase is a full UI/UX enhancement. This work is separate from backend MVP completion and must be tracked independently.

## Target Figma

Proposed full Admin UI reference:

```text
https://www.figma.com/design/GuPVPzUgzd4u5qn0T8ZGJW/Admin-WOG?node-id=1627-37733&t=RlmsbT2ZSGrSEGjX-1
```

Figma identifiers:

```text
file key: GuPVPzUgzd4u5qn0T8ZGJW
node id: 1627:37733
```

Current review status:

- The target file/node was requested through the Figma connector.
- The connector could not retrieve design context because the connected Figma account does not have editor access to the file.
- The design must therefore be treated as a proposed target, not yet as an approved implementation specification.
- Before implementation starts, grant the connected Figma account editor access or provide an accessible duplicate/file.
- Once access is available, inspect exact layout, components, breakpoints, tokens, modal patterns, empty states, tables, filters, forms, icons, and responsive behavior before changing Blade views.

## Feasibility

A full redesign to match the referenced Figma is expected to be feasible with the existing stack:

```text
Laravel Blade
Tailwind CSS
Alpine.js / lightweight JavaScript
Vite
Existing session-based Admin Web routes
```

The design should be adapted to Blade and existing project conventions rather than copying generated React code from Figma.

Expected implementation profile:

- Most work: Blade, Tailwind, Alpine.js, layout components, modal components, interaction states, responsive behavior, and UX copy.
- Some work: Web Admin controllers and queries to prepare better view models, valid action lists, counts, and dependent selections.
- Limited work: Web route additions for optional AJAX search or bulk actions.
- Core API/domain behavior should remain unchanged unless an approved UX requirement cannot be supported by current contracts.

## Design implementation rules

1. Preserve all existing domain rules in backend services and controllers.
2. Do not move authorization, lifecycle validation, financial validation, assignment ownership, reward, or ledger rules into JavaScript.
3. Modal forms must submit to canonical server-side actions.
4. Validation errors must reopen the correct modal and preserve old input.
5. Destructive and financial actions require explicit confirmation.
6. Show only actions valid for the resource's current state.
7. Prefer action-oriented Indonesian copy over raw enum values.
8. Retain full-page detail views for information-dense modules.
9. Use modal CRUD for short forms and contextual actions.
10. Preserve direct URLs and accessible non-JavaScript fallback where practical.
11. Do not hardcode pending point-policy values into the redesigned UI.
12. Final implementation must follow the accessible Figma design once access is available.

## Recommended modal strategy

### Suitable for modal-based interaction

- Create/edit tour package.
- Create/edit company vehicle.
- Customer suspend/reactivate.
- Payment approve/reject.
- Driver approve/reject.
- Driver document preview and verification.
- Driver vehicle verification.
- Withdrawal approve/reject/mark paid.
- Booking status transition.
- Driver assignment offer/cancel.
- Participant allocation editor.
- Create/edit travel group.
- Attach booking to travel group.
- Confirmation for destructive actions.

### Keep as full-page detail

- Booking detail.
- Driver detail and verification workspace.
- Customer detail.
- Travel group detail.
- Withdrawal detail if it includes financial audit context.
- Reports.
- Audit-log detail.

These detail pages may open action modals but should not themselves be replaced by oversized modal dialogs.

## Module progress

### 1. Global layout and navigation

Status: `planned`

Current UX risks:

- Eleven navigation items have equal visual weight.
- Operational, master-data, partner, financial, report, and audit modules are not grouped.
- Indonesian and English labels are mixed.
- There are no visible queue badges for pending work.
- Page headers, action locations, filters, empty states, and row actions are not fully standardized.

Planned enhancement:

- Rebuild global layout according to the final Figma.
- Group navigation into operational categories.
- Add pending queue badges where useful.
- Standardize breadcrumbs, page titles, descriptions, primary actions, filters, table actions, pagination, flash messages, validation states, and empty states.
- Create reusable Blade components for modal, drawer, badge, button, table, filter bar, confirmation, form field, and status timeline.
- Add responsive desktop/mobile navigation consistent with the Figma design.

Backend/Web adjustment:

- Shared pending counts may require a view composer or dedicated navigation data provider.
- No REST API changes expected.

Potential data sources/routes:

```text
GET /admin
GET /admin/payments?status=pending
GET /admin/drivers?verification_status=pending
GET /admin/withdrawals?status=pending
GET /admin/bookings
```

### 2. Dashboard

Status: `planned`

Current UX risks:

- Metrics may not directly communicate what needs action today.
- Queue shortcuts and operational priority may not be visually dominant enough.
- Users may need to navigate into several modules to discover pending work.

Planned enhancement:

- Adapt dashboard cards, charts, queues, recent activity, and shortcuts to the final Figma.
- Separate informational metrics from actionable queues.
- Make each actionable card lead to a filtered module view.
- Use consistent date and currency formatting.
- Add clear loading, empty, and zero states.

Backend/Web adjustment:

- Dashboard controller query/view model may need additional grouped counts or trend data required by Figma.
- Existing dashboard route can remain canonical.

Affected route/API:

```text
GET /admin
GET /api/v1/admin/dashboard
```

Reason:

- The Web route may need richer server-rendered data.
- The API endpoint is affected only if the same new dashboard data must also be exposed to another client; otherwise it should remain unchanged.

### 3. Tour packages

Status: `planned`

Current UX risks:

- Create/edit uses separate pages for a short master-data form.
- Slug and implementation details may be too prominent for operational users.
- Status and destructive actions are not strongly differentiated.
- Filter reset and row-action consistency need improvement.

Planned enhancement:

- Move create/edit into reusable modal forms.
- Keep slug under an advanced section or auto-generate it.
- Add clear active/inactive actions and delete/archive confirmation.
- Standardize search, status filters, reset button, badges, and row action menu.

Backend/Web adjustment:

- Existing resource routes should continue to work.
- Controller responses may return modal-friendly validation context or support partial rendering if chosen.
- No domain change expected.

Affected routes/API:

```text
GET    /admin/tour-packages
GET    /admin/tour-packages/create
POST   /admin/tour-packages
GET    /admin/tour-packages/{tourPackage}/edit
PUT    /admin/tour-packages/{tourPackage}
DELETE /admin/tour-packages/{tourPackage}

GET    /api/v1/admin/tour-packages
POST   /api/v1/admin/tour-packages
GET    /api/v1/admin/tour-packages/{tourPackage}
PUT    /api/v1/admin/tour-packages/{tourPackage}
DELETE /api/v1/admin/tour-packages/{tourPackage}
```

Reason:

- Web routes are directly used by modal forms.
- API endpoints need adjustment only if request/response behavior changes; visual conversion alone does not require it.

### 4. Company vehicles

Status: `planned`

Current UX risks:

- CRUD is page-based even though the form is suitable for modal interaction.
- Operational availability and verification/ownership concepts may be unclear.
- Destructive actions need stronger consequences and confirmation.

Planned enhancement:

- Modal create/edit.
- Clear status badges and action labels.
- Confirmation before delete or availability changes.
- Standardized row action menu and responsive table/card view.

Backend/Web adjustment:

- Existing Web resource routes remain usable.
- No core API/domain change expected.

Affected routes/API:

```text
GET    /admin/vehicles
GET    /admin/vehicles/create
POST   /admin/vehicles
GET    /admin/vehicles/{vehicle}/edit
PUT    /admin/vehicles/{vehicle}
DELETE /admin/vehicles/{vehicle}

GET    /api/v1/admin/vehicles
POST   /api/v1/admin/vehicles
GET    /api/v1/admin/vehicles/{vehicle}
PUT    /api/v1/admin/vehicles/{vehicle}
DELETE /api/v1/admin/vehicles/{vehicle}
```

### 5. Travel groups

Status: `planned`

Current UX risks:

- Relationship between travel group, booking, assignment, vehicle, and participant allocation is not obvious.
- Creating a group and attaching bookings are separate concepts without enough guidance.
- Admin can potentially select the wrong booking without rich context.

Planned enhancement:

- Create/edit group through modal.
- Attach booking through searchable selection modal.
- Display booking code, date, package, participant count, payment status, and booking status during selection.
- Add warnings for date/package mismatch where relevant.
- Keep group detail as a full workspace.

Backend/Web adjustment:

- Existing attach-booking action can remain.
- Controller may need richer candidate-booking data and server-side search.
- Add an AJAX candidate endpoint only if the dataset becomes too large for server-rendered options.

Affected routes/API:

```text
GET   /admin/travel-groups
GET   /admin/travel-groups/create
POST  /admin/travel-groups
GET   /admin/travel-groups/{travelGroup}
PATCH /admin/travel-groups/{travelGroup}/status
POST  /admin/travel-groups/{travelGroup}/bookings

GET  /api/v1/admin/travel-groups
POST /api/v1/admin/travel-groups
GET  /api/v1/admin/travel-groups/{travelGroup}
POST /api/v1/admin/travel-groups/{travelGroup}/bookings
```

Reason:

- Existing actions support the flow.
- New API work is required only if searchable candidates or bulk changes must be exposed as an API contract.

### 6. Customers

Status: `planned`

Current UX risks:

- Suspend/reactivate consequences may not be clear.
- Generic status forms expose implementation state rather than a user-oriented action.
- Admin needs context before disabling access.

Planned enhancement:

- Replace status dropdown with contextual actions: `Suspend customer` or `Aktifkan kembali`.
- Confirmation modal explains access/token effects.
- Show related booking/payment activity before confirmation.
- Add reason field only if product decides to persist suspension reasons.

Backend/Web adjustment:

- Existing status endpoint supports state changes.
- Persisted suspension reason would require a migration, model field, validation, audit update, and API/Web contract change; this is optional and not yet approved.

Affected routes/API:

```text
GET   /admin/customers
GET   /admin/customers/{customer}
PATCH /admin/customers/{customer}/status
```

Potential API impact if customer-status behavior changes:

```text
GET   /api/v1/admin/customers
GET   /api/v1/admin/customers/{customer}
PATCH /api/v1/admin/customers/{customer}/status
```

Note: confirm current API availability before adding or documenting customer-admin API endpoints.

### 7. Bookings

Status: `highest priority / planned`

Current UX risks:

- Status, assignment, cancellation, participant allocation, payment context, and trip context are combined without a guided sequence.
- Raw booking enum choices can include invalid transitions.
- Driver and vehicle are selected independently, so mismatched ownership is possible before server validation.
- Capacity, schedule conflict, availability, and verification context are not sufficiently visible.
- Participant allocation requires repetitive per-participant submits.
- Destructive actions need clearer consequences.

Planned enhancement:

- Replace generic status select with valid next-action buttons.
- Use contextual modals for confirm, start, complete, and cancel.
- Assignment modal shows eligible drivers and only their eligible vehicles.
- Display verification, availability, capacity, tour date, and schedule-conflict context.
- Use a single participant-allocation modal with full capacity overview and one final save action.
- Confirmation modal for assignment cancellation.
- Preserve point-policy warning: do not display a fixed reward until product policy is approved.

Backend/Web adjustment:

- Web controller should provide valid transitions from the canonical lifecycle rules.
- Web controller/view model should provide eligible driver-vehicle pairs.
- Bulk participant allocation may need a new or expanded server action because the current Web action processes one participant allocation payload at a time.
- Business validation remains canonical in backend services.

Affected routes/API:

```text
GET   /admin/bookings
GET   /admin/bookings/{booking}
PATCH /admin/bookings/{booking}/status
POST  /admin/bookings/{booking}/assignments
PATCH /admin/bookings/{booking}/assignments/{assignment}/cancel
PUT   /admin/bookings/{booking}/participant-allocations

GET   /api/v1/admin/bookings
GET   /api/v1/admin/bookings/{booking}
PATCH /api/v1/admin/bookings/{booking}/status
POST  /api/v1/admin/bookings/{booking}/driver-assignments
PATCH /api/v1/admin/bookings/{booking}/driver-assignments/{driverAssignment}/cancel
GET   /api/v1/admin/bookings/{booking}/participant-allocations
PUT   /api/v1/admin/bookings/{booking}/participant-allocations
```

Reason:

- Existing endpoints support individual actions.
- A bulk allocation UX may require request-schema expansion or a new endpoint.
- Assignment responses may need richer eligibility data only if server-side dependent search is required.
- Any reward information in assignment UI is blocked by `docs/POINT_REWARD_DECISION_PENDING.md`.

### 8. Payments

Status: `high priority / planned`

Current UX risks:

- Payment proof opens in another tab.
- Approve and reject are presented through one generic select.
- Rejection reason appears even when approving.
- Financial consequences are not emphasized strongly enough.

Planned enhancement:

- Preview image/PDF in modal or drawer.
- Separate `Setujui pembayaran` and `Tolak pembayaran` actions.
- Show booking total versus submitted amount.
- Show rejection reason only in reject modal.
- After processing, optionally navigate to the next pending payment.

Backend/Web adjustment:

- Existing PATCH action supports approve/reject.
- Controller may prepare `next_pending_payment_id` or filtered redirect behavior.
- Secure inline file preview may need a protected file route only if public storage URLs are not acceptable.

Affected routes/API:

```text
GET   /admin/payments
GET   /admin/payments/{payment}
PATCH /admin/payments/{payment}

GET   /api/v1/admin/payments
GET   /api/v1/admin/payments/{payment}
PATCH /api/v1/admin/payments/{payment}/verification
```

### 9. Driver verification

Status: `high priority / planned`

Current UX risks:

- Driver profile, documents, vehicles, and final decision are mixed together.
- Approval hierarchy is not obvious.
- Document preview is separate from verification actions.
- Raw `approved/rejected` enum labels are exposed.
- Rejection reason fields are visible even when not needed.

Planned enhancement:

- Use tabs or stepper: Profile, Documents, Vehicles, Decision.
- Preview documents in modal/drawer.
- Add explicit verification actions for each document and vehicle.
- Show checklist of unresolved items before final driver approval.
- Separate approve and reject modals with contextual copy.

Backend/Web adjustment:

- Current Web routes only expose profile and vehicle decisions; document verification must be confirmed and may require additional Web routes/controllers even though API document-verification endpoints already exist.
- Controller should supply approval readiness and unresolved-item counts.

Affected routes/API:

```text
GET   /admin/drivers
GET   /admin/drivers/{driverProfile}
PATCH /admin/drivers/{driverProfile}
PATCH /admin/drivers/{driverProfile}/vehicles/{vehicle}
```

Existing API endpoints that may need Web equivalents or shared behavior:

```text
PATCH /api/v1/admin/drivers/{driverProfile}/verification
PATCH /api/v1/admin/driver-vehicles/{vehicle}/verification
PATCH /api/v1/admin/driver-documents/{driverDocument}/verification
PATCH /api/v1/admin/driver-vehicles/{vehicle}/documents/{vehicleDocument}/verification
```

Reason:

- The redesigned verification workspace should not imply that profile approval automatically approves documents or vehicles.
- Web actions may need to reuse the same canonical verification logic as API controllers.

### 10. Withdrawals

Status: `high priority / planned`

Current UX risks:

- The UI displays approved, rejected, and paid together even when not all transitions are valid.
- `Simpan status` is too generic for a financial action.
- Marking paid lacks a strong confirmation step.
- Transfer reference is not currently stored.

Planned enhancement:

- Pending: separate approve and reject actions.
- Approved: show only `Tandai sudah dibayar`.
- Rejected/paid: read-only state.
- Confirmation modal shows driver, bank, account, points, and amount.
- Add transfer reference/date only after explicit product approval.

Backend/Web adjustment:

- Existing transition endpoint supports current state changes.
- Transfer reference/date requires schema, migration, validation, audit, report, API, and Web changes; not part of visual-only enhancement.

Affected routes/API:

```text
GET   /admin/withdrawals
GET   /admin/withdrawals/{withdrawal}
PATCH /admin/withdrawals/{withdrawal}

GET   /api/v1/admin/withdrawals
GET   /api/v1/admin/withdrawals/{withdrawal}
PATCH /api/v1/admin/withdrawals/{withdrawal}
```

Related driver endpoints:

```text
GET  /api/v1/driver/withdrawals
POST /api/v1/driver/withdrawals
GET  /api/v1/driver/points/summary
GET  /api/v1/driver/points/ledger
```

Point/conversion changes remain governed by `docs/POINT_REWARD_DECISION_PENDING.md`.

### 11. Reports

Status: `planned`

Current UX risks:

- Export actions may not clearly explain filters, date range, file contents, or generation state.
- Reports may feel disconnected from operational modules.

Planned enhancement:

- Match report cards/filter layout to Figma.
- Explain each export and current filter scope.
- Add clear empty, loading, download, and error feedback.
- Preserve canonical CSV generation implementation.

Backend/Web adjustment:

- Existing export routes remain canonical.
- Additional filters require synchronized Web/API validation and export query changes.

Affected routes/API:

```text
GET /admin/reports
GET /admin/reports/export/bookings
GET /admin/reports/export/payments
GET /admin/reports/export/drivers
GET /admin/reports/export/withdrawals

GET /api/v1/admin/reports/export/bookings
GET /api/v1/admin/reports/export/payments
GET /api/v1/admin/reports/export/drivers
GET /api/v1/admin/reports/export/withdrawals
```

### 12. Audit logs

Status: `planned`

Current UX risks:

- Before/after payloads may be difficult for operational users to interpret.
- Technical event names and identifiers may need human-readable labels.

Planned enhancement:

- Redesign index and detail according to Figma.
- Add readable event labels, actor/resource context, and structured before/after diff.
- Keep raw payload available under an advanced section.
- Add consistent filter and empty-state behavior.

Backend/Web adjustment:

- No endpoint change expected for visual formatting.
- New filters or enriched actor/resource labels may require query/view-model changes.

Affected routes/API:

```text
GET /admin/audit-logs
GET /admin/audit-logs/{auditLog}

GET /api/v1/admin/audit-logs
GET /api/v1/admin/audit-logs/{auditLog}
```

## Backend changes classification

### No core backend change expected

- Layout redesign.
- Navigation grouping.
- Modal open/close behavior.
- Button, table, badge, filter, pagination, and empty-state styling.
- Action-oriented labels.
- Showing only valid actions when data is already available.
- Confirmation dialogs.
- Public-storage image/document preview.
- Existing single-resource form submissions.

### Web controller/query adjustment likely

- Shared navigation queue counts.
- Dashboard-specific metrics required by Figma.
- Valid next booking transitions.
- Eligible driver and vehicle pair preparation.
- Candidate booking search for travel groups.
- Modal validation context and redirect behavior.
- Next pending payment shortcut.
- Driver verification readiness summary.
- Human-readable audit-log presentation.

### Endpoint or schema adjustment possible

- Bulk participant allocation.
- AJAX search endpoints for large datasets.
- Protected document preview/download.
- Persisted customer suspension reason.
- Withdrawal transfer reference and paid timestamp fields.
- New dashboard trend data exposed through API.
- Any reward estimate or point value shown before assignment acceptance.

Each possible backend/API change requires separate approval before implementation.

## Proposed implementation phases

### Phase 0 — Figma access and design audit

Status: `blocked`

- Obtain editor access to the target Figma file.
- Retrieve design context for the target node and related pages/components.
- Identify desktop/mobile breakpoints.
- Map Figma components and tokens to Blade/Tailwind components.
- Confirm which screens exist and which Admin modules need custom adaptation.
- Freeze the first implementation scope.

### Phase 1 — UI foundation

Status: `not started`

- Design tokens.
- Admin layout.
- Sidebar/topbar/mobile navigation.
- Modal and drawer system.
- Buttons, badges, forms, table, filter, pagination, alert, and empty-state components.
- Validation and flash-message behavior.

### Phase 2 — Operational high-priority modules

Status: `not started`

- Dashboard.
- Bookings.
- Payments.
- Driver verification.
- Withdrawals.

### Phase 3 — Master data and relationship modules

Status: `not started`

- Tour packages.
- Vehicles.
- Travel groups.
- Customers.

### Phase 4 — Monitoring modules

Status: `not started`

- Reports.
- Audit logs.

### Phase 5 — QA and acceptance

Status: `not started`

- Responsive checks.
- Keyboard and screen-reader interaction.
- Focus trapping and Escape behavior for modal/drawer.
- Validation-error reopening.
- Authorization checks.
- Destructive/financial confirmation checks.
- Existing backend feature tests.
- New Admin Web feature tests.
- Manual Admin Web acceptance against real local database.

## Completion criteria

The Admin UI/UX enhancement is complete only when:

1. Final accessible Figma screens are implemented or deviations are documented.
2. All current Admin Web functionality remains available.
3. All state transitions remain enforced server-side.
4. Modal validation behavior is reliable.
5. Responsive and keyboard behavior is verified.
6. No unauthorized action is exposed.
7. Existing backend CI remains green.
8. Relevant Admin Web tests are added and green.
9. Manual Admin acceptance flow passes.
10. Point-related UI does not present temporary policy as final.

## Current conclusion

The full redesign is technically possible with the current Laravel Blade stack. Most work is UI/UX implementation, with limited Web Controller/query adjustments. Core backend and REST API changes are not expected for the majority of the redesign. The exact implementation plan remains provisional until the Figma file can be inspected through an account with sufficient access.
