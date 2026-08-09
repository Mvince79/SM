# D-05 Load Test Result

Status: **Scripts verified locally; production/VPS measurements pending.**

The acceptance result must be recorded from the deployed Linux VPS, behind the
real Nginx/PHP-FPM/MySQL/Redis stack. Laravel's Windows development server is
single-process and is not a valid source for production throughput numbers.

## Local script smoke test — 06/08/2026

k6 v2.1.0 was checksum-verified and used against a disposable, migrated SQLite
database with array cache/session. This run validates script syntax, endpoint
contracts, checks, thresholds, and summary-file generation only.

| Metric | Local smoke result |
|---|---:|
| Concurrent one-shot VUs | 3 |
| Completed load requests | 3/3 |
| Throughput | 1.68 req/s |
| HTTP error rate | 0.00% |
| Checks passed | 100.00% |
| List page p95 | 621.35 ms |
| Map API p95 | 301.69 ms |
| Search API p95 | 878.36 ms |

These numbers are **not** a production pass: they were produced by the
single-process Laravel development server with only demo seed data.

## Test contract

| Workload | Load model | Acceptance |
|---|---:|---:|
| `/missing` | 20 concurrent one-shot VUs | p95 < 1000 ms |
| `/api/v1/map-data` | 20 concurrent one-shot VUs | p95 < 1000 ms |
| `/api/v1/missing-children?q=Nguyễn` | 10 concurrent one-shot VUs | p95 < 1000 ms |
| Public burst total | 50 concurrent requests | HTTP errors < 1%; no HTTP 500 |
| AI queue trigger | 10 concurrent demo jobs | enqueue p95 < 1000 ms; queue drains without failed jobs/OOM |

The public API limiter allows 60 requests per minute per source IP. The script
uses three warm-up requests followed by exactly 50 load requests, so it tests a
50-VU burst without manufacturing HTTP 429 responses. Wait at least 60 seconds
before repeating the run from the same load generator.

## Authorized run procedure

Run k6 from a different machine than the VPS so the load generator does not
consume the CPU/RAM being measured. Confirm the target is the team's own test
or production domain before using `CONFIRM_TARGET=D-05`.

```bash
cd /path/to/SM
k6 run \
  -e BASE_URL=https://YOUR_DOMAIN \
  -e CONFIRM_TARGET=D-05 \
  scripts/load-test/map-data.js
```

The run writes raw JSON to `storage/logs/k6-public-summary.json` and a compact
Markdown table to `storage/logs/k6-public-summary.md`. Copy the generated table
into the **Measured results** section below; do not commit raw logs.

At the same time on the VPS, record peak CPU and memory:

```bash
htop
watch -n 1 'free -m; printf "\n"; ps -eo pid,comm,%cpu,%mem --sort=-%cpu | head -n 12'
```

For the AI queue test, use only a synthetic/demo photo that already has a face
encoding. Export the internal key without putting it in the command line or
repository:

```bash
cd /var/www/timtre
read -rsp 'Internal key: ' K6_INTERNAL_KEY && export K6_INTERNAL_KEY
printf '\n'
k6 run \
  -e BASE_URL=http://127.0.0.1 \
  -e PHOTO_ID=DEMO_PHOTO_ID \
  scripts/load-test/ai-queue.js
unset K6_INTERNAL_KEY
php artisan horizon:status
php artisan horizon:supervisors
```

Open `/horizon` during the run. Record the peak `face-match` queue length, time
until it returns to zero, failed-job count, and VPS peak RAM. The one-worker AI
supervisor is intentional; queued work should drain sequentially without
loading multiple ArcFace model copies.

## Measured results

| Metric | Result | Acceptance | Status |
|---|---:|---:|---:|
| Total public load requests | Pending VPS run | 50 | Pending |
| Throughput | Pending VPS run | Record RPS | Pending |
| HTTP error rate | Pending VPS run | < 1% and no HTTP 500 | Pending |
| List page p95 | Pending VPS run | < 1000 ms | Pending |
| Map API p95 | Pending VPS run | < 1000 ms | Pending |
| Search API p95 | Pending VPS run | < 1000 ms | Pending |
| Peak VPS CPU | Pending VPS run | No sustained saturation | Pending |
| Peak VPS RAM | Pending VPS run | No OOM/swap storm | Pending |
| AI queue drain time | Pending VPS run | Queue returns to zero | Pending |
| AI failed jobs | Pending VPS run | 0 | Pending |

## Bottleneck notes and follow-up

Record whether the limiting component is Nginx/PHP-FPM, MySQL, Redis, payload
size from map markers, or the single AI worker. Attach relevant `htop`, Horizon,
and application-log timestamps. Any result over the threshold must become a
tracked post-launch item with the measured endpoint, p95, error rate, and peak
resource usage—not a generic “performance is slow” note.
