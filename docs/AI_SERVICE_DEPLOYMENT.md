# AI Service deployment (A-04)

The FastAPI/DeepFace service must only listen on `127.0.0.1:8001`. It is an
internal process used by Laravel, not a public API.

## 1. Prepare the service account and directories

Run after VPS hardening (A-01) and after the repository exists at
`/var/www/timtre`:

```bash
sudo install -d -o www-data -g www-data /var/lib/timtre-ai/.deepface/weights
sudo install -d -o www-data -g www-data /var/log/timtre-ai
cd /var/www/timtre/ai-service
python3.11 -m venv venv
./venv/bin/python -m pip install --upgrade pip wheel
./venv/bin/python -m pip install -r requirements.txt
cp .env.production.example .env
sudo chown www-data:www-data .env
sudo chmod 600 .env
```

Generate a unique secret without printing it into shell history where possible.
Set the same value as `INTERNAL_KEY` in `ai-service/.env` and
`FACE_MATCH_INTERNAL_KEY` in Laravel `.env`. Do not commit either file.

Required production values:

```dotenv
APP_ENV=production
BIND_HOST=127.0.0.1
BIND_PORT=8001
WARMUP_ON_STARTUP=true
MAX_CONCURRENT_INFERENCES=1
```

Laravel must use:

```dotenv
FACE_MATCH_SERVICE_URL=http://127.0.0.1:8001
FACE_MATCH_INTERNAL_KEY=<same unique secret>
FACE_MATCH_QUEUE=face-match
```

## 2. Preflight and download model weights

Run both commands as the same OS user and with the same `HOME` used by
Supervisor. This ensures ArcFace weights are cached under
`/var/lib/timtre-ai/.deepface/weights` and are reused after restart.

```bash
cd /var/www/timtre/ai-service
sudo -u www-data HOME=/var/lib/timtre-ai ./venv/bin/python scripts/preflight.py
sudo -u www-data HOME=/var/lib/timtre-ai ./venv/bin/python warmup.py
```

The first warm-up can take several minutes because it may download model
weights. Do not start Supervisor until both commands finish successfully.

## 3. Install Supervisor configuration

```bash
sudo cp /var/www/timtre/docs/supervisor-timtre-ai.conf.example /etc/supervisor/conf.d/timtre-ai.conf
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl status timtre-ai
```

Expected status: `RUNNING`. The command intentionally uses one Uvicorn worker;
each additional worker would load another model copy into RAM.

## 4. Verify locally on the VPS

```bash
cd /var/www/timtre/ai-service
sudo -u www-data HOME=/var/lib/timtre-ai ./venv/bin/python scripts/smoke_test.py
sudo ss -ltnp | grep ':8001'
```

After deploying a synthetic/demo face image to the VPS, also exercise real
inference (never use a private KYC image for an operations smoke test):

```bash
sudo -u www-data HOME=/var/lib/timtre-ai ./venv/bin/python scripts/smoke_test.py --image /path/to/synthetic-face.jpg
```

`ss` must show `127.0.0.1:8001`, never `0.0.0.0:8001` or `[::]:8001`.

Verify Laravel uses the service:

```bash
cd /var/www/timtre
php artisan config:clear
php artisan horizon:terminate
php artisan tinker --execute="dump(app(App\\Services\\FaceMatchService::class)->isHealthy());"
```

## 5. Verify the port is not public

From a different computer on the internet (not from the VPS), run:

```bash
curl --connect-timeout 5 http://VPS_PUBLIC_IP:8001/health
```

The request must fail with connection refused or timeout. Also verify UFW only
allows SSH, HTTP and HTTPS:

```bash
sudo ufw status numbered
```

Do not add an allow rule for port 8001 and do not proxy this service through
Nginx.

## 6. Crash/restart test

```bash
sudo supervisorctl pid timtre-ai
sudo kill -9 "$(sudo supervisorctl pid timtre-ai)"
sleep 35
sudo supervisorctl status timtre-ai
cd /var/www/timtre/ai-service
sudo -u www-data HOME=/var/lib/timtre-ai ./venv/bin/python scripts/smoke_test.py
```

Supervisor must return the service to `RUNNING`, and both `/health` and
`/ready` must return HTTP 200.

## Rollback

```bash
sudo supervisorctl stop timtre-ai
cd /var/www/timtre
git checkout <previous-release-tag>
sudo -u www-data HOME=/var/lib/timtre-ai ./ai-service/venv/bin/python ai-service/scripts/preflight.py
sudo supervisorctl start timtre-ai
```
