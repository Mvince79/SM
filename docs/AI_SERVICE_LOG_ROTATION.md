# AI service log rotation and restart alert (F-06)

This is a VPS operations task. It applies only after A-04 has deployed the
`timtre-ai` Supervisor program. The AI service remains bound to loopback; no
port, Nginx, or firewall change is needed.

## Install the configuration

```bash
sudo install -d -o www-data -g www-data -m 0750 /var/log/timtre-ai /var/lib/timtre-ai
sudo cp /var/www/timtre/docs/logrotate-timtre-ai.example /etc/logrotate.d/timtre-ai
sudo install -o root -g root -m 0755 \
  /var/www/timtre/scripts/monitoring/timtre-ai-restart-alert \
  /usr/local/bin/timtre-ai-restart-alert
sudo cp /var/www/timtre/docs/supervisor-timtre-ai-alert.conf.example \
  /etc/supervisor/conf.d/timtre-ai-restart-alert.conf
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl restart timtre-ai
```

The `timtre-ai` program must use the updated
`docs/supervisor-timtre-ai.conf.example`: native Supervisor rotation is disabled
and logrotate is the only rotator. The policy runs daily, retains seven dated
copies, and compresses older copies. `copytruncate` lets Uvicorn continue to
write the current file without a process restart.

To deliver a remote alert, set `AI_ALERT_WEBHOOK_URL` as an environment variable
on the event-listener program before `supervisorctl update`. It is deliberately
optional: without it, the alert is still written through syslog with priority
`daemon.err`, where the VPS monitoring stack can collect it. Do not put a secret
webhook URL in this repository.

## Verify

```bash
sudo logrotate -d /etc/logrotate.d/timtre-ai
sudo logrotate -f /etc/logrotate.d/timtre-ai
sudo ls -lh /var/log/timtre-ai/
sudo supervisorctl status timtre-ai timtre-ai-restart-alert
```

Run the forced rotation once only for verification. Subsequent runs are handled
by the system logrotate timer. Confirm that `service.log` stays writable by
`www-data` and an archived `service.log-YYYYMMDD` file exists; after the next
rotation, the prior archive is compressed.

## Crash-recovery drill

Perform this on the VPS during a maintenance window. It intentionally interrupts
the AI process, so do not run it while a real face-match job is in progress.

```bash
for attempt in 1 2 3; do
  sudo kill -9 "$(sudo supervisorctl pid timtre-ai)"
  sleep 35
  sudo supervisorctl status timtre-ai
done
sudo journalctl -t timtre-ai-restart-alert --since '10 minutes ago'
```

Each status must return `RUNNING`. Three unexpected exits within five minutes
write exactly one alert for the incident window; the marker prevents notification
spam. Supervisor's `startretries=3` and default backoff prevent a tight restart
loop. After the test, verify readiness with the A-04 smoke test.
