# Offroad Booking API

Backend dan Web Admin untuk platform pemesanan wisata offroad.

## Project Status

> **BACKEND MVP: COMPLETED ✅**

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
