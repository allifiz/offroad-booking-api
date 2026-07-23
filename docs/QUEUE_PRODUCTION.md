# Production Queue Operations

## Environment

Use a real asynchronous queue in production:

```env
QUEUE_CONNECTION=database
DB_QUEUE_TABLE=jobs
DB_QUEUE=default
DB_QUEUE_RETRY_AFTER=120
QUEUE_FAILED_DRIVER=database-uuids
QUEUE_HEALTH_PENDING_WARNING=100
QUEUE_HEALTH_OLDEST_SECONDS_WARNING=300
QUEUE_HEALTH_FAILED_WARNING=1
```

Run migrations before starting workers:

```bash
php artisan migrate --force
```

The default Laravel migration already creates `jobs`, `job_batches`, and `failed_jobs`.

## Worker policy

Operational notifications are sent to the `notifications` queue with:

- 5 maximum attempts
- 30 second timeout
- fail on timeout
- backoff of 10, 60, 300, then 900 seconds
- dispatch after the surrounding database transaction commits

Recommended worker command:

```bash
php artisan queue:work database \
  --queue=notifications,default \
  --sleep=3 \
  --tries=5 \
  --timeout=30 \
  --max-time=3600
```

`DB_QUEUE_RETRY_AFTER` must remain greater than the worker timeout to avoid the same job being processed twice.

This queue also carries operational assignment push delivery for the driver app, so worker availability directly affects assignment notification timeliness.

## Supervisor

Copy the included config:

```bash
sudo cp deploy/supervisor/offroad-booking-worker.conf /etc/supervisor/conf.d/
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start offroad-booking-notifications:*
```

After every deployment:

```bash
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan queue:restart
```

Check processes:

```bash
sudo supervisorctl status
```

## Health and recovery

Human-readable health check:

```bash
php artisan queue:health
```

Machine-readable health check:

```bash
php artisan queue:health --json
```

The command exits with code 1 when pending, stale, or failed jobs reach their configured thresholds.

Inspect failed jobs:

```bash
php artisan queue:failed
```

Retry one failed job:

```bash
php artisan queue:retry <uuid>
```

Retry all failed jobs:

```bash
php artisan queue:retry all
```

Delete one failed job after investigation:

```bash
php artisan queue:forget <uuid>
```

Prune old failed jobs:

```bash
php artisan queue:prune-failed --hours=168
```

## Scheduler recommendation

Run Laravel scheduler every minute and add an external monitor around `queue:health --json`. The monitor should alert when the command exits non-zero rather than automatically deleting or retrying failed jobs without review.
