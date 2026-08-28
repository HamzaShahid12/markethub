# MarketHub — Deployment Guide

Covers spec section 18's deployment plan, adapted to what this project
actually needs: a Linux server (or managed Laravel host), MySQL, a
queue worker, and — since Phase 9 — a running Reverb process.

## 1. Server / hosting

Any of these work; pick based on how hands-on you want to be:

- **Managed Laravel hosting** (Laravel Forge + a DigitalOcean/Hetzner/
  AWS box, or Laravel Cloud) — handles Nginx, PHP-FPM, SSL renewal,
  and queue/scheduler daemons through their dashboard. Fastest path
  for a portfolio deploy.
- **Plain Ubuntu/Debian VPS** — full manual control, see §7 for the
  Supervisor config this repo ships for the queue worker + Reverb.
- **Docker** — optional per spec section 18; not included in this
  overlay since it adds real complexity (multi-container Nginx +
  PHP-FPM + MySQL + Redis + Reverb) that's genuinely optional for a
  portfolio deploy. If you want it, the shape is: one `app` container
  (PHP-FPM), one `web` container (Nginx or Caddy), one `db` container
  (MySQL), one `queue` container (`php artisan queue:work`), one
  `reverb` container (`php artisan reverb:start`) — all sharing the
  same codebase volume and `.env`.

## 2. Database

- Production MySQL 8+ (matches the `varchar`/`json`/enum column types
  Phase 1's migrations use).
- Create a dedicated database user with privileges scoped to the
  `markethub` database only — never the app's DB user as MySQL root.
- Run migrations as part of every deploy: `php artisan migrate --force`
  (`--force` is required to run migrations in production without the
  interactive confirmation prompt).
- Do **not** run `--seed` in production — the seeder creates demo
  accounts with a known password (`password`). If you want the catalog
  seeded once for a live demo, run it manually the first time only,
  then immediately change or delete the demo account passwords.

## 3. HTTPS / SSL

- Free via Let's Encrypt (`certbot`) on a plain VPS, or automatic on
  Forge/Laravel Cloud/most PaaS hosts.
- Set `APP_URL=https://yourdomain.com` in `.env` — Laravel uses this
  to generate absolute URLs (password reset links, notification mail
  links from Phase 8, the `asset()` calls for product images).
- Reverb needs its own TLS termination if exposed directly, or put it
  behind the same reverse proxy as the app with a `wss://` upgrade —
  see §6.

## 4. Environment variables

**Never commit `.env`** — `.gitignore` in this repo already excludes
it. Set these directly on the server (via your host's dashboard, or a
`.env` file with restrictive permissions: `chmod 600 .env`):

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=markethub
DB_USERNAME=markethub_app
DB_PASSWORD=<strong, generated, not reused elsewhere>

SESSION_DRIVER=database
QUEUE_CONNECTION=database   # or redis — see Phase 11's README section
CACHE_STORE=database        # or redis

MAIL_MAILER=smtp            # point at a real transactional mail provider
MAIL_HOST=...
MAIL_USERNAME=...
MAIL_PASSWORD=...

BROADCAST_CONNECTION=reverb
REVERB_APP_ID=<generate a real one>
REVERB_APP_KEY=<generate a real one>
REVERB_APP_SECRET=<generate a real one>
REVERB_HOST=yourdomain.com
REVERB_PORT=443
REVERB_SCHEME=https

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

`APP_DEBUG=false` matters more than it looks — with it `true` in
production, an unhandled exception dumps a full stack trace (including
`.env` values) to any visitor. This is the single most important line
in this list.

## 5. Queue worker (required)

Every phase from 8 onward depends on a running queue worker — order
confirmation emails, vendor new-order alerts, low-stock notifications,
status-update notifications, and chat message notifications are all
queued jobs. Without a worker process, they silently sit in the `jobs`
table forever.

```bash
php artisan queue:work --tries=3 --backoff=10
```

Run this as a persistent daemon (see §7's Supervisor config), not a
one-off command — it needs to survive server reboots and restart
automatically if the PHP process dies mid-job.

## 6. Reverb (required for Phase 9's real-time features)

```bash
php artisan reverb:start --host=0.0.0.0 --port=8080
```

Behind a reverse proxy (Nginx/Caddy), proxy WebSocket upgrade requests
to this port. Nginx example:

```nginx
location /app {
    proxy_pass http://127.0.0.1:8080;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "upgrade";
    proxy_set_header Host $host;
}
```

Without this running, the app still works — the bell badge and chat
just fall back to next-page-load instead of updating live, since
nothing in the request/response cycle depends on the socket being up.

## 7. Supervisor config

`deploy/supervisor-worker.conf` and `deploy/supervisor-reverb.conf` in
this repo are ready to drop into `/etc/supervisor/conf.d/` on a plain
VPS (adjust the `directory` and `user` values first) — see those files
for the exact config.

```bash
sudo cp deploy/supervisor-*.conf /etc/supervisor/conf.d/
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start markethub-worker:*
sudo supervisorctl start markethub-reverb:*
```

## 8. Scheduler (recurring tasks)

Nothing in this build currently registers a scheduled task, but the
cron entry costs nothing to have ready for when one is added (e.g. a
future "clean up expired coupons" or "digest email" job):

```
* * * * * cd /path/to/markethub && php artisan schedule:run >> /dev/null 2>&1
```

## 9. Storage & uploads

Product images, vendor logos/banners, and any future uploads go to
the `public` disk (`Storage::disk('public')`, used throughout Phases
3 and 6). Every deploy needs:

```bash
php artisan storage:link
```

This symlinks `public/storage` → `storage/app/public` so uploaded
files are web-accessible. On most hosts this survives deploys as long
as `storage/` itself is a persistent volume/directory, not wiped on
each release — confirm this explicitly if using a platform that
rebuilds the filesystem per deploy (in that case, point the `public`
disk at S3-compatible object storage instead; Laravel's filesystem
config makes this a config change, not a code change).

## 10. Production asset build

```bash
npm ci
npm run build
```

`npm ci` (not `npm install`) for reproducible builds from
`package-lock.json`. Vite's production build goes to `public/build/`
— Laravel's `@vite` directive (already in the app's root Blade layout
from the Breeze scaffold) automatically serves the built, hashed
assets instead of the dev server once `APP_ENV=production`.

## 11. Error logging & monitoring

- `LOG_CHANNEL=stack` (Laravel default) writes to `storage/logs/laravel.log`
  — fine for a small deploy, but rotate it (`logrotate`) so it doesn't
  grow unbounded.
- For anything beyond a portfolio demo, wire up a real error tracker
  (Sentry, Flare, or Bugsnag all have a Laravel package that's a
  `composer require` + a few `.env` keys) — nothing in this codebase
  needs to change to add one.

## 12. One-shot deploy checklist

```bash
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link   # first deploy only
sudo supervisorctl restart markethub-worker:* markethub-reverb:*
```

`config:cache`/`route:cache`/`view:cache` are production-only —
running them locally in development actively breaks `.env` hot-reload
and route changes, so keep them out of your local workflow.
