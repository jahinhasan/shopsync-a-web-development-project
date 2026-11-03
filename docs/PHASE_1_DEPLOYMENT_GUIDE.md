# ShopSync — Phase 1 Deployment Guide

**Target:** Render (Docker runtime) with a MySQL-compatible TiDB Cloud database.
This guide documents the current deployment path end-to-end, the environment variables
it needs (no secrets), and the procedures for building, deploying, checking health, and
rolling back. It is written for this repository's actual configuration —
`Dockerfile`, `docker/start.sh`, `docker/nginx.conf`, `docker/php-prod.ini`.

---

## 1. Production Environment Requirements

| Component | Requirement |
|---|---|
| Runtime | Render **Docker** service (the repo image is PHP 8.3-FPM + nginx-light) |
| Database | MySQL-compatible TiDB Cloud instance (already provisioned) |
| PHP | 8.3 platform-pinned via `composer-production.lock` |
| Node | 22.x (build stage only — assets never ship in runtime) |
| Port | Render assigns `$PORT` dynamically; the container rewrites nginx config at boot |
| Persistence | **None by default** — see §6 for uploads |

The image refuses to start without `APP_KEY` (`docker/start.sh` exits with an error), so
a missing key is caught at deploy time, not at first request.

## 2. Environment Variables (Render dashboard)

Set these in the Render service's *Environment* tab. **Never commit real values** — this
list intentionally contains names only. Local `.env` is not used in production and is
excluded from image builds by `.dockerignore`.

### Application (required)

| Variable | Notes |
|---|---|
| `APP_KEY` | **Required.** Use the existing application key — regenerating invalidates encrypted values. `start.sh` fails fast if unset. |
| `APP_ENV` | `production` |
| `APP_DEBUG` | **Must be `false`.** Debug pages expose stack traces and configuration. |
| `APP_URL` | The public https URL (used for asset/`storage` URL generation) |
| `APP_LOCALE` / `APP_FALLBACK_LOCALE` | `en` |

### Database (required — TiDB Cloud)

| Variable | Notes |
|---|---|
| `DB_CONNECTION` | `mysql` |
| `DB_HOST` | TiDB Cloud endpoint hostname |
| `DB_PORT` | Usually `4000` for TiDB Cloud |
| `DB_DATABASE` | Database name |
| `DB_USERNAME` / `DB_PASSWORD` | TiDB credentials — keep out of logs and reports |

The `pdo_mysql` extension is compiled into the image; sessions, cache and queue tables
live in the same database.

### Sessions & security (required for correctness on HTTPS)

| Variable | Notes |
|---|---|
| `SESSION_SECURE_COOKIE` | **Set `true`.** Render terminates TLS; without this the session cookie is not marked `Secure`. |
| `SESSION_SAME_SITE` | `lax` (default; do not relax to `none` without a reason) |
| `SESSION_ENCRYPT` | `false` is acceptable; `true` adds defense in depth |

### Mail (required for password reset)

Password reset sends email through Laravel's mailer. The current production default
(`MAIL_MAILER=log`) **writes emails to the container log instead of delivering them** —
users cannot reset passwords until a real mailer is configured:

| Variable | Notes |
|---|---|
| `MAIL_MAILER` | `smtp` |
| `MAIL_HOST` / `MAIL_PORT` | Your SMTP provider's endpoint (e.g. `587`) |
| `MAIL_USERNAME` / `MAIL_PASSWORD` | SMTP credentials (secret) |
| `MAIL_SCHEME` | `smtp` (or `smtps` per provider) |
| `MAIL_FROM_ADDRESS` / `MAIL_FROM_NAME` | The visible sender |

Email verification is intentionally **not** part of the application (removed in Phase 1);
mail is needed only for password reset.

### Payments (optional, feature-flag by absence)

| Variable | Notes |
|---|---|
| `SHOPSYNC_BKASH_NUMBER` | bKash merchant number shown at checkout. If unset, bKash is rejected at checkout with a clear message — COD keeps working. |
| `SHOPSYNC_NAGAD_NUMBER` | Same behavior for Nagad. |

### Real-time chat (optional)

Reverb variables (`REVERB_APP_KEY`, `REVERB_HOST`, …) enable live chat. Without them the
UI falls back to refresh-to-see-messages (documented in the README; the Vite build bakes
placeholder values so the bundle never breaks).

### Uploads (see §6)

| Variable | Notes |
|---|---|
| `FILESYSTEM_PUBLIC_ROOT` | Optional absolute path of a mounted persistent volume for uploads. **Unset = `storage/app/public` inside the container** (ephemeral on Render). |

## 3. Docker Build & Deploy Procedure

Build (locally, optional — Render builds from the `Dockerfile` directly):

```bash
docker build -t shopsync:local .
# Local smoke test against your local database:
docker compose up --build -d
curl http://127.0.0.1:8080/up
docker compose logs -f app
docker compose down
```

Deploy:

1. Push the branch to GitHub and merge.
2. Render builds the image (multi-stage: npm ci + Vite build → PHP runtime with
   production Composer install from `composer-production.lock` — no dev packages).
3. At container start, `docker/start.sh`:
   - verifies `APP_KEY`,
   - rewrites nginx's `__PORT__` with Render's `$PORT`,
   - creates `public/storage` if missing (`storage:link`),
   - runs `php artisan config:cache`,
   - starts php-fpm + nginx under a supervisor loop.
4. Render's health check hits `/up`.

### Database migrations

The container **never migrates automatically** — this is deliberate so a deploy can never
alter the production schema unattended. To apply a schema change:

```bash
# From a shell against the production environment (Render shell or a one-off job):
php artisan migrate --force
```

Back up the database first (TiDB Cloud console export / `mysqldump`). Never run
`migrate:fresh`, `db:wipe`, or `db:seed` against production — they destroy data.

## 4. Storage Setup

Two disks matter:

- **`local` (private)** — shop-application identity documents and evidence. Root:
  `storage/app/private`. Never served directly; downloads stream through
  admin-authorized controller endpoints. Fine on the container filesystem *only if* you
  accept losing pending applications on redeploy — prefer the same persistent volume as
  below if applications are actively reviewed in production.
- **`public` (uploads)** — product images at `products/…`, served by nginx under
  `/storage/…` with `nosniff` and long cache. Root: `FILESYSTEM_PUBLIC_ROOT` if set,
  else `storage/app/public`.

### Making uploads persistent (Render disk)

Render's container filesystem is **ephemeral**: every redeploy or restart wipes anything
uploaded after the build. The seeded product images are baked into the image, so the
catalog survives, but **owner/customer uploads do not**. To fix:

1. In the Render service, add a **Disk** (e.g. 5 GB) mounted at
   `/var/data/shopsync-uploads`.
2. Set `FILESYSTEM_PUBLIC_ROOT=/var/data/shopsync-uploads/app/public`.
3. Set `FILESYSTEM_PRIVATE_ROOT`-equivalent if you want documents persistent too:
   currently the private disk root is fixed to `storage/app/private`; extending it with
   the same env pattern is a one-line follow-up if needed.
4. Redeploy. `storage:link` (run by `start.sh`) now links `public/storage` to the mounted
   path, and Laravel writes new uploads there.
5. **Migrate existing uploads once** by copying them into the mounted path before the
   switch (from the old container or from a fresh export).

No external storage provider (S3 etc.) is used or required by this setup; adopt one only
with explicit approval.

## 5. Health Checks

| Check | Endpoint / mechanism |
|---|---|
| Render | `GET /up` (Laravel health route) |
| Docker | `HEALTHCHECK` in the Dockerfile curls `/up` every 30 s (5 s timeout, 20 s start period, 3 retries) |
| Container | `start.sh` exits if php-fpm **or** nginx dies — Render restarts the service |
| Logs | Everything logs to stdout/stderr (nginx, PHP-FPM, Laravel `stderr`); read via `docker compose logs` locally or the Render **Logs** tab |

A failing `/up` after a deploy usually means: `APP_KEY` unset, TiDB unreachable
(credentials/security-group), or a cached-config mismatch — check the logs first.

## 6. Rollback Procedure

- **Code rollback:** Render → *Deployments* → redeploy the previous successful build
  (or `git revert` the offending commit and push). The image is immutable, so rollback is
  a redeploy, not a repair.
- **Config rollback:** revert the changed environment variable in the Render dashboard
  and redeploy (config is cached at boot, so a restart/redeploy is required).
- **Database rollback:** schema changes are manual (§3), so a code rollback never needs
  a schema rollback. If a migration must be reversed, restore from the pre-migration
  backup — do not improvise destructive SQL on production data.
- **Uploads:** live only on the mounted disk (if configured, §4) — redeploys do not
  affect them; container restarts do, until the disk is attached.

## 7. Production Checklist (one-time)

- [ ] `APP_KEY` set (existing key), `APP_ENV=production`, `APP_DEBUG=false`
- [ ] `APP_URL` is the public https URL
- [ ] TiDB credentials set; `php artisan migrate --force` status known
- [ ] `SESSION_SECURE_COOKIE=true`
- [ ] SMTP configured (password reset) — send one reset email to confirm delivery
- [ ] `SHOPSYNC_BKASH_NUMBER` / `SHOPSYNC_NAGAD_NUMBER` set, or deliberately omitted
- [ ] Reverb provisioned, or refresh-fallback accepted
- [ ] Render disk attached and `FILESYSTEM_PUBLIC_ROOT` set (uploads persistence)
- [ ] `/up` returns 200 and the logs show no errors
- [ ] A test checkout, a test registration, and a test password reset pass end-to-end
