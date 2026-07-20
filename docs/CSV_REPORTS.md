# Admin CSV Report Exports

All report exports require an authenticated Sanctum token with the `admin` role.

Base path:

```text
/api/v1/admin/reports/export
```

## Endpoints

```text
GET /bookings
GET /payments
GET /drivers
GET /withdrawals
```

Every export is returned as a streamed UTF-8 CSV file with a BOM so it opens cleanly in Microsoft Excel.

Response headers include:

```text
Content-Type: text/csv; charset=UTF-8
Content-Disposition: attachment; filename=<report>-YYYYMMDD-HHMMSS.csv
Cache-Control: no-store, no-cache, must-revalidate
X-Content-Type-Options: nosniff
```

Text beginning with `=`, `+`, `-`, or `@` is prefixed with an apostrophe to reduce spreadsheet formula-injection risk.

## Shared period filters

```text
date_from=YYYY-MM-DD
date_to=YYYY-MM-DD
```

Defaults to the latest 30 days. Maximum range is 366 days.

## Booking export

```text
GET /api/v1/admin/reports/export/bookings
```

Optional status:

```text
pending | confirmed | ongoing | completed | cancelled
```

Columns include booking code, customer, package, tour date, participants, amount, booking status, payment status, and created time.

## Payment export

```text
GET /api/v1/admin/reports/export/payments
```

Optional status:

```text
unpaid | pending | paid | refunded | failed
```

Columns include payment ID, booking code, customer, amount, method, status, submission/review data, and rejection reason.

## Driver export

```text
GET /api/v1/admin/reports/export/drivers
```

Optional filters:

```text
status=available|unavailable|suspended
verification_status=pending|approved|rejected
```

Columns include driver identity, contact information, operational and verification status, point balances, verifier, and timestamps.

## Withdrawal export

```text
GET /api/v1/admin/reports/export/withdrawals
```

Optional status:

```text
pending | approved | rejected | paid
```

Columns include driver, points, amount, bank account, processor, status, rejection reason, and timestamps.

## PowerShell download example

```powershell
$headers = @{
    Accept = "text/csv"
    Authorization = "Bearer YOUR_ADMIN_TOKEN"
}

Invoke-WebRequest `
    -Uri "http://127.0.0.1:8000/api/v1/admin/reports/export/bookings?date_from=2026-07-01&date_to=2026-07-31" `
    -Headers $headers `
    -OutFile ".\bookings-july-2026.csv"
```
