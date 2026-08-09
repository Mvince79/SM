# Laravel Horizon operations (C-05)

Horizon is the only production worker manager for this application. Do not run
`queue:work` or install the legacy `supervisor-face-match.conf.example` beside
Horizon, because two worker managers can consume the same jobs concurrently.

## 1. VPS prerequisites

Run these checks on the Linux VPS from `/var/www/timtre`:

```bash
php -m | grep -E 'pcntl|posix|redis'
redis-cli ping
composer install --no-dev --optimize-autoloader
php artisan horizon:install --no-interaction
```

Expected: all three PHP extensions are listed and Redis returns `PONG`.
Horizon cannot run natively on Windows because `pcntl` and `posix` are Linux
process-control extensions; Windows remains suitable for the test suite and the
existing `composer dev` worker.

Production `.env` must include:

```dotenv
APP_ENV=production
QUEUE_CONNECTION=redis
REDIS_CLIENT=phpredis
REDIS_QUEUE_RETRY_AFTER=180
REDIS_QUEUE_BLOCK_FOR=5
FACE_MATCH_QUEUE=face-match
HORIZON_GENERAL_MIN_PROCESSES=1
HORIZON_GENERAL_MAX_PROCESSES=4
HORIZON_FACE_MATCH_PROCESSES=1
HORIZON_WORKER_TIMEOUT=150
```

The 180-second Redis retry window must remain greater than the 150-second
Horizon worker timeout and the 120-second AI/eKYC job timeout. Keep exactly one
`face-match` worker unless the AI service and available RAM are deliberately
scaled together.

## 2. Install Supervisor

First disable any old standalone queue worker without deleting its config:

```bash
sudo supervisorctl stop sm-face-match-worker:* || true
sudo mv /etc/supervisor/conf.d/sm-face-match-worker.conf \
  /etc/supervisor/conf.d/sm-face-match-worker.conf.disabled 2>/dev/null || true
sudo install -d -o www-data -g www-data /var/log/timtre
sudo cp docs/supervisor-timtre-horizon.conf.example \
  /etc/supervisor/conf.d/timtre-horizon.conf
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl status timtre-horizon
```

If `command -v php` is not `/usr/bin/php`, edit the `command` line before
running `supervisorctl update`. Expected Horizon status is `RUNNING`.

Also keep the Laravel scheduler cron active; `routes/console.php` records a
Horizon metrics snapshot every five minutes in production/staging.

## 3. Access control acceptance test

Open `https://YOUR_DOMAIN/horizon`:

- an active `ADMIN` or `MODERATOR` account receives the dashboard;
- a regular `USER`, banned administrator, or guest receives HTTP 403;
- the same authorization protects every `/horizon/api/*` endpoint, including
  the retry action.

## 4. Failed-job and retry acceptance test

This repository includes a controlled job that fails exactly once and succeeds
after a dashboard retry. It does not touch business data.

```bash
php artisan ops:horizon-smoke-failure --force
```

Copy the printed run UUID. In Horizon, open **Failed Jobs**, locate the tag
`run:<UUID>`, and confirm the exception says `Intentional Horizon smoke-test
failure`. Click **Retry** once. The retried job must move to **Completed Jobs**;
`storage/logs/laravel.log` records `Horizon smoke-test job completed after
retry` with the same UUID.

Useful CLI checks:

```bash
php artisan horizon:status
php artisan horizon:supervisors
php artisan queue:failed
sudo supervisorctl status timtre-horizon
```

## 5. Deployment and restart

After each release:

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize
php artisan horizon:terminate
sudo supervisorctl status timtre-horizon
```

`horizon:terminate` lets the current workers finish and exit; Supervisor starts
a fresh master with the new code. `stopwaitsecs=3600` is intentionally longer
than the longest expected job.

## 6. Rollback

```bash
php artisan horizon:pause
git checkout <previous-release-tag>
composer install --no-dev --optimize-autoloader
php artisan optimize
php artisan horizon:terminate
php artisan horizon:continue
sudo supervisorctl status timtre-horizon
```

If Redis is unavailable, restore Redis first. Do not fall back to a second
worker manager while the Horizon Supervisor program is still running.
