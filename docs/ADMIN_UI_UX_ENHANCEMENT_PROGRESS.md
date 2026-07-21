# Admin UI/UX Enhancement Progress

Last updated: 2026-07-21 (Asia/Jakarta)  
Branch: `main`  
Repository: `allifiz/offroad-booking-api`

## Status summary

| Area | Status |
|---|---|
| Backend business flow | Completed and manually verified |
| Current Admin Web functionality | Completed |
| Current Admin UI baseline | Completed |
| Admin UX enhancement | Planned |
| Full visual redesign | Planned |
| Figma access | Available |
| Figma design review | Initial review completed |
| Implementation | Not started |

This track is independent from backend MVP completion. The backend workflow is already accepted and must remain the source of truth. The redesign exists to improve clarity, speed, consistency, and visual quality without redefining existing business behavior.

## Canonical implementation principle

```text
Figma = visual reference and interaction pattern
Existing backend = workflow and business-rule source of truth
```

Implementation must follow these rules:

1. Preserve current booking, payment, assignment, verification, withdrawal, audit, and point behavior.
2. When a Figma interaction conflicts with existing backend behavior, adapt the UX instead of changing the backend.
3. Do not add product behavior merely because it appears in Figma.
4. Reuse existing Web routes, controllers, services, authorization, validation, and database schema wherever possible.
5. Core domain services must remain unchanged unless a separate approved backend change explicitly requires otherwise.
6. JavaScript may improve interaction, but it must not become the authority for authorization or business validation.
7. Modal forms must still submit to canonical server-side actions.
8. Existing non-JavaScript URLs should remain usable as fallback where practical.
9. Point-related UI must respect `docs/POINT_REWARD_DECISION_PENDING.md`.

## Target Figma

Accessible design reference:

```text
https://www.figma.com/design/LsmerjTpUvpqRP8awPLg7q/Untitled?node-id=0-1&t=7aiBXytLVCxesQki-1
```

Figma identifiers:

```text
file key: LsmerjTpUvpqRP8awPLg7q
page root: 0:1
```

Reviewed frames:

```text
Dashboard       = 1:4414
Cari Driver     = 1:4508
Orderan         = 1:4968
Paket Offroad   = 1:5028
Detail Pesanan  = 1:5089
Pengaturan      = 1:5152
Data Mitra      = 1:5197
Login           = 1:5358
Success popup   = 1:5346
```

Design direction:

- light gray application background;
- white floating sidebar and topbar;
- rounded white content cards;
- soft shadows;
- compact data tables;
- colored status badges;
- contextual dashboard cards;
- modal/popup feedback;
- desktop-first layout that must be adapted responsively;
- Roboto-style visual hierarchy;
- restrained blue primary accent with semantic status colors.

The implementation does not need to be pixel-perfect. It must preserve the same visual language, hierarchy, component patterns, and overall user experience while remaining practical for Laravel Blade and existing project data.

## Feasibility and expected impact

The redesign is feasible with the existing stack:

```text
Laravel Blade
Tailwind CSS
Alpine.js or lightweight JavaScript
Vite
Existing session-based Admin Web routes
```

Expected implementation profile:

```text
85–90% Blade, Tailwind, components, modal behavior, and UX copy
10–15% Web Controller queries, view models, counts, filters, and eager loading
0% intended change to core workflow or domain services
```

The redesign must not require React, SPA conversion, WebSocket, Redis presence tracking, GPS tracking, or a new API version.

## Approved interpretation of ambiguous Figma features

| Figma concept | Approved safe interpretation |
|---|---|
| Driver Online | `availability_status = available` |
| Online/offline badges | Existing availability state, not real-time presence |
| Jadwal/calendar | Read-only projection of existing booking `tour_date` and trip status |
| Orderan Masuk | Existing bookings that require a valid admin action |
| Terima order | Existing valid booking status transition only |
| Cancel order | Existing cancellation flow and validation |
| Cari Driver | Existing eligible/approved/available driver data |
| Driver assignment | Existing assignment offer flow |
| Tambah Mitra | Hide, relabel, or map to an existing action; do not create a new admin-created driver flow |
| Table checkbox | Selection affordance only unless an existing bulk action already exists |
| Notification icon | Existing pending queue counts or link shortcuts |
| Success popup | Visual feedback after an existing successful server action |
| Calendar navigation | UI filtering only, no drag-and-drop rescheduling |
| Settings/profile screen | Implement only existing supported account actions unless separately approved |

Explicitly out of scope:

- real-time driver presence;
- driver heartbeat;
- location/GPS tracking;
- WebSocket updates;
- drag-and-drop rescheduling;
- bulk mutation endpoints;
- admin-created driver onboarding;
- new point/reward behavior;
- new booking lifecycle states;
- new payment or withdrawal rules.

## Global UI foundation

Status: `planned`

Planned components:

- Admin application shell;
- responsive sidebar;
- topbar;
- page header and breadcrumbs;
- primary/secondary/destructive buttons;
- status badges;
- modal and confirmation dialog;
- success/error popup;
- filter bar;
- search input;
- table shell;
- pagination shell;
- empty state;
- loading state;
- form field and error message;
- tabs and step indicators;
- compact statistic cards;
- queue badge;
- drawer for mobile actions where a modal is unsuitable.

Implementation constraints:

- use reusable Blade components and partials;
- retain CSRF and method spoofing;
- reopen the correct modal after validation errors;
- preserve `old()` input;
- destructive and financial actions require explicit confirmation;
- action labels should be written in Indonesian and action-oriented;
- raw enums should not be exposed when a clearer label is available;
- status colors must be consistent across all modules.

## Module plan

### 1. Login

Status: `planned`

Target:

- adopt the Figma split-layout visual style;
- retain existing email/password authentication;
- display validation and authentication errors clearly;
- preserve the existing login route and session behavior.

Routes:

```text
GET  /admin/login
POST /admin/login
```

Backend impact: none.

### 2. Global navigation and topbar

Status: `planned`

Target:

- adopt the Figma floating sidebar and topbar;
- group existing modules into understandable sections;
- use icons and active states consistently;
- show pending queue badges when useful;
- retain access to every existing Admin module even when the Figma has fewer menu items.

Recommended grouping:

```text
Operasional
- Dashboard
- Booking
- Pembayaran
- Travel Group

Mitra dan Armada
- Driver
- Kendaraan
- Withdrawal

Master Data
- Paket Wisata
- Customer

Monitoring
- Laporan
- Audit Log
```

Potential Web adjustment:

- shared counts for pending payments, driver verification, and withdrawals;
- implement through a view composer or shared navigation data provider.

REST API impact: none.

### 3. Dashboard

Status: `planned`

Target mapping from Figma:

- statistic cards use existing totals and aggregates;
- driver list uses existing driver profiles and availability;
- `Driver Online` means `availability_status = available`;
- calendar shows existing bookings/trips by `tour_date`;
- detail cards show trips relevant to the selected date;
- `Top Jalur` maps to tour-package performance using existing booking data;
- actionable cards link to filtered existing modules.

Existing route:

```text
GET /admin
```

Potential Web Controller adjustments:

- revenue aggregate;
- count of approved/active drivers;
- count of trips/bookings by period;
- available driver list;
- upcoming trip list;
- package ranking;
- pending queue counts.

These are read-only queries. Do not modify domain behavior.

API endpoint potentially observed but not required to change:

```text
GET /api/v1/admin/dashboard
```

Only update the API when a separate client explicitly requires the same new fields.

### 4. Tour packages

Status: `planned`

Target:

- table styled like the Figma package table;
- create and edit use modal forms;
- row actions use a compact action menu;
- status displayed as clear semantic badges;
- slug and technical fields moved to an advanced area;
- delete/deactivate actions use confirmation dialogs;
- existing pagination, filter, and validation remain canonical.

Existing Web routes:

```text
GET    /admin/tour-packages
GET    /admin/tour-packages/create
POST   /admin/tour-packages
GET    /admin/tour-packages/{tourPackage}/edit
PUT    /admin/tour-packages/{tourPackage}
DELETE /admin/tour-packages/{tourPackage}
```

Implementation decision:

- modal submit reuses `POST`, `PUT`, and `DELETE` routes;
- create/edit pages remain as fallback routes;
- no REST API or domain change expected.

### 5. Vehicles

Status: `planned`

Target:

- modal create/edit;
- clear distinction between ownership, verification, and availability;
- semantic badges;
- confirmation for availability or destructive actions;
- responsive table/card fallback.

Existing Web routes:

```text
GET    /admin/vehicles
GET    /admin/vehicles/create
POST   /admin/vehicles
GET    /admin/vehicles/{vehicle}/edit
PUT    /admin/vehicles/{vehicle}
DELETE /admin/vehicles/{vehicle}
```

Backend impact: none expected beyond view data preparation.

### 6. Travel groups

Status: `planned`

Target:

- list styled consistently with the Figma data-table language;
- create group through modal;
- keep detail as a full workspace;
- attach booking through a searchable modal;
- show booking code, date, package, participants, payment status, and booking status before attachment;
- retain existing attach-booking rule and validation.

Existing Web routes:

```text
GET   /admin/travel-groups
GET   /admin/travel-groups/create
POST  /admin/travel-groups
GET   /admin/travel-groups/{travelGroup}
PATCH /admin/travel-groups/{travelGroup}/status
POST  /admin/travel-groups/{travelGroup}/bookings
```

Backend impact:

- potentially richer candidate-booking query;
- no new endpoint unless dataset size later proves server-side search necessary;
- no domain change.

### 7. Customers

Status: `planned`

Target:

- searchable and filterable list;
- full-page detail retained;
- replace generic status dropdown with contextual actions;
- use `Suspend customer` and `Aktifkan kembali` actions;
- confirmation explains access consequences;
- do not persist a suspension reason unless separately approved.

Existing Web routes:

```text
GET   /admin/customers
GET   /admin/customers/{customer}
PATCH /admin/customers/{customer}/status
```

Backend impact: none.

### 8. Bookings

Status: `highest priority / planned`

Target:

- use the Figma order/detail visual language;
- retain a full-page booking workspace;
- show timeline/context for payment, assignment, allocation, and trip state;
- replace raw status dropdown with valid next-action buttons;
- actions open contextual confirmation modals;
- never display actions that violate the current lifecycle;
- provide a clear route to payment verification when payment blocks the next action;
- assignment modal shows eligible drivers and their own eligible vehicles;
- participant allocation remains aligned with existing server behavior;
- destructive actions show consequences.

Existing Web routes:

```text
GET   /admin/bookings
GET   /admin/bookings/{booking}
PATCH /admin/bookings/{booking}/status
POST  /admin/bookings/{booking}/assignments
PATCH /admin/bookings/{booking}/assignments/{assignment}/cancel
PUT   /admin/bookings/{booking}/participant-allocations
```

Implementation decisions:

- existing lifecycle service remains authoritative;
- valid next actions are derived from current state;
- driver/vehicle dependent selection is handled by view data and JavaScript filtering;
- server validation remains mandatory;
- do not add bulk allocation if it requires a new contract;
- keep per-participant save or provide a visual grouped editor that still submits through existing supported actions;
- no point estimate is shown until point policy is approved.

Core services explicitly protected:

```text
BookingLifecycleService
assignment validation rules
payment prerequisites
completion reward behavior
```

### 9. Payments

Status: `high priority / planned`

Target:

- payment queue uses compact table/card styling;
- proof preview opens inside a modal when supported by browser format;
- separate `Setujui pembayaran` and `Tolak pembayaran` actions;
- rejection reason only appears in the reject modal;
- decision summary includes customer, booking, method, and amount;
- processed payments become read-only.

Existing Web routes:

```text
GET   /admin/payments
GET   /admin/payments/{payment}
PATCH /admin/payments/{payment}
```

Backend impact: none.

### 10. Driver verification

Status: `high priority / planned`

Target:

- adopt the Figma `Data Driver` table language;
- interpret online status through existing availability;
- retain full-page detail/verification workspace;
- separate profile, documents, and vehicles visually;
- preview document through modal;
- use contextual approve/reject actions;
- show rejection reason only when rejecting;
- make parent and child verification statuses explicit.

Existing Web routes:

```text
GET   /admin/drivers
GET   /admin/drivers/{driverProfile}
PATCH /admin/drivers/{driverProfile}
PATCH /admin/drivers/{driverProfile}/vehicles/{vehicle}
```

Existing API actions that may be reused conceptually by Web Admin if document verification is expanded:

```text
PATCH /api/v1/admin/driver-documents/{driverDocument}/verification
PATCH /api/v1/admin/driver-vehicles/{vehicle}/documents/{vehicleDocument}/verification
```

Implementation constraint:

- do not redesign driver onboarding behavior;
- do not add admin-created driver accounts;
- do not add real-time presence.

Potential backend adjustment:

- a small Web route/controller action may be needed only if current Admin Web cannot process document-level verification that already exists in the API;
- reuse existing service/validation logic rather than create a new domain rule.

### 11. Withdrawals

Status: `high priority / planned`

Target:

- use state-specific actions instead of one generic status dropdown;
- pending: `Setujui` or `Tolak`;
- approved: `Tandai sudah dibayar`;
- rejected/paid: read-only;
- confirmation modal summarizes driver, points, amount, bank, and account;
- retain current allowed transitions and ledger behavior.

Existing Web routes:

```text
GET   /admin/withdrawals
GET   /admin/withdrawals/{withdrawal}
PATCH /admin/withdrawals/{withdrawal}
```

Implementation decision:

- do not add transfer-reference fields;
- do not change conversion, minimum points, or balance categories;
- point policy remains pending.

Core service explicitly protected:

```text
WithdrawalService
```

### 12. Reports

Status: `planned`

Target:

- adopt Figma table/card styling;
- clearer report groups and date filters;
- preserve existing CSV export behavior;
- no chart or metric should invent data unavailable from current reports.

Existing Web routes:

```text
GET /admin/reports
GET /admin/reports/export/bookings
GET /admin/reports/export/payments
GET /admin/reports/export/drivers
GET /admin/reports/export/withdrawals
```

Backend impact: none expected.

### 13. Audit logs

Status: `planned`

Target:

- consistent filter bar and table;
- readable actor/action/resource/status labels;
- full-page detail retained;
- before/after data displayed as structured sections;
- no mutation or bulk action added.

Existing Web routes:

```text
GET /admin/audit-logs
GET /admin/audit-logs/{auditLog}
```

Backend impact: none.

### 14. Admin profile/settings

Status: `deferred unless existing routes support it`

Figma contains a profile/settings screen, but this must not automatically become a new backend feature.

Safe implementation options:

- show admin identity and logout only;
- link settings icon to existing supported actions;
- hide unsupported name/password editing controls.

Do not add profile or password-update endpoints as part of the visual redesign unless separately approved.

## Modal strategy

Suitable for modal interaction:

- tour package create/edit;
- vehicle create/edit;
- booking valid status actions;
- assignment offer/cancel;
- participant allocation action UI;
- payment approve/reject;
- driver approve/reject;
- document preview;
- vehicle verification;
- customer suspend/reactivate;
- withdrawal approve/reject/mark paid;
- travel group create;
- attach booking;
- destructive confirmations;
- success feedback.

Keep as full-page workspace:

- booking detail;
- driver detail and verification;
- travel group detail;
- customer detail;
- withdrawal financial detail;
- reports;
- audit-log detail.

A modal must not be used when it hides important operational context or creates an oversized form.

## Backend change policy

### Allowed lightweight adjustments

These do not count as business-flow changes:

- additional read-only aggregate queries;
- eager loading;
- filtered collections;
- pending queue counts;
- view composers;
- modal-specific view models;
- valid-action lists derived from current state;
- driver/vehicle pairing data;
- server-rendered search/filter parameters;
- Web-only document verification action that reuses existing logic.

### Not allowed in this track

- database migrations;
- new lifecycle states;
- changed booking transitions;
- changed payment rules;
- changed assignment eligibility;
- changed verification rules;
- changed withdrawal transitions;
- changed point values or timing;
- bulk mutation endpoints;
- real-time driver state;
- GPS/location tracking;
- new mobile API behavior;
- breaking response-contract changes.

If any design requirement cannot be achieved without one of the above, adapt or omit the UI element and document the deviation.

## Implementation phases

### Phase 0 — Design mapping

Status: `in progress`

- Figma access confirmed;
- key frames identified;
- design language reviewed;
- map each existing Admin module to the closest Figma pattern;
- document intentional deviations caused by existing workflow.

### Phase 1 — UI foundation

Status: `not started`

- design tokens;
- global layout;
- sidebar/topbar;
- Blade components;
- modal system;
- popup/flash system;
- form and validation behavior;
- table/filter/pagination system;
- responsive behavior.

### Phase 2 — Operational modules

Status: `not started`

Priority order:

1. Dashboard
2. Bookings
3. Payments
4. Driver verification
5. Withdrawals

### Phase 3 — Master and relationship modules

Status: `not started`

1. Tour packages
2. Vehicles
3. Travel groups
4. Customers

### Phase 4 — Monitoring modules

Status: `not started`

1. Reports
2. Audit logs

### Phase 5 — Acceptance

Status: `not started`

- functional regression tests;
- authorization regression;
- modal validation tests;
- status-transition regression;
- responsive desktop/tablet/mobile checks;
- keyboard and focus behavior;
- confirmation behavior;
- manual Admin Web E2E against seeded/local database;
- verify Postman-tested backend flow remains unchanged.

## Definition of done

The enhancement is complete only when:

1. Admin Web adopts the Figma visual language across all existing modules.
2. Existing workflows remain functionally unchanged.
3. Every action still uses canonical backend validation and authorization.
4. Invalid lifecycle actions are hidden or disabled with a clear reason.
5. Modal validation and old input work correctly.
6. Destructive and financial actions require confirmation.
7. Tables, filters, pagination, empty states, and status badges are consistent.
8. Core backend tests remain green.
9. Manual Admin Web happy path passes.
10. Existing Postman E2E behavior remains valid.
11. Point-related UI does not present pending values as final policy.
12. Any deviation from Figma is documented as a workflow-preserving adaptation.

## Current conclusion

The Figma design is feasible without complex backend changes.

Approved implementation stance:

```text
Use the Figma as the complete visual direction.
Use the existing Laravel backend as the complete workflow direction.
Prefer a small UX deviation over any unnecessary backend behavior change.
```
