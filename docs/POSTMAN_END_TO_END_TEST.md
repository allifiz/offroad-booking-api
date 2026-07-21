# Postman End-to-End Test — Offroad Booking API

Dokumen ini menguji flow backend dari awal sampai akhir sebelum aplikasi Flutter tersedia.

Semua langkah memakai **satu rangkaian data yang sama**, sehingga customer, driver, kendaraan, booking, pembayaran, assignment, reward, dan withdrawal saling terhubung serta tersimpan valid di database.

## Prasyarat

Backend harus sudah dijalankan dan database sudah di-seed:

```bash
php artisan migrate:fresh --seed
php artisan storage:link
php artisan serve
```

Siapkan file lokal berikut untuk upload multipart:

```text
./postman-files/profile-driver.jpg
./postman-files/ktp-driver.jpg
./postman-files/sim-driver.jpg
./postman-files/stnk-vehicle.jpg
./postman-files/vehicle-front.jpg
./postman-files/payment-proof.jpg
```

Buat Postman Environment dan isi nilai awal:

```text
base_url              = http://127.0.0.1:8000/api/v1
admin_email           = admin@offroad.test
admin_password        = password
customer_email        = customer.e2e@offroad.test
customer_phone        = 081299990001
customer_password     = password123
customer_name         = Customer E2E

driver_email          = driver.e2e@offroad.test
driver_phone          = 081399990001
driver_password       = password123
driver_name           = Driver E2E
driver_identity       = NIK-E2E-0001
driver_license        = SIM-E2E-0001
vehicle_plate         = B 9901 E2E

tour_date             = 2026-12-20
```

Setelah setiap request berhasil, salin nilai ID/token yang disebutkan pada bagian **Ekspektasi** ke Postman Environment.

---

## Test 01 — Health Check API

### cURL

```bash
curl --request GET \
  --url "{{base_url}}/health" \
  --header "Accept: application/json"
```

### Ekspektasi

- HTTP `200`.
- `success` bernilai `true`.
- `message` berisi `Offroad Booking API is running.`.

---

## Test 02 — Login Admin Seeded

### cURL

```bash
curl --request POST \
  --url "{{base_url}}/auth/login" \
  --header "Accept: application/json" \
  --header "Content-Type: application/json" \
  --data '{
    "email": "{{admin_email}}",
    "password": "{{admin_password}}",
    "device_name": "postman-e2e-admin"
  }'
```

### Ekspektasi

- HTTP `200`.
- User memiliki role `admin`.
- Response memiliki bearer token.
- Simpan token sebagai `admin_token`.

---

## Test 03 — Admin Membuat Paket Wisata Aktif

### cURL

```bash
curl --request POST \
  --url "{{base_url}}/admin/tour-packages" \
  --header "Accept: application/json" \
  --header "Authorization: Bearer {{admin_token}}" \
  --header "Content-Type: application/json" \
  --data '{
    "name": "Paket E2E Merapi Sunrise",
    "slug": "paket-e2e-merapi-sunrise",
    "description": "Paket khusus pengujian end-to-end Postman.",
    "meeting_point": "Basecamp E2E Kaliurang",
    "duration_minutes": 240,
    "minimum_participants": 2,
    "maximum_participants": 6,
    "price_per_person": 250000,
    "status": "active"
  }'
```

### Ekspektasi

- HTTP `201`.
- Message `Paket wisata berhasil dibuat.`.
- Status paket `active`.
- Simpan `data.id` sebagai `tour_package_id`.
- Nilai booking untuk 2 peserta nantinya adalah `500000`.

---

## Test 04 — Registrasi Customer Baru

### cURL

```bash
curl --request POST \
  --url "{{base_url}}/customers/register" \
  --header "Accept: application/json" \
  --header "Content-Type: application/json" \
  --data '{
    "name": "{{customer_name}}",
    "email": "{{customer_email}}",
    "phone": "{{customer_phone}}",
    "password": "{{customer_password}}",
    "password_confirmation": "{{customer_password}}",
    "device_name": "postman-e2e-customer"
  }'
```

### Ekspektasi

- HTTP `201`.
- Message `Registrasi customer berhasil.`.
- User memiliki role `customer` dan status `active`.
- Simpan `data.access_token` sebagai `customer_token`.
- Simpan `data.user.id` sebagai `customer_id`.

---

## Test 05 — Customer Membaca Detail Paket yang Sama

### cURL

```bash
curl --request GET \
  --url "{{base_url}}/tour-packages/{{tour_package_id}}" \
  --header "Accept: application/json"
```

### Ekspektasi

- HTTP `200`.
- ID sama dengan `tour_package_id`.
- Nama paket `Paket E2E Merapi Sunrise`.
- Status paket `active`.
- Harga per orang `250000`.

---

## Test 06 — Customer Membuat Booking Dua Peserta

### cURL

```bash
curl --request POST \
  --url "{{base_url}}/customer/bookings" \
  --header "Accept: application/json" \
  --header "Authorization: Bearer {{customer_token}}" \
  --header "Content-Type: application/json" \
  --data '{
    "tour_package_id": {{tour_package_id}},
    "tour_date": "{{tour_date}}",
    "notes": "Booking E2E untuk pengujian flow lengkap.",
    "participants": [
      {
        "name": "{{customer_name}}",
        "phone": "{{customer_phone}}",
        "is_group_leader": true
      },
      {
        "name": "Peserta E2E Dua",
        "phone": "081299990002",
        "is_group_leader": false
      }
    ]
  }'
```

### Ekspektasi

- HTTP `201`.
- Message `Booking berhasil dibuat.`.
- `participant_count` bernilai `2`.
- `total_amount` bernilai `500000`.
- Status booking `pending`.
- Payment status `unpaid`.
- Simpan `data.id` sebagai `booking_id`.
- Simpan `data.booking_code` sebagai `booking_code`.
- Simpan `data.total_amount` sebagai `payment_amount`.
- Simpan ID kedua peserta dari `data.participants` sebagai `participant_1_id` dan `participant_2_id` bila ingin menguji alokasi peserta.

---

## Test 07 — Customer Membaca Booking yang Baru Dibuat

### cURL

```bash
curl --request GET \
  --url "{{base_url}}/customer/bookings/{{booking_id}}" \
  --header "Accept: application/json" \
  --header "Authorization: Bearer {{customer_token}}"
```

### Ekspektasi

- HTTP `200`.
- Booking code sama dengan `booking_code`.
- Customer booking adalah user dari `customer_token`.
- Paket wisata memiliki ID `tour_package_id`.
- Terdapat dua peserta.

---

## Test 08 — Customer Mengunggah Bukti Pembayaran

### cURL

```bash
curl --request POST \
  --url "{{base_url}}/customer/bookings/{{booking_id}}/payments" \
  --header "Accept: application/json" \
  --header "Authorization: Bearer {{customer_token}}" \
  --form "amount={{payment_amount}}" \
  --form "method=bank_transfer" \
  --form "proof=@./postman-files/payment-proof.jpg"
```

### Ekspektasi

- HTTP `201`.
- Payment terhubung ke `booking_id` dan `customer_id`.
- Amount sama dengan `500000`.
- Status payment `pending`.
- Payment status pada booking menjadi `pending`.
- Simpan `data.id` sebagai `payment_id`.

---

## Test 09 — Admin Membaca Pembayaran yang Sama

### cURL

```bash
curl --request GET \
  --url "{{base_url}}/admin/payments/{{payment_id}}" \
  --header "Accept: application/json" \
  --header "Authorization: Bearer {{admin_token}}"
```

### Ekspektasi

- HTTP `200`.
- Payment ID sama dengan `payment_id`.
- Booking code sama dengan `booking_code`.
- Customer email sama dengan `customer_email`.
- Status payment `pending`.

---

## Test 10 — Admin Menyetujui Pembayaran

### cURL

```bash
curl --request PATCH \
  --url "{{base_url}}/admin/payments/{{payment_id}}/verification" \
  --header "Accept: application/json" \
  --header "Authorization: Bearer {{admin_token}}" \
  --header "Content-Type: application/json" \
  --data '{
    "status": "paid"
  }'
```

### Ekspektasi

- HTTP `200`.
- Message `Pembayaran berhasil disetujui.`.
- Payment status `paid`.
- Payment status pada booking juga `paid`.

---

## Test 11 — Admin Mengonfirmasi Booking

### cURL

```bash
curl --request PATCH \
  --url "{{base_url}}/admin/bookings/{{booking_id}}/status" \
  --header "Accept: application/json" \
  --header "Authorization: Bearer {{admin_token}}" \
  --header "Content-Type: application/json" \
  --data '{
    "status": "confirmed"
  }'
```

### Ekspektasi

- HTTP `200`.
- Message `Status booking berhasil diperbarui.`.
- Booking berubah dari `pending` menjadi `confirmed`.
- Payment status tetap `paid`.

---

## Test 12 — Registrasi Driver, Dokumen, dan Kendaraan

### cURL

```bash
curl --request POST \
  --url "{{base_url}}/driver/register" \
  --header "Accept: application/json" \
  --form "name={{driver_name}}" \
  --form "email={{driver_email}}" \
  --form "phone={{driver_phone}}" \
  --form "password={{driver_password}}" \
  --form "password_confirmation={{driver_password}}" \
  --form "identity_number={{driver_identity}}" \
  --form "license_number={{driver_license}}" \
  --form "address=Jalan E2E Nomor 1, Yogyakarta" \
  --form "date_of_birth=1990-01-15" \
  --form "profile_photo=@./postman-files/profile-driver.jpg" \
  --form "driver_documents[0][type]=identity_card" \
  --form "driver_documents[0][document_number]={{driver_identity}}" \
  --form "driver_documents[0][file]=@./postman-files/ktp-driver.jpg" \
  --form "driver_documents[1][type]=driver_license" \
  --form "driver_documents[1][document_number]={{driver_license}}" \
  --form "driver_documents[1][expires_at]=2030-12-31" \
  --form "driver_documents[1][file]=@./postman-files/sim-driver.jpg" \
  --form "vehicle[name]=Jeep E2E Willys" \
  --form "vehicle[plate_number]={{vehicle_plate}}" \
  --form "vehicle[brand]=Willys" \
  --form "vehicle[model]=CJ3B" \
  --form "vehicle[year]=1980" \
  --form "vehicle[capacity]=4" \
  --form "vehicle[notes]=Kendaraan khusus test E2E" \
  --form "vehicle_documents[0][type]=vehicle_registration" \
  --form "vehicle_documents[0][document_number]=STNK-E2E-0001" \
  --form "vehicle_documents[0][expires_at]=2030-12-31" \
  --form "vehicle_documents[0][file]=@./postman-files/stnk-vehicle.jpg" \
  --form "vehicle_photos[0]=@./postman-files/vehicle-front.jpg"
```

### Ekspektasi

- HTTP `201`.
- Message menyatakan data menunggu verifikasi admin.
- Role user `driver`.
- Driver profile verification `pending` dan availability `unavailable`.
- Kendaraan verification `pending` dan availability `unavailable`.
- Simpan `data.id` sebagai `driver_user_id`.
- Simpan `data.driver_profile.id` sebagai `driver_profile_id`.
- Simpan dua ID dokumen driver sebagai `driver_document_1_id` dan `driver_document_2_id`.
- Simpan ID kendaraan sebagai `driver_vehicle_id`.
- Dari detail driver admin pada langkah berikutnya, simpan ID dokumen kendaraan sebagai `vehicle_document_id`.

---

## Test 13 — Admin Membaca Detail Driver Baru

### cURL

```bash
curl --request GET \
  --url "{{base_url}}/admin/drivers/{{driver_profile_id}}" \
  --header "Accept: application/json" \
  --header "Authorization: Bearer {{admin_token}}"
```

### Ekspektasi

- HTTP `200`.
- Email driver sama dengan `driver_email`.
- Profile verification `pending`.
- Terdapat dua dokumen driver.
- Terdapat kendaraan dengan ID `driver_vehicle_id`.
- Salin ID dokumen kendaraan pertama sebagai `vehicle_document_id`.

---

## Test 14 — Admin Menyetujui Dokumen Driver Pertama

### cURL

```bash
curl --request PATCH \
  --url "{{base_url}}/admin/driver-documents/{{driver_document_1_id}}/verification" \
  --header "Accept: application/json" \
  --header "Authorization: Bearer {{admin_token}}" \
  --header "Content-Type: application/json" \
  --data '{
    "verification_status": "approved"
  }'
```

### Ekspektasi

- HTTP `200`.
- Verification dokumen pertama menjadi `approved`.

---

## Test 15 — Admin Menyetujui Dokumen Driver Kedua

### cURL

```bash
curl --request PATCH \
  --url "{{base_url}}/admin/driver-documents/{{driver_document_2_id}}/verification" \
  --header "Accept: application/json" \
  --header "Authorization: Bearer {{admin_token}}" \
  --header "Content-Type: application/json" \
  --data '{
    "verification_status": "approved"
  }'
```

### Ekspektasi

- HTTP `200`.
- Verification dokumen kedua menjadi `approved`.

---

## Test 16 — Admin Menyetujui Dokumen Kendaraan

### cURL

```bash
curl --request PATCH \
  --url "{{base_url}}/admin/driver-vehicles/{{driver_vehicle_id}}/documents/{{vehicle_document_id}}/verification" \
  --header "Accept: application/json" \
  --header "Authorization: Bearer {{admin_token}}" \
  --header "Content-Type: application/json" \
  --data '{
    "verification_status": "approved"
  }'
```

### Ekspektasi

- HTTP `200`.
- Verification dokumen kendaraan menjadi `approved`.

---

## Test 17 — Admin Menyetujui Kendaraan Driver

### cURL

```bash
curl --request PATCH \
  --url "{{base_url}}/admin/driver-vehicles/{{driver_vehicle_id}}/verification" \
  --header "Accept: application/json" \
  --header "Authorization: Bearer {{admin_token}}" \
  --header "Content-Type: application/json" \
  --data '{
    "verification_status": "approved"
  }'
```

### Ekspektasi

- HTTP `200`.
- Message `Kendaraan driver berhasil disetujui.`.
- Vehicle verification menjadi `approved`.

---

## Test 18 — Admin Menyetujui Profil Driver

### cURL

```bash
curl --request PATCH \
  --url "{{base_url}}/admin/drivers/{{driver_profile_id}}/verification" \
  --header "Accept: application/json" \
  --header "Authorization: Bearer {{admin_token}}" \
  --header "Content-Type: application/json" \
  --data '{
    "verification_status": "approved"
  }'
```

### Ekspektasi

- HTTP `200`.
- Message `Driver berhasil disetujui.`.
- Driver profile verification menjadi `approved`.

---

## Test 19 — Admin Mengubah Kendaraan Menjadi Available

### cURL

```bash
curl --request PATCH \
  --url "{{base_url}}/admin/vehicles/{{driver_vehicle_id}}" \
  --header "Accept: application/json" \
  --header "Authorization: Bearer {{admin_token}}" \
  --header "Content-Type: application/json" \
  --data '{
    "status": "available"
  }'
```

### Ekspektasi

- HTTP `200`.
- Status operasional kendaraan menjadi `available`.
- Verification kendaraan tetap `approved`.

---

## Test 20 — Login Driver yang Sudah Disetujui

### cURL

```bash
curl --request POST \
  --url "{{base_url}}/auth/login" \
  --header "Accept: application/json" \
  --header "Content-Type: application/json" \
  --data '{
    "email": "{{driver_email}}",
    "password": "{{driver_password}}",
    "device_name": "postman-e2e-driver"
  }'
```

### Ekspektasi

- HTTP `200`.
- User role `driver`.
- Simpan bearer token sebagai `driver_token`.

---

## Test 21 — Driver Mengubah Availability Menjadi Available

### cURL

```bash
curl --request PATCH \
  --url "{{base_url}}/driver/availability" \
  --header "Accept: application/json" \
  --header "Authorization: Bearer {{driver_token}}" \
  --header "Content-Type: application/json" \
  --data '{
    "status": "available"
  }'
```

### Ekspektasi

- HTTP `200`.
- Message `Availability driver berhasil diperbarui.`.
- Driver status menjadi `available`.

---

## Test 22 — Admin Menawarkan Assignment Driver

### cURL

```bash
curl --request POST \
  --url "{{base_url}}/admin/bookings/{{booking_id}}/driver-assignments" \
  --header "Accept: application/json" \
  --header "Authorization: Bearer {{admin_token}}" \
  --header "Content-Type: application/json" \
  --data '{
    "driver_id": {{driver_user_id}},
    "vehicle_id": {{driver_vehicle_id}}
  }'
```

### Ekspektasi

- HTTP `201`.
- Message `Assignment driver berhasil dibuat.`.
- Assignment terhubung ke `booking_id`, `driver_user_id`, dan `driver_vehicle_id`.
- Status assignment `offered`.
- Simpan `data.id` sebagai `assignment_id`.

---

## Test 23 — Driver Membaca Assignment yang Sama

### cURL

```bash
curl --request GET \
  --url "{{base_url}}/driver/assignments/{{assignment_id}}" \
  --header "Accept: application/json" \
  --header "Authorization: Bearer {{driver_token}}"
```

### Ekspektasi

- HTTP `200`.
- Assignment ID sama dengan `assignment_id`.
- Booking code sama dengan `booking_code`.
- Kendaraan ID sama dengan `driver_vehicle_id`.
- Status assignment `offered`.

---

## Test 24 — Driver Menerima Assignment

### cURL

```bash
curl --request PATCH \
  --url "{{base_url}}/driver/assignments/{{assignment_id}}/accept" \
  --header "Accept: application/json" \
  --header "Authorization: Bearer {{driver_token}}"
```

### Ekspektasi

- HTTP `200`.
- Message `Assignment berhasil diterima.`.
- Assignment status menjadi `accepted`.

---

## Test 25 — Admin Memulai Perjalanan

### cURL

```bash
curl --request PATCH \
  --url "{{base_url}}/admin/bookings/{{booking_id}}/status" \
  --header "Accept: application/json" \
  --header "Authorization: Bearer {{admin_token}}" \
  --header "Content-Type: application/json" \
  --data '{
    "status": "ongoing"
  }'
```

### Ekspektasi

- HTTP `200`.
- Booking berubah dari `confirmed` menjadi `ongoing`.
- Payment status tetap `paid`.
- Assignment tetap `accepted`.

---

## Test 26 — Admin Menyelesaikan Perjalanan

### cURL

```bash
curl --request PATCH \
  --url "{{base_url}}/admin/bookings/{{booking_id}}/status" \
  --header "Accept: application/json" \
  --header "Authorization: Bearer {{admin_token}}" \
  --header "Content-Type: application/json" \
  --data '{
    "status": "completed"
  }'
```

### Ekspektasi

- HTTP `200`.
- Booking berubah dari `ongoing` menjadi `completed`.
- Sistem membuat reward driver secara idempotent.
- Dengan konfigurasi default, driver menerima `100` poin.

---

## Test 27 — Driver Memeriksa Ringkasan Poin

### cURL

```bash
curl --request GET \
  --url "{{base_url}}/driver/points/summary" \
  --header "Accept: application/json" \
  --header "Authorization: Bearer {{driver_token}}"
```

### Ekspektasi

- HTTP `200`.
- Available points minimal `100`.
- Held points `0` sebelum withdrawal.
- Konversi default adalah Rp1.000 per poin.

---

## Test 28 — Driver Memeriksa Ledger Reward

### cURL

```bash
curl --request GET \
  --url "{{base_url}}/driver/points/ledger" \
  --header "Accept: application/json" \
  --header "Authorization: Bearer {{driver_token}}"
```

### Ekspektasi

- HTTP `200`.
- Terdapat ledger tipe `credit` sebesar `100` poin.
- Reference ID mengarah ke `booking_id`.
- Description menyebut reward trip selesai dan `booking_code`.

---

## Test 29 — Driver Mengajukan Withdrawal Semua Poin

### cURL

```bash
curl --request POST \
  --url "{{base_url}}/driver/withdrawals" \
  --header "Accept: application/json" \
  --header "Authorization: Bearer {{driver_token}}" \
  --header "Content-Type: application/json" \
  --data '{
    "points": 100,
    "bank_name": "Bank E2E Indonesia",
    "account_number": "9901000001",
    "account_name": "{{driver_name}}"
  }'
```

### Ekspektasi

- HTTP `201`.
- Withdrawal status `pending`.
- Points `100`.
- Amount dengan konfigurasi default bernilai `100000`.
- Available points berkurang menjadi `0`.
- Held points bertambah menjadi `100`.
- Simpan `data.id` sebagai `withdrawal_id`.

---

## Test 30 — Admin Membaca Withdrawal yang Sama

### cURL

```bash
curl --request GET \
  --url "{{base_url}}/admin/withdrawals/{{withdrawal_id}}" \
  --header "Accept: application/json" \
  --header "Authorization: Bearer {{admin_token}}"
```

### Ekspektasi

- HTTP `200`.
- Driver sama dengan `driver_user_id`.
- Withdrawal ID sama dengan `withdrawal_id`.
- Status `pending`.
- Points `100` dan amount `100000`.

---

## Test 31 — Admin Menyetujui Withdrawal

### cURL

```bash
curl --request PATCH \
  --url "{{base_url}}/admin/withdrawals/{{withdrawal_id}}" \
  --header "Accept: application/json" \
  --header "Authorization: Bearer {{admin_token}}" \
  --header "Content-Type: application/json" \
  --data '{
    "status": "approved"
  }'
```

### Ekspektasi

- HTTP `200`.
- Withdrawal berubah dari `pending` menjadi `approved`.
- Held points tetap `100` sampai payout ditandai paid.

---

## Test 32 — Admin Menandai Withdrawal Sudah Dibayar

### cURL

```bash
curl --request PATCH \
  --url "{{base_url}}/admin/withdrawals/{{withdrawal_id}}" \
  --header "Accept: application/json" \
  --header "Authorization: Bearer {{admin_token}}" \
  --header "Content-Type: application/json" \
  --data '{
    "status": "paid"
  }'
```

### Ekspektasi

- HTTP `200`.
- Withdrawal berubah dari `approved` menjadi `paid`.
- Held points driver kembali menjadi `0`.
- Available points tetap `0` karena poin sudah dibayarkan.

---

## Test 33 — Driver Memeriksa Riwayat Withdrawal Final

### cURL

```bash
curl --request GET \
  --url "{{base_url}}/driver/withdrawals" \
  --header "Accept: application/json" \
  --header "Authorization: Bearer {{driver_token}}"
```

### Ekspektasi

- HTTP `200`.
- Terdapat withdrawal ID `withdrawal_id`.
- Status final `paid`.
- Points `100`.
- Amount `100000`.

---

## Test 34 — Customer Memeriksa Booking Final

### cURL

```bash
curl --request GET \
  --url "{{base_url}}/customer/bookings/{{booking_id}}" \
  --header "Accept: application/json" \
  --header "Authorization: Bearer {{customer_token}}"
```

### Ekspektasi

- HTTP `200`.
- Booking code sama dengan `booking_code`.
- Status booking `completed`.
- Payment status `paid`.
- Paket, peserta, total amount, dan tour date tetap sama dengan data awal.

---

## Test 35 — Admin Memeriksa Audit Log Flow

### cURL

```bash
curl --request GET \
  --url "{{base_url}}/admin/audit-logs?per_page=100" \
  --header "Accept: application/json" \
  --header "Authorization: Bearer {{admin_token}}"
```

### Ekspektasi

- HTTP `200`.
- Terdapat audit log untuk beberapa perubahan penting, seperti verifikasi pembayaran, verifikasi driver/kendaraan, perubahan booking, dan pemrosesan withdrawal.
- Actor untuk tindakan admin mengarah ke user admin yang login.

---

## Test 36 — Logout Customer

### cURL

```bash
curl --request POST \
  --url "{{base_url}}/auth/logout" \
  --header "Accept: application/json" \
  --header "Authorization: Bearer {{customer_token}}"
```

### Ekspektasi

- HTTP `200`.
- Token customer aktif dicabut.
- Request berikutnya menggunakan token yang sama menghasilkan HTTP `401`.

---

## Test 37 — Logout Driver

### cURL

```bash
curl --request POST \
  --url "{{base_url}}/auth/logout" \
  --header "Accept: application/json" \
  --header "Authorization: Bearer {{driver_token}}"
```

### Ekspektasi

- HTTP `200`.
- Token driver aktif dicabut.
- Request berikutnya menggunakan token yang sama menghasilkan HTTP `401`.

---

## Test 38 — Logout Admin

### cURL

```bash
curl --request POST \
  --url "{{base_url}}/auth/logout" \
  --header "Accept: application/json" \
  --header "Authorization: Bearer {{admin_token}}"
```

### Ekspektasi

- HTTP `200`.
- Token admin aktif dicabut.
- Seluruh data E2E yang dibuat tetap tersimpan di database untuk pemeriksaan manual.

---

# Kondisi Akhir Database

Setelah seluruh test berhasil, database harus memiliki rangkaian data berikut:

```text
Admin seeded
  └── membuat 1 paket wisata aktif

Customer E2E
  └── membuat 1 booking dengan 2 peserta
      └── mengirim 1 pembayaran
          └── diverifikasi paid oleh admin

Driver E2E
  ├── memiliki 2 dokumen driver approved
  ├── memiliki 1 kendaraan approved dan available
  ├── menerima 1 assignment untuk booking yang sama
  ├── menyelesaikan trip melalui lifecycle booking
  ├── menerima 100 reward points
  └── mengajukan 1 withdrawal 100 poin
      └── approved lalu paid oleh admin
```

Status final yang diharapkan:

```text
Tour package       = active
Booking            = completed
Booking payment    = paid
Payment            = paid
Driver verification = approved
Driver availability = available
Vehicle verification = approved
Vehicle availability = available
Assignment         = accepted
Driver points      = available 0 / held 0
Withdrawal         = paid
```

## Menjalankan Ulang Flow

Karena email, nomor telepon, nomor identitas, nomor SIM, slug paket, dan nomor polisi bersifat unik, gunakan salah satu cara berikut sebelum mengulang flow:

```bash
php artisan migrate:fresh --seed
```

atau ubah semua data unik pada Postman Environment sebelum menjalankan ulang.