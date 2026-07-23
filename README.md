# Offroad Booking API

Backend dan Web Admin untuk platform pemesanan wisata offroad.

## Project Status

> **BACKEND MVP: COMPLETED ?**

Status per tahap:

| Area | Status |
|---|---|
| REST API customer | Selesai |
| REST API driver | Selesai |
| Authentication & authorization | Selesai |
| Booking lifecycle | Selesai |
| Payment verification | Selesai |
| Travel groups | Selesai |
| Driver assignments | Selesai |
| Vehicle management | Selesai |
| Driver rewards & withdrawals | Selesai |
| Web Admin | Selesai |
| Shared Admin Layout | Selesai |
| Reports & audit logs | Selesai |
| Automated tests & CI | Hijau |

Backend telah dinyatakan selesai untuk cakupan MVP dan siap digunakan sebagai fondasi pengembangan aplikasi Flutter.

## Mobile integration updates

Tambahan terbaru untuk sinkronisasi dengan Flutter mobile:

- driver assignment yang baru dibuat tetap memakai notifikasi database Laravel
- assignment offered untuk driver juga mengirim push notification FCM
- token device driver disimpan di tabel `driver_device_tokens`
- endpoint driver untuk token push:
  - `POST /api/v1/driver/device-tokens`
  - `DELETE /api/v1/driver/device-tokens`
- driver bisa memulai dan menyelesaikan trip lewat:
  - `PATCH /api/v1/driver/assignments/{driverAssignment}/start-trip`
  - `PATCH /api/v1/driver/assignments/{driverAssignment}/complete-trip`

## FCM setup

Tambahkan ke `.env`:

```env
FCM_PROJECT_ID=offroad-bc692
FCM_CREDENTIALS_PATH=D:/DOWNLOAD/offroad-bc692-firebase-adminsdk-fbsvc-39e7d3fe27.json
```

Gunakan service account JSON dari Firebase Admin SDK, bukan `google-services.json`.

## Main Modules

- Customer authentication and profile
- Tour package catalog
- Booking and participant management
- Payment proof submission and verification
- Travel group management
- Driver registration and document verification
- Vehicle management
- Driver assignment and participant allocation
- Driver points and withdrawal processing
- Admin dashboard and operational management
- CSV reports and audit logs
- FCM push notification delivery for assigned drivers

## Technology

- Laravel
- Laravel Sanctum
- Blade and Tailwind CSS
- MySQL/PostgreSQL compatible database layer
- PHPUnit feature tests
- GitHub Actions CI

## Local Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
npm run build
php artisan serve
php artisan queue:work --queue=notifications
```

Run tests:

```bash
php artisan test
```

## Development Handoff

Tahap pengembangan berikutnya:

1. Flutter customer application
2. Flutter driver application
3. Staging environment
4. Production security hardening
5. Monitoring, backups, and deployment automation

## Completion Record

Backend MVP ditandai selesai setelah seluruh modul utama, Web Admin, shared layout, dan automated CI berhasil diselesaikan dan tervalidasi hijau.
