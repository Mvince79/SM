# Error tracking and uptime monitoring (F-01)

The repository side is ready. Complete the account-level steps below after the
production domain and the four team email addresses are available. Do not commit
the Sentry DSN, UptimeRobot API key, or team email addresses.

## 1. Configure Sentry

Create a PHP/Laravel project in Sentry, select the free plan, and copy its DSN to
the production `.env`:

```dotenv
SENTRY_LARAVEL_DSN=https://PUBLIC_KEY@SENTRY_HOST/PROJECT_ID
SENTRY_ENVIRONMENT=production
SENTRY_RELEASE=v5.0-sprint5
SENTRY_SAMPLE_RATE=1.0
SENTRY_TRACES_SAMPLE_RATE=0.0
SENTRY_PROFILES_SAMPLE_RATE=0.0
SENTRY_SEND_DEFAULT_PII=false
```

Then reload cached configuration and long-running processes:

```bash
cd /var/www/timtre
php artisan config:clear
php artisan config:cache
php artisan horizon:terminate
sudo supervisorctl restart timtre-reverb timtre-ai
php artisan sentry:test
```

The SDK captures unhandled exceptions. Normal `404`/model-not-found exceptions
are ignored, performance traces are disabled by default, and PII/SQL bindings
remain disabled. This protects KYC data and preserves the free event quota.

In Sentry, create an issue alert for **a new issue** in the `production`
environment. Add the team's shared email distribution list or the four verified
members. Add Slack only if the project already has an approved workspace; do not
place Slack credentials in Laravel.

## 2. Verify a real handled HTTP 500

Run this only during a maintenance window. It registers an in-memory route in a
single Tinker process, sends an internal request through Laravel's HTTP kernel,
and does not expose a permanent public test endpoint:

```bash
php artisan tinker --execute="Illuminate\\Support\\Facades\\Route::get('/__ops/sentry-500', fn () => throw new RuntimeException('F-01 production 500 verification')); \$response = app()->handle(Illuminate\\Http\\Request::create('/__ops/sentry-500', 'GET')); dump(\$response->getStatusCode());"
```

Expected output is `500`. Confirm the issue appears in Sentry with environment
`production`, release `v5.0-sprint5`, and a stack trace. Confirm the four team
members receive the new-issue alert. Close the Tinker process; the temporary
route then ceases to exist.

Also request a random missing path and confirm it returns `404` without creating
a Sentry issue:

```bash
curl -i https://YOUR_DOMAIN/__f01-missing-route
```

## 3. Configure UptimeRobot

Create one HTTP(S) monitor using the public framework health endpoint:

- Friendly name: `TimTre production health`
- URL: `https://YOUR_DOMAIN/health`
- Interval: `5 minutes`
- Expected HTTP status: `200`
- SSL expiry monitoring: enabled when available on the selected plan

The endpoint is intentionally public and performs no database write. Attach all
four verified email alert contacts to this monitor. Keep the API key outside the
repository if the monitor is created through the API.

Verify before the downtime drill:

```bash
curl --fail --silent --show-error https://YOUR_DOMAIN/health
```

## 4. Downtime notification drill

Schedule the drill with all four recipients. This is a real outage test:

```bash
sudo systemctl stop nginx
```

Wait for UptimeRobot's five-minute check and confirm that all four recipients
receive the DOWN alert within 5-10 minutes. Restore service immediately after
the alert:

```bash
sudo systemctl start nginx
sudo systemctl is-active nginx
curl --fail --silent --show-error https://YOUR_DOMAIN/health
```

Confirm all four recipients also receive the UP/recovery alert. Record the UTC
time, detection delay, recovery delay, Sentry issue URL, and the four recipient
confirmations in the deployment log. Never run this drill without an agreed
maintenance window.
