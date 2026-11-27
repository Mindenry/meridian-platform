# Deployment

The public website and Studio are static/SSR front ends and fit Netlify. The API
is PHP and cannot run on Netlify; host it on a VPS, Fly.io, Render, Railway, or
any container platform with PostgreSQL, Redis, object storage and Meilisearch.

## 1. Production topology

```
www.example.com        Netlify   Nuxt SSR (Netlify Function)
studio.example.com     Netlify   Vue SPA
api.example.com        Docker    nginx -> PHP-FPM, queue, scheduler
                                   PostgreSQL, Redis, Meilisearch, MinIO/S3
```

Use subdomains of the same registrable domain. Studio authenticates with Sanctum
cookies, and browsers block cross-site cookies in many configurations.

| Host | Hosts | Required |
| --- | --- | --- |
| `www.example.com` | Website | Yes |
| `studio.example.com` | Studio | Yes |
| `api.example.com` | API and `/storage` media | Yes |

## 2. API on a container host

### Environment

Start from `apps/api/.env.example` and set at least:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.example.com
APP_KEY=base64:...

DB_CONNECTION=pgsql
DB_HOST=postgres
DB_DATABASE=meridian
DB_USERNAME=meridian
DB_PASSWORD=...

REDIS_HOST=redis
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

FILESYSTEM_DISK=s3
MEDIA_DISK=s3
AWS_ENDPOINT=https://s3.example.com
AWS_BUCKET=media
AWS_ACCESS_KEY_ID=...
AWS_SECRET_ACCESS_KEY=...
AWS_USE_PATH_STYLE_ENDPOINT=true
MEDIA_URL=https://cdn.example.com/media

SEARCH_DRIVER=meilisearch
MEILISEARCH_HOST=http://meilisearch:7700
MEILISEARCH_KEY=...

PUBLIC_SITE_URL=https://www.example.com
STUDIO_URL=https://studio.example.com
CORS_ALLOWED_ORIGINS=https://www.example.com,https://studio.example.com
SANCTUM_STATEFUL_DOMAINS=www.example.com,studio.example.com
SESSION_DOMAIN=.example.com
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
TRUSTED_PROXIES=*
TRUSTED_HOSTS=api.example.com
LOG_CHANNEL=stderr
DEMO_MODE=false
```

`TRUSTED_PROXIES=*` is only safe when nginx is behind a private load balancer or
reverse proxy and the API port is not publicly reachable. Otherwise list the
proxy addresses.

### Run

```bash
cd infrastructure
cp .env.example .env
# edit .env, then from the repository root:
APP_URL=https://api.example.com ./infrastructure/prod-up.sh
```

The script starts only the API-side services. If you prefer explicit Compose
commands:

```bash
docker compose -f infrastructure/compose.yaml -f infrastructure/compose.prod.yaml \
  up -d --build postgres redis meilisearch minio api queue scheduler nginx
```

The production override:

- sets `APP_ENV=production`, `APP_DEBUG=false`, `SEED_DEMO=false`;
- stops publishing MinIO ports;
- enables config and view caching;
- restarts API, queue, scheduler and nginx.

The base file also defines `website` and `studio` for local review. Do not add
them to a production `up`; Netlify hosts those.

Run a one-off migration before switching traffic:

```bash
docker compose -f infrastructure/compose.yaml \
  -f infrastructure/compose.prod.yaml exec api php artisan migrate --force
```

Seed only if you intend to run the demonstration content:

```bash
docker compose ... exec api php artisan db:seed --force
docker compose ... exec api php artisan search:index
```

### TLS

Terminate TLS in front of nginx (Caddy, Traefik, an ALB, or a host reverse proxy).
Forward `X-Forwarded-Proto` and `X-Forwarded-For`; nginx already passes the scheme
to PHP. Do not expose MinIO or PostgreSQL ports.

### Media

Two supported paths:

1. **nginx `/storage`** with `FILESYSTEM_DISK=public` and
   `MEDIA_URL=https://api.example.com/storage`.
2. **S3-compatible bucket** (MinIO or a cloud provider) with a public read policy
   or CDN, and `MEDIA_URL` pointing at the CDN.

Keep uploads under `MEDIA_MAX_SIZE` and preserve the existing MIME allow-list.

## 3. Website on Netlify

Create one Netlify site from this repository:

| Setting | Value |
| --- | --- |
| Base directory | `apps/website` |
| Build command | `pnpm build` |
| Publish directory | `dist` |
| Functions directory | `.netlify/functions-internal` |

`apps/website/netlify.toml` already defines these values, sets
`NITRO_PRESET=netlify`, routes unknown paths to the Nuxt function, and adds
security and cache headers.

Set these environment variables in Netlify (Site configuration → Environment
variables):

```dotenv
NUXT_PUBLIC_API_BASE=https://api.example.com/api/v1
NUXT_API_INTERNAL_BASE=https://api.example.com/api/v1
NUXT_PUBLIC_SITE=meridian
NUXT_PUBLIC_SITE_URL=https://www.example.com
NUXT_PUBLIC_STUDIO_URL=https://studio.example.com
NUXT_PUBLIC_DEMO_MODE=true
NITRO_PRESET=netlify
NODE_VERSION=22
```

`NUXT_API_INTERNAL_BASE` must be public because Netlify Functions cannot reach a
private Docker network. It is used for SSR, sitemaps, RSS and document proxies.

Add `https://www.example.com` to `CORS_ALLOWED_ORIGINS` on the API. Deploy:

```bash
cd apps/website
npx netlify-cli login
npx netlify-cli init
npx netlify-cli deploy --build --prod
```

Netlify preview deployments have unique hostnames. Add each preview origin to
`CORS_ALLOWED_ORIGINS` if you need previews to submit forms or search.

## 4. Studio on Netlify

Create a second Netlify site:

| Setting | Value |
| --- | --- |
| Base directory | `apps/studio` |
| Build command | `pnpm build` |
| Publish directory | `dist` |

`apps/studio/netlify.toml` adds the SPA fallback and security headers. Set:

```dotenv
VITE_API_BASE=https://api.example.com/api/v1
NODE_VERSION=22
```

`VITE_API_BASE` is compiled at build time, so changing it requires a redeploy.

For Sanctum cookies:

- API `SESSION_DOMAIN=.example.com`
- API `SANCTUM_STATEFUL_DOMAINS=www.example.com,studio.example.com`
- API `SESSION_SECURE_COOKIE=true`
- API `CORS_ALLOWED_ORIGINS` includes `https://studio.example.com`

Using the default `*.netlify.app` host for Studio with an API on another domain
will usually break cookie auth in modern browsers. Use custom domains.

## 5. DNS

| Type | Name | Value |
| --- | --- | --- |
| CNAME | `www` | Netlify site hostname |
| CNAME | `studio` | Netlify Studio site hostname |
| A / CNAME | `api` | Container host or load balancer |
| CNAME | `cdn` | Optional media CDN |

Enable Netlify managed HTTPS for both front ends. Issue a certificate for the API
host at the proxy.

## 6. Go-live checklist

### API

- [ ] `APP_ENV=production`, `APP_DEBUG=false`, unique `APP_KEY`.
- [ ] HTTPS only; `SESSION_SECURE_COOKIE=true`; HSTS at the edge.
- [ ] `CORS_ALLOWED_ORIGINS` and `SANCTUM_STATEFUL_DOMAINS` contain only real hosts.
- [ ] Demo users removed or passwords rotated; `DEMO_MODE=false` if not a demo.
- [ ] `php artisan config:cache route:cache view:cache` run in the image.
- [ ] Queue worker and scheduler running; failed jobs visible.
- [ ] `search:index` run; query response reports `engine: meilisearch`.
- [ ] `/api/v1/health` monitored; `/storage` or CDN serves an image.
- [ ] Backups scheduled and one restore drill completed.

### Website

- [ ] Netlify build succeeds from `apps/website`.
- [ ] All three locales render with correct canonical and hreflang.
- [ ] Search, contact form and job application reach the API.
- [ ] Sitemap and RSS return 200.
- [ ] Security headers present; `_nuxt` assets cached immutably.
- [ ] Accessibility and performance checks pass on the deployed URL.

### Studio

- [ ] Login works from `studio.example.com`.
- [ ] Create, submit, approve and publish a draft.
- [ ] Media upload reaches the configured bucket.
- [ ] Site switching is limited to assigned sites.

## 7. Deploy and rollback

Netlify builds on every push to `main` once the site is linked. The API deploy
sequence is:

1. Build and push an image tagged with the commit SHA.
2. Run migrations as a one-shot job.
3. Restart `api`, then `queue` and `scheduler`.
4. Rebuild the search index if content or schema changed.
5. Smoke test the API, all three locales, search and one Studio login.

Keep the previous image tag. Migrations must be backwards compatible for one
release. Do not roll back destructive migrations automatically; restore from a
backup or write a forward fix.

## 8. Backups

Use `infrastructure/backup.sh` for a Compose-hosted database and media, then copy
the archives off-host and encrypt them. For managed services, enable automated
backups and PITR. See [RUNBOOK.md](RUNBOOK.md) for the restore drill.

## 9. Scaling

- API containers are stateless apart from PostgreSQL, Redis and S3; scale behind
  a load balancer.
- Scale queue workers independently. Use Redis or SQS, never `sync`.
- Image variant generation is the heaviest request path; move it to the queue if
  uploads grow.
- Netlify Functions scale with traffic. Keep SSR API calls fast; Netlify has a
  request timeout.
- Meilisearch scales vertically first; use a managed search service for large
  corpora.

## 10. CI

`.github/workflows/ci.yml` runs PHP tests and Pint, Composer audit, website and
Studio typechecks and builds, a Netlify-preset build with output assertions, and
the Playwright suite including accessibility and performance checks.

## 11. Known constraints

- Netlify cannot run Laravel, PostgreSQL, Redis, Meilisearch or MinIO.
- Netlify Function cold starts add latency to the first SSR request after idle.
- No external error tracking is configured; add a Sentry DSN or equivalent.
- No WAF or bot protection is included beyond framework rate limits.
- The API does not ship a CSP because the front end uses inline JSON-LD and
  runtime styles. Add a nonce-based policy at the edge if required.
