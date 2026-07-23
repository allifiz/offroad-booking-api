# API Documentation

Sumber dokumentasi API berada di:

```text
docs/openapi.yaml
```

## Validasi lokal

```powershell
npx --yes @redocly/cli@latest lint docs/openapi.yaml
```

## Preview menggunakan Redoc

```powershell
npx --yes @redocly/cli@latest preview-docs docs/openapi.yaml
```

## Preview menggunakan Swagger UI via Docker

```powershell
docker run --rm -p 8081:8080 `
  -e SWAGGER_JSON=/spec/openapi.yaml `
  -v "${PWD}/docs:/spec" `
  swaggerapi/swagger-ui
```

Buka:

```text
http://127.0.0.1:8081
```

## Import ke Postman

1. Buka Postman.
2. Pilih **Import**.
3. Pilih file `docs/openapi.yaml`.
4. Postman akan membuat collection dari operasi yang terdokumentasi.

## Catatan integrasi mobile

- Endpoint driver assignment push token: `POST /driver/device-tokens` dan `DELETE /driver/device-tokens`.
- Assignment offer untuk driver menggunakan notifikasi database Laravel dan push FCM untuk driver yang sama.
- Pastikan queue worker berjalan untuk queue `notifications` agar push assignment terkirim.

## Aturan pemeliharaan

- Setiap endpoint baru harus ditambahkan ke `docs/openapi.yaml`.
- Perubahan request, response, status code, auth, atau rate limit harus diperbarui pada spec.
- GitHub Actions menjalankan Redocly lint pada setiap push dan pull request menuju `main`.
- Spec tidak menggantikan feature test; keduanya harus tetap konsisten.
