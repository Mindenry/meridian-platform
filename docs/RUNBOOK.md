# Operations runbook

This runbook covers the Docker Compose deployment used for the demonstration.
It is deliberately explicit about what is and is not production-grade.

## Services

| Service | Purpose | Port |
| --- | --- | --- |
| `nginx` | Public entry point for the API | 8000 |
| `api` | Laravel API + queue/scheduler image | 9000 (internal) |
| `queue` | `queue:work` worker | — |
| `scheduler` | `schedule:work` | — |
| `postgres` | Primary database | 5432 (internal) |
| `redis` | Cache, sessions, queues | 6379 (internal) |
| `meilisearch` | Search index | 7700 (internal) |
| `minio` | Media object storage | 9000/9001 |
| `website` | Nuxt public site | 3002 |
| `studio` | Vue CMS | 3003 |

## Health checks

```bash
curl -fsS http://localhost:8000/api/v1/health
curl -fsS -o /dev/null -w '%{http_code}\n' http://localhost:3002/en
curl -fsS -o /dev/null -w '%{http_code}\n' http://localhost:3003/login
docker compose -f infrastructure/compose.yaml ps
```

A healthy deployment returns `{"status":"ok","database":"ok"}` and HTTP 200 from
both front ends.

## Backup

```bash
./infrastructure/backup.sh
```

Creates three timestamped files in `backups/`:

- `postgres-<stamp>.sql.gz` — logical database dump (`--clean --if-exists`)
- `minio-<stamp>.tar.gz` — MinIO data volume
- `storage-<stamp>.tar.gz` — Laravel application storage volume

Recommended cadence for a real deployment: nightly database dumps plus hourly
WAL archiving, and object storage versioning. This repository does **not** ship
WAL archiving, off-site replication, or automated retention; add those before
treating the stack as production infrastructure.

## Restore

```bash
./infrastructure/restore.sh backups/postgres-<stamp>.sql.gz \
  backups/minio-<stamp>.tar.gz \
  backups/storage-<stamp>.tar.gz
```

The script stops the application services, restores the database, restores media
volumes when supplied, restarts the stack, and rebuilds the Meilisearch index.

### Restore drill (quarterly)

1. Copy the latest backups to a clean host.
2. Run `docker compose up -d postgres redis minio meilisearch`.
3. Run `restore.sh` with the three archives.
4. Verify: health endpoint, a public page in each locale, one Studio login, one
   media image, and one search query.
5. Record the recovery time and any missing object warnings.

## Deploying a change

```bash
pnpm lint
pnpm --filter @meridian/website build
pnpm --filter @meridian/studio build
(cd apps/api && php artisan test --compact && vendor/bin/pint --test)
docker compose -f infrastructure/compose.yaml build
docker compose -f infrastructure/compose.yaml up -d
```

For schema changes, run `php artisan migrate --force` inside the `api` service
before restarting `queue` and `scheduler`.

## Queue and scheduler

```bash
docker compose -f infrastructure/compose.yaml logs -f queue
docker compose -f infrastructure/compose.yaml logs -f scheduler
docker compose -f infrastructure/compose.yaml exec api php artisan queue:failed
docker compose -f infrastructure/compose.yaml exec api php artisan queue:retry all
```

Scheduled publishing runs every minute through `schedule:work`. If the scheduler
is down, scheduled pages stay in `scheduled` and are not public.

## Search index

```bash
docker compose -f infrastructure/compose.yaml exec api php artisan search:index
```

The command reports documents per site. If Meilisearch is unavailable, public
search falls back to the database and the API response reports
`"engine": "database"`.

## Incident checklist

1. Confirm scope: API, website, Studio, media, or search.
2. Check `docker compose ps` and the relevant service logs.
3. Check `/api/v1/health`; a degraded database is a stop-the-line incident.
4. For data loss, stop writes (`docker compose stop api queue scheduler`) before
   restoring.
5. After recovery, rebuild search, verify locale switching, and confirm one
   end-to-end form submission.
6. Record timeline, root cause, and follow-up actions.

## Known limitations

- Single-node Compose deployment; no high availability or automatic failover.
- No external error tracking (Sentry or equivalent) is configured.
- No load test or capacity plan is committed; run one before a launch campaign.
- Backups are local by default; copy them off-host and encrypt them at rest.
- Accessibility is checked by automated axe tests plus manual keyboard review,
  not by an external audit.
