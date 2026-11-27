# Deployment

There are two ways to run this project:

| Mode | What runs | Best for |
| --- | --- | --- |
| Static demo | Netlify only | Showing the public site and the Studio tour |
| Full stack | API host plus Netlify or containers | Editing content, collecting leads, testing search |

The static demo needs no PHP, database, or object storage. The full stack needs all
of them.

## 1. Static demo on Netlify

The Nuxt site builds a prerendered snapshot from committed fixtures. Netlify runs
`pnpm generate`, serves `.output/public`, and calls no API at runtime.

What works: every page in Thai, English and Chinese; navigation and language
switching; search across the snapshot; RSS and sitemap files; the Studio tour;
accessibility and performance checks. Form submissions show a demo confirmation
and send nothing.

What does not work: Studio login and editing, new form leads, live search
indexing, and CMS previews.

### Create the site

1. Push the repository to GitHub.
2. In Netlify, choose **Add new site → Import an existing project**.
3. Leave the base directory empty. The repository root `netlify.toml` sets the
   build command, publish directory, Node and pnpm versions, and security
   headers. It installs the monorepo at the root, where the lockfile lives.
4. Deploy. The default branch is `main`.

The demo reads `apps/website/public/demo` and `apps/website/public/demo-media`.
Both are committed, so the build is reproducible and takes about ten seconds.

Optional environment variables:

```dotenv
NUXT_PUBLIC_SITE_URL=https://your-site.netlify.app
NUXT_PUBLIC_SITE=meridian
```

When `NUXT_PUBLIC_SITE_URL` is omitted, the build uses Netlify's `URL` value.
Set it explicitly after attaching a custom domain.

### Deploy from the CLI

```bash
cd apps/website
npx netlify-cli login
npx netlify-cli init
npx netlify-cli deploy --build --prod
```

### Refresh the snapshot

The demo content does not change until the fixtures are regenerated. With the
API running locally:

```bash
cd apps/website
node scripts/snapshot-demo.mjs
pnpm generate
```

The script writes the API responses, search index, media copies and PDFs under
`public/`. Commit those files to publish the new snapshot.

## 2. Full stack on a container host

The API is PHP and cannot run on Netlify. Host it on a VPS, Fly.io, Render,
Railway, or any container platform with PostgreSQL, Redis, object storage and
Meilisearch.

```
www.example.com        Netlify or nginx   Nuxt site
studio.example.com     Netlify or nginx   Vue Studio
api.example.com        Docker             nginx, PHP-FPM, queue, scheduler
                                          PostgreSQL, Redis, Meilisearch, MinIO
```

Use subdomains of the same registrable domain. Studio uses Sanctum cookies, and
browsers block cross-site cookies in many configurations.

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

MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS=no-reply@example.com
MAIL_FROM_NAME="Meridian"
MAIL_SCHEME=tls
```

`TRUSTED_PROXIES=*` is safe only when nginx sits behind a private load balancer
or reverse proxy and the API port is not public. Otherwise list the proxy
addresses.

### Run

```bash
cd infrastructure
cp .env.example .env
# edit .env, then from the repository root:
APP_URL=https://api.example.com ./infrastructure/prod-up.sh
```

The script starts PostgreSQL, Redis, Meilisearch, MinIO, the API, the queue, the
scheduler and nginx. It runs migrations and rebuilds the search index. The
website and Studio are not started because Netlify or a separate proxy hosts
them.

The production override sets `APP_ENV=production`, `APP_DEBUG=false`,
`SEED_DEMO=false`, enables config and view caching, stops publishing MinIO
ports, and restarts services.

To run the front ends in containers instead, add them explicitly:

```bash
APP_URL=https://api.example.com docker compose \
  -f infrastructure/compose.yaml \
  -f infrastructure/compose.prod.yaml \
  up -d website studio
```

### TLS

Terminate TLS in front of nginx with Caddy, Traefik, an ALB, or a host reverse
proxy. Forward `X-Forwarded-Proto` and `X-Forwarded-For`. Do not expose MinIO,
PostgreSQL, or Redis ports.

### Media

Two supported paths:

1. nginx `/storage` with `FILESYSTEM_DISK=public` and
   `MEDIA_URL=https://api.example.com/storage`.
2. An S3-compatible bucket with a public read policy or CDN, and `MEDIA_URL`
   pointing at the CDN.

Keep uploads under `MEDIA_MAX_SIZE` and preserve the MIME allow-list.

### Studio cookies

For Sanctum cookie auth:

- API `SESSION_DOMAIN=.example.com`
- API `SANCTUM_STATEFUL_DOMAINS=www.example.com,studio.example.com`
- API `SESSION_SECURE_COOKIE=true`
- API `CORS_ALLOWED_ORIGINS` includes the Studio origin
- Studio and API live under the same registrable domain

Using the default `*.netlify.app` host for Studio with an API on another domain
will usually break cookie auth. Use custom domains.

## 3. DNS

| Type | Name | Value |
| --- | --- | --- |
| CNAME | `www` | Netlify site or nginx host |
| CNAME | `studio` | Netlify site or nginx host |
| A / CNAME | `api` | Container host or load balancer |
| CNAME | `cdn` | Optional media CDN |

Enable managed HTTPS for Netlify sites and issue a certificate for the API host
at the proxy.

## 4. Go-live checklist

### API

- [ ] `APP_ENV=production`, `APP_DEBUG=false`, unique `APP_KEY`.
- [ ] HTTPS only; `SESSION_SECURE_COOKIE=true`; HSTS at the edge.
- [ ] `CORS_ALLOWED_ORIGINS` and `SANCTUM_STATEFUL_DOMAINS` contain only real hosts.
- [ ] Demo users removed or passwords rotated; `DEMO_MODE=false`.
- [ ] `php artisan config:cache` and `view:cache` run in the container.
- [ ] Queue worker and scheduler running; failed jobs visible.
- [ ] `search:index` run; a query reports `engine: meilisearch`.
- [ ] `/api/v1/health` monitored; media serves through `/storage` or the CDN.
- [ ] Backups scheduled and one restore drill completed.

### Website

- [ ] Build succeeds from `apps/website`.
- [ ] Three locales render with correct canonical and hreflang.
- [ ] Search, contact form and job application reach the API.
- [ ] Sitemap and RSS return 200.
- [ ] Security headers present; hashed assets cached.
- [ ] Accessibility and performance checks pass on the deployed URL.

### Studio

- [ ] Login works from `studio.example.com`.
- [ ] Create, submit, approve and publish a draft.
- [ ] Media upload reaches the configured bucket.
- [ ] Site switching is limited to assigned sites.

## 5. Deploy and rollback

For the static demo, Netlify builds every push to `main`. For the API:

1. Build and push an image tagged with the commit SHA.
2. Run migrations as a one-shot job.
3. Restart `api`, then `queue` and `scheduler`.
4. Rebuild the search index if content or schema changed.
5. Smoke test the API, all three locales, search and one Studio login.

Keep the previous image tag. Migrations must be backwards compatible for one
release. Do not roll back destructive migrations automatically; restore from a
backup or write a forward fix.

## 6. Backups

Use `infrastructure/backup.sh` for a Compose-hosted database and media, then copy
the archives off-host and encrypt them. For managed services, enable automated
backups and point-in-time recovery. See [RUNBOOK.md](RUNBOOK.md) for the restore
drill.

## 7. Scaling

- API containers are stateless apart from PostgreSQL, Redis and S3; scale behind
  a load balancer.
- Scale queue workers independently. Use Redis or SQS, never `sync`.
- Image variant generation is the heaviest request path; move it to the queue if
  uploads grow.
- Meilisearch scales vertically first; use a managed search service for large
  corpora.
- A static Netlify demo scales without application servers.

## 8. CI

`.github/workflows/ci.yml` runs PHP tests and Pint, Composer audit, website and
Studio typechecks and builds, a static demo build with output checks, a
production Compose validation, and the Playwright suite including accessibility
and performance checks.

## 9. Constraints

- The static demo is a snapshot. Content changes require a new snapshot commit.
- Demo forms do not persist data.
- Netlify cannot run Laravel, PostgreSQL, Redis, Meilisearch, or MinIO.
- No external error tracking is configured; add a Sentry DSN or equivalent.
- No WAF or bot protection is included beyond framework rate limits.
- The API does not ship a CSP because the front end uses inline JSON-LD and
  runtime styles. Add a nonce-based policy at the edge if required.
