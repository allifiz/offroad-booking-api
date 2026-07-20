#!/usr/bin/env bash
set -Eeuo pipefail

APP_DIR="${APP_DIR:-/var/www/offroad-booking-api}"
PHP_BIN="${PHP_BIN:-php}"
COMPOSER_BIN="${COMPOSER_BIN:-composer}"
BRANCH="${BRANCH:-main}"

cd "$APP_DIR"

$PHP_BIN artisan down --retry=60
trap '$PHP_BIN artisan up >/dev/null 2>&1 || true' EXIT

git fetch origin "$BRANCH"
git reset --hard "origin/$BRANCH"

$COMPOSER_BIN install \
  --no-dev \
  --no-interaction \
  --prefer-dist \
  --optimize-autoloader

$PHP_BIN artisan migrate --force
$PHP_BIN artisan storage:link || true
$PHP_BIN artisan optimize:clear
$PHP_BIN artisan config:cache
$PHP_BIN artisan route:cache
$PHP_BIN artisan view:cache
$PHP_BIN artisan queue:restart
$PHP_BIN artisan app:health

$PHP_BIN artisan up
trap - EXIT

echo "Deployment completed successfully."
