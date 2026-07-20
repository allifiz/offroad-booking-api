# Admin Dashboard Metrics

## Endpoint

```http
GET /api/v1/admin/dashboard
Authorization: Bearer <ADMIN_TOKEN>
Accept: application/json
```

Optional query parameters:

- `date_from`: start date in `YYYY-MM-DD`
- `date_to`: end date in `YYYY-MM-DD`, must be on or after `date_from`
- maximum range: 366 days
- default range: the latest 30 calendar days

## Metrics

The response contains:

- booking counts by status, participant count, and gross booking value
- payment counts by status, paid revenue, pending amount, and refunded amount
- total/approved/available drivers and point liabilities
- total/approved/available vehicles
- withdrawal counts by status, requested points, paid amount, and pending amount
- zero-filled daily trend for bookings, gross booking value, and paid revenue

Driver and vehicle inventory metrics are current snapshots. Booking, payment, withdrawal, and trend metrics follow the selected reporting period.

## Example

```bash
curl --location 'http://127.0.0.1:8000/api/v1/admin/dashboard?date_from=2026-07-01&date_to=2026-07-31' \
--header 'Accept: application/json' \
--header 'Authorization: Bearer YOUR_ADMIN_TOKEN'
```

A customer or driver token receives `403 Forbidden`. Invalid or excessive date ranges receive `422 Unprocessable Entity`.
