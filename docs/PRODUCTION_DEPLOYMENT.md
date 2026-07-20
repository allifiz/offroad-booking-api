# Production Deployment and Recovery

## Prerequisites

- Linux server with PHP 8.4, Composer 2, MySQL 8/MariaDB, Nginx, and Supervisor.
- Application directory: `/var/www/offroad-booking-api`.
- Writable `storage` and `bootstrap/cache` directories.
- Production `.env` with `APP_ENV=production`, `APP_DEBUG=false`, MySQL credentials, and `QUEUE_CONNECTION=database`.

## First deployment

```bash
cd /var/www/offroad-booking-api
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan app:health
```

Install the queue worker using `deploy/supervisor/offroad-booking-worker.conf` and verify:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl status
php artisan queue:health
```

## Routine deployment

Make the scripts executable once:

```bash
chmod +x deploy/scripts/deploy.sh deploy/scripts/backup.sh
```

Run a backup before deployment:

```bash
APP_DIR=/var/www/offroad-booking-api \
BACKUP_DIR=/var/backups/offroad-booking-api \
./deploy/scripts/backup.sh
```

Deploy the latest `main`:

```bash
APP_DIR=/var/www/offroad-booking-api \
BRANCH=main \
./deploy/scripts/deploy.sh
```

The deploy script enters maintenance mode, updates code, installs production dependencies, runs migrations, rebuilds caches, restarts queue workers, executes `app:health`, and only then reopens the application.

## Monitoring

Recommended checks:

```bash
php artisan app:health --json
php artisan queue:health --json
php artisan queue:failed
sudo supervisorctl status
```

Suggested schedule:

```cron
*/5 * * * * cd /var/www/offroad-booking-api && php artisan app:health --json >> storage/logs/health.log 2>&1
*/5 * * * * cd /var/www/offroad-booking-api && php artisan queue:health --json >> storage/logs/queue-health.log 2>&1
0 2 * * * APP_DIR=/var/www/offroad-booking-api BACKUP_DIR=/var/backups/offroad-booking-api /var/www/offroad-booking-api/deploy/scripts/backup.sh >> /var/log/offroad-backup.log 2>&1
```

For real production, send non-zero exit codes to an external monitor instead of relying only on local log files.

## Backup contents

Each timestamped backup directory contains:

- `database.sql.gz`
- `storage.tar.gz`
- `env.backup` with mode `600`
- `SHA256SUMS`

Default retention is 14 days. Override with `RETENTION_DAYS`.

## Restore procedure

1. Put the application in maintenance mode.
2. Verify checksums.
3. Restore the database.
4. Restore uploaded files.
5. Restore or recreate `.env` securely.
6. Rebuild caches and restart workers.
7. Run health checks before reopening.

```bash
php artisan down --retry=60
cd /var/backups/offroad-booking-api/20260720T120000Z
sha256sum -c SHA256SUMS

gunzip -c database.sql.gz | mysql -h 127.0.0.1 -u offroad_booking -p offroad_booking
cd /var/www/offroad-booking-api
tar -xzf /var/backups/offroad-booking-api/20260720T120000Z/storage.tar.gz

php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
php artisan app:health
php artisan queue:health
php artisan up
```

## Rollback

Database migrations are not automatically reversible during deployment. Prefer forward fixes. If code rollback is required:

```bash
php artisan down --retry=60
git reset --hard <known-good-commit>
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
php artisan app:health
php artisan up
```

Restore the database backup only when the rollback also requires reversing incompatible data/schema changes.
