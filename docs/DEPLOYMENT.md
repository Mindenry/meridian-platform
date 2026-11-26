# Deployment

## 1. Local development

```bash
# API
cd apps/api
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate:fresh --seed
php artisan storage:link
php artisan serve --host=127.0.0.1 --port=8000

# Website and Studio (repository root, separate terminals)
pnpm install
pnpm --filter @meridian/website dev
pnpm --filter @meridian/studio dev
```

The website defaults to `http://localhost:3002`, Studio to
`http://localhost:3003`, and the API to `http://localhost:8000`. These are pinned
in `nuxt.config.ts`/`vite` scripts so they do not drift when port 3000 or 3001 is
already occupied.

## 2. Docker Compose stack

```bash
cp infrastructure/.env.example infrastructure/.env
docker compose -f infrastructure/compose.yaml up --build
```

| Service | Image / build | Port | Purpose |
| --- | --- | --- | --- |
| `postgres` | `postgres:17-alpine` | internal | Primary database |
| `redis` | `redis:7-alpine` | internal | Cache, sessions, queues |
| `meilisearch` | `getmeili/meilisearch:v1.12` | internal | Search index |
| `minio` | `bitnamilegacy/minio` | 9000, 9001 | S3-compatible media storage |
| `minio-init` | `bitnamilegacy/minio-client` | — | Creates the public `media` bucket |
| `api` | `infrastructure/docker/api` | internal 9000 | PHP-FPM, migrations, first-boot seed |
| `nginx` | `infrastructure/docker/nginx` | 8000 | API gateway and `/storage` |
| `queue` | same API image | — | `queue:work` |
| `scheduler` | same API image | — | `schedule:work` |
| `website` | `infrastructure/docker/website` | 3002 | Nuxt SSR |
| `studio` | `infrastructure/docker/studio` | 3003 | Static CMS |
| `reverb` | same API image, `realtime` profile | 8081 | WebSocket server (optional) |

MinIO uses Bitnami's legacy image namespace because the upstream `minio/minio`
and `quay.io/minio/minio` pulls were denied in this environment. The S3 API and
console are unchanged; replace the image if your registry provides current MinIO
builds.

First boot runs migrations and seeds the demo content once, tracked by
`storage/app/.demo-seeded`. It then rebuilds the Meilisearch index when
`SEARCH_DRIVER=meilisearch`. Set `SEED_DEMO=false` to start empty. After a bulk
import or content migration, rebuild the index explicitly:

```bash
docker compose -f infrastructure/compose.yaml exec api php artisan search:index
```

To enable Reverb:

```bash
docker compose -f infrastructure/compose.yaml --profile realtime up -d
```

and set `BROADCAST_CONNECTION=reverb` for the API services.

### Environment groups

| Variable | Purpose |
| --- | --- |
| `APP_KEY` | Laravel encryption key; rotate per environment |
| `DB_*` | PostgreSQL connection |
| `REDIS_*`, `CACHE_STORE`, `SESSION_DRIVER`, `QUEUE_CONNECTION` | Runtime state |
| `FILESYSTEM_DISK`, `MEDIA_DISK`, `AWS_*` | Object storage |
| `SEARCH_DRIVER`, `MEILISEARCH_*` | Search engine |
| `PUBLIC_SITE_URL`, `STUDIO_URL` | Canonical URL generation and notifications |
| `CORS_ALLOWED_ORIGINS`, `SANCTUM_STATEFUL_DOMAINS` | Browser trust boundaries |
| `SEED_DEMO`, `DEMO_MODE` | Demo behaviour and labelling |

`NUXT_PUBLIC_API_BASE` must be reachable from the **browser**, not just from the
website container. The Compose defaults use `http://localhost:8000/api/v1`,
which is correct for local review. In a real deployment it becomes the public API
hostname.

## 3. Production checklist

### Application

- [ ] Set `APP_ENV=production`, `APP_DEBUG=false`, and a unique `APP_KEY`.
- [ ] Serve only over HTTPS; set `SESSION_SECURE_COOKIE=true` and HSTS at the edge.
- [ ] Set `PUBLIC_SITE_URL` and `STUDIO_URL` to the real hosts.
- [ ] Restrict `SANCTUM_STATEFUL_DOMAINS` and `CORS_ALLOWED_ORIGINS` to real hosts.
- [ ] Disable `DEMO_MODE` for a real site and replace demo content.
- [ ] Change all demo passwords or remove seeded users.
- [ ] Run `php artisan config:cache route:cache view:cache` in the image or entrypoint.
- [ ] Ensure `storage` and `bootstrap/cache` are writable by PHP-FPM only.
- [ ] Keep `APP_KEY` in a secrets manager, not in Compose.

### Data and storage

- [ ] Use managed PostgreSQL with automated backups and PITR.
- [ ] Use managed Redis or a replicated deployment.
- [ ] Use private S3 + signed URLs, or a CDN in front of a public media bucket.
- [ ] Configure lifecycle rules for unused variants and uploads.
- [ ] Test restore, not only backup.
- [ ] Store CSP/security headers at the edge.

### Workers

- [ ] Run at least one queue worker and one scheduler instance.
- [ ] Add health checks for queue depth and scheduler heartbeats.
- [ ] Set `queue:work --tries=3 --timeout=90` and alert on failed jobs.
- [ ] Run `content:publish-scheduled` via `schedule:work` (Laravel schedules it).

### Search

- [ ] Decide whether Meilisearch is required; if so, run it with a master key and
  persistent volume.
- [ ] Index content after deploy and on publish (or run periodic reindex).
- [ ] Monitor index freshness; the API reports which engine served each query.

### Observability

- [ ] Ship application logs to a central store with retention.
- [ ] Add error tracking (Sentry or equivalent) and alerting.
- [ ] Monitor `/api/v1/health`, queue depth, scheduler ticks, disk, and 5xx rate.
- [ ] Track failed logins and 429 rates.
- [ ] Review audit logs for publishing and access changes.

## 4. Deploy sequence

1. Build and push images tagged with the commit SHA.
2. Run migrations as a one-shot job (`php artisan migrate --force`) before
   switching traffic.
3. Deploy the new API, then the queue and scheduler, then the website and Studio.
4. Warm caches and verify `/api/v1/health`.
5. Smoke test: home in three locales, search, one admin login, sitemap.
6. Keep the previous image tag for rollback. Migrations should be backwards
   compatible for one release (add columns, backfill, then remove in a later
   release).

## 5. Backups

- **PostgreSQL:** nightly `pg_dump -Fc`, plus provider PITR. Restore into a
  staging database monthly.
- **Media:** bucket versioning and cross-region replication for production.
- **Secrets:** backed up in the secret manager, never in the repository.
- **Configuration:** Compose files and environment templates are versioned; real
  `.env` files are not.

## 6. Scaling notes

- The API is stateless apart from Redis/DB/S3, so it scales horizontally behind a
  load balancer.
- PHP-FPM workers should be sized to CPU and memory; image variant generation is
  the most expensive request path and can move to a queue for large uploads.
- The queue worker can scale independently. Use Redis or SQS, not `sync`.
- The website can run multiple Node processes; Nuxt SSR has no local state.
- Studio is static and can be served from a CDN.
- Meilisearch scales vertically first; for very large corpora consider a managed
  search service.

## 7. Rollback

- Re-deploy the previous image tag.
- Do not roll back destructive migrations automatically; restore from backup or
  write a forward fix.
- Scheduled publications and audit logs are additive and safe across a rollback.

## 8. CI

`.github/workflows/ci.yml` runs:

- PHP tests and Pint on PHP 8.5.
- Composer audit.
- Website and Studio typecheck and production builds.
- The Playwright suite against locally started API, website, and Studio, with
  failure artifacts uploaded.

Docker images are built by Compose locally; a production pipeline should push
them to a registry and deploy by digest.
