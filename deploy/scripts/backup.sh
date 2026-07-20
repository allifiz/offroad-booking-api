#!/usr/bin/env bash
set -Eeuo pipefail

APP_DIR="${APP_DIR:-/var/www/offroad-booking-api}"
BACKUP_DIR="${BACKUP_DIR:-/var/backups/offroad-booking-api}"
RETENTION_DAYS="${RETENTION_DAYS:-14}"
TIMESTAMP="$(date -u +%Y%m%dT%H%M%SZ)"
DESTINATION="${BACKUP_DIR}/${TIMESTAMP}"

cd "$APP_DIR"
mkdir -p "$DESTINATION"

env_value() {
  local key="$1"
  local fallback="${2:-}"
  local value

  value="$(grep -E "^${key}=" .env | tail -n 1 | cut -d= -f2- || true)"
  value="${value%\"}"
  value="${value#\"}"

  printf '%s' "${value:-$fallback}"
}

DB_HOST_VALUE="${DB_HOST:-$(env_value DB_HOST 127.0.0.1)}"
DB_PORT_VALUE="${DB_PORT:-$(env_value DB_PORT 3306)}"
DB_DATABASE_VALUE="${DB_DATABASE:-$(env_value DB_DATABASE)}"
DB_USERNAME_VALUE="${DB_USERNAME:-$(env_value DB_USERNAME)}"
DB_PASSWORD_VALUE="${DB_PASSWORD:-$(env_value DB_PASSWORD)}"

: "${DB_DATABASE_VALUE:?DB_DATABASE is required}"
: "${DB_USERNAME_VALUE:?DB_USERNAME is required}"

php artisan down --retry=60
trap 'php artisan up >/dev/null 2>&1 || true' EXIT

MYSQL_PWD="$DB_PASSWORD_VALUE" mysqldump \
  --host="$DB_HOST_VALUE" \
  --port="$DB_PORT_VALUE" \
  --user="$DB_USERNAME_VALUE" \
  --single-transaction \
  --routines \
  --triggers \
  --set-gtid-purged=OFF \
  "$DB_DATABASE_VALUE" | gzip -9 > "${DESTINATION}/database.sql.gz"

tar -czf "${DESTINATION}/storage.tar.gz" storage/app/public
cp .env "${DESTINATION}/env.backup"
chmod 600 "${DESTINATION}/env.backup"
sha256sum "${DESTINATION}"/* > "${DESTINATION}/SHA256SUMS"

php artisan up
trap - EXIT

find "$BACKUP_DIR" -mindepth 1 -maxdepth 1 -type d -mtime "+${RETENTION_DAYS}" -exec rm -rf {} +

echo "Backup created at ${DESTINATION}"
