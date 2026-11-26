# Meridian API

Laravel application behind the public website and Meridian Studio. It owns the
content model, publishing workflow, media, forms and leads, search indexing,
analytics events, and site-scoped access control.

## Run

From the repository root:

```bash
cd apps/api
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate:fresh --seed
php artisan serve --host=127.0.0.1 --port=8000
```

The database is seeded with the demonstration content. All demo users share the
password `Meridian!2026`.

## Verify

```bash
php artisan test --compact
vendor/bin/pint --test
```

## Entry points

| Route | Purpose |
| --- | --- |
| `GET /api/v1/health` | Database connectivity and version |
| `/api/v1/public/*` | Read-only website content, search, forms and analytics |
| `/api/v1/admin/*` | Sanctum-protected CMS operations |

The full contract is in [../../docs/API.md](../../docs/API.md) and
[../../docs/openapi.yaml](../../docs/openapi.yaml). Architecture and publishing
decisions are documented in [../../docs/ARCHITECTURE.md](../../docs/ARCHITECTURE.md).
