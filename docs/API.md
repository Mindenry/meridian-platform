# API

Base URL: `http://localhost:8000/api/v1`
Contract: [openapi.yaml](openapi.yaml)

All endpoints return JSON. Errors use a stable envelope:

```json
{
  "message": "The submitted data is invalid.",
  "code": "validation_error",
  "errors": { "title": ["A page title is required."] }
}
```

## 1. Authentication

The public API is stateless. The CMS uses Sanctum's SPA cookie flow:

```bash
curl -c cookies.txt http://localhost:8000/sanctum/csrf-cookie
TOKEN=$(grep XSRF-TOKEN cookies.txt | awk '{print $7}' | python -c 'import sys,urllib.parse;print(urllib.parse.unquote(sys.stdin.read().strip()))')

curl -b cookies.txt -c cookies.txt \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "X-XSRF-TOKEN: $TOKEN" \
  -H "Origin: http://localhost:3003" \
  -H "Referer: http://localhost:3003/" \
  -d '{"email":"admin@meridian.example","password":"Meridian!2026"}' \
  http://localhost:8000/api/v1/auth/login
```

- Sessions are regenerated on login and invalidated on logout.
- The Studio sends `withCredentials` and refreshes CSRF automatically on `419`.
- `SANCTUM_STATEFUL_DOMAINS` must contain the Studio origin. Only the Studio is
  stateful; the public site never authenticates.
- Login is rate limited by `throttle:login`.

`GET /auth/me` returns the user, roles (with site IDs), effective permissions, and
accessible sites. The Studio uses this to build navigation and to select the
initial site.

## 2. Required headers

| Header | Used by | Required | Meaning |
| --- | --- | --- | --- |
| `X-Site` | Public API | yes | Site slug, for example `meridian` |
| `X-Site-Id` | Admin API | optional | Numeric site selected in Studio; falls back to `meridian` |
| `X-Locale` | Public API | optional | `th`, `en`, or `zh-CN`; defaults to the site default |
| `X-XSRF-TOKEN` | Admin writes | yes | CSRF token from the cookie |
| `Accept` | all | recommended | `application/json` |

Public content is never readable across sites: `X-Site` resolves a site and every
query is scoped to it. Admin access is additionally checked against the user's
site assignments.

## 3. Pagination and filtering

Admin list endpoints accept `page` and `per_page` (5–100, default 20) and return:

```json
{
  "data": [],
  "meta": { "current_page": 1, "last_page": 3, "per_page": 20, "total": 41 }
}
```

Pages additionally accept `status`, `q`, and `locale`. Search accepts `q` and
`limit`. Article lists accept `type` (`insight`/`news`), `category`, and `page`.

## 4. Public endpoints

| Method | Path | Purpose |
| --- | --- | --- |
| GET | `/health` | Liveness and database check |
| GET | `/public/bootstrap` | Site, locales, navigation, public settings, organization schema |
| GET | `/public/home` | Published homepage |
| GET | `/public/pages/{slug}` | Published page by localized slug |
| GET | `/public/resolve?path=/en/about` | Resolve a path to a page or redirect |
| GET | `/public/preview/{token}` | Expiring CMS preview |
| GET | `/public/solutions` | Solution collection |
| GET | `/public/solutions/{slug}` | Solution detail |
| GET | `/public/industries` | Industry collection |
| GET | `/public/industries/{slug}` | Industry detail |
| GET | `/public/case-studies` | Case study collection |
| GET | `/public/case-studies/{slug}` | Case study detail |
| GET | `/public/articles` | Insights and news |
| GET | `/public/articles/{slug}` | Article detail |
| GET | `/public/careers` | Open roles |
| GET | `/public/careers/{slug}` | Job detail |
| GET | `/public/documents` | Public documents |
| GET | `/public/team` | Leadership profiles |
| GET | `/public/search?q=` | Grouped search with engine disclosure |
| GET | `/public/search/suggestions?q=` | Typeahead |
| GET | `/public/sitemap?locale=` | Localized sitemap entries |
| GET | `/public/sitemap-index` | Per-locale sitemap index |
| POST | `/public/forms/{key}/submissions` | Submit a form |
| POST | `/public/jobs/{slug}/applications` | Submit a job application |
| POST | `/public/analytics/events` | Record a first-party event |

### Public page payload

```json
{
  "data": {
    "type": "page",
    "title": "About Meridian",
    "slug": "about",
    "locale": "en",
    "content_locale": "en",
    "is_fallback": false,
    "url": "http://localhost:3002/en/about",
    "blocks": [
      { "uid": "…", "type": "hero", "props": { "…": "…" }, "resolved": {} }
    ],
    "seo": {
      "title": "About Meridian — an industrial engineering partner",
      "description": "…",
      "canonical": "http://localhost:3002/en/about",
      "robots": "index,follow",
      "og": { "title": "…", "description": "…", "image": "…" },
      "schema": [{ "@context": "https://schema.org", "@type": "WebPage" }]
    },
    "breadcrumbs": [],
    "alternates": [
      { "locale": "th", "hreflang": "th", "url": "…/th/about" },
      { "locale": "en", "hreflang": "en", "url": "…/en/about" },
      { "locale": "zh-CN", "hreflang": "zh-CN", "url": "…/zh-cn/about" },
      { "locale": "x-default", "hreflang": "x-default", "url": "…/th/about" }
    ]
  }
}
```

### Form submission

Fields are defined by the form schema returned from `/public/contact`. Send the
values plus:

```json
{
  "consent": true,
  "website": "",
  "elapsed_ms": 3500,
  "page": "/en/contact"
}
```

`website` is a honeypot and must stay empty. Very fast submissions and messages
with more than two links raise the spam score. A successful submission returns
`201` with a reference such as `MRD-000123`. Accepted submissions create a lead
unless the form disables it.

## 5. Admin endpoints

Every admin route requires `auth:sanctum`, a site header, and site access.

### Pages and workflow

| Method | Path | Permission |
| --- | --- | --- |
| GET | `/admin/pages` | `pages.view` |
| POST | `/admin/pages` | `pages.create` |
| GET | `/admin/pages/{page}` | `pages.view` |
| PATCH | `/admin/pages/{page}` | edit capability |
| DELETE | `/admin/pages/{page}` | `pages.delete` |
| PUT | `/admin/pages/{page}/blocks` | edit capability |
| PUT | `/admin/pages/{page}/seo` | edit capability |
| POST | `/admin/pages/{page}/submit` | edit capability |
| POST | `/admin/pages/{page}/approve` | `pages.approve` |
| POST | `/admin/pages/{page}/request-changes` | `pages.approve` |
| POST | `/admin/pages/{page}/publish` | `pages.publish` |
| POST | `/admin/pages/{page}/schedule` | `pages.publish` |
| POST | `/admin/pages/{page}/cancel-schedule` | `pages.publish` |
| POST | `/admin/pages/{page}/unpublish` | `pages.publish` |
| GET | `/admin/pages/{page}/revisions` | `pages.view` |
| POST | `/admin/pages/{page}/revisions/{revision}/restore` | edit capability |
| POST | `/admin/pages/{page}/preview-token` | `pages.view` |

`PATCH` accepts an optional `lock_version`. A mismatch returns `409` so two
editors cannot silently overwrite each other.

### Media

`GET/POST /admin/media`, `GET/PATCH/DELETE /admin/media/{asset}`,
`GET /admin/media/{asset}/usage`, `POST /admin/media/folders`.
Uploads accept JPEG, PNG, WebP, GIF, and PDF up to 10 MB, validate MIME by content,
generate WebP variants for images, and reject deletion while referenced.

### Content

`articles` (with categories), `case-studies`, `solutions`, `industries`, `jobs`
(with applications), `documents`, `team`, `navigation`, `forms`, `submissions`.
Each follows the same list/detail/create/update/delete shape with domain-specific
permissions.

### Leads

`GET /admin/leads`, `GET/PATCH/DELETE /admin/leads/{lead}`,
`POST /admin/leads/{lead}/activities`, and CSV export at
`GET /admin/leads/export`. Filters: `status`, `priority`, `owner_id`, `q`.

### SEO, access, settings

- `GET /admin/seo/health`, `POST /admin/seo/audit`, `GET /admin/seo/issues`,
  `PATCH /admin/seo/issues/{issue}`, `GET /admin/seo/sitemap`.
- `GET/POST/PATCH/DELETE /admin/redirects`.
- `GET /admin/users`, `POST /admin/users/invite`, `PATCH /admin/users/{user}`,
  `GET /admin/roles`, `GET /admin/audit-logs`.
- `GET/PATCH /admin/settings`, `PUT /admin/integrations/{provider}`,
  `POST /admin/integrations/{provider}/test`.
- `GET /admin/workflows`, `PATCH /admin/workflows/definitions/{definition}`.

## 6. Rate limits

| Limiter | Scope |
| --- | --- |
| `login` | Login attempts per IP + email |
| `api` | General API |
| `search` | Public search and suggestions |
| `public-forms` | Form submissions and job applications |
| `uploads` | Admin media uploads |

Limits are configured in `AppServiceProvider` and return `429` with `Retry-After`.

## 7. Examples

Public page:

```bash
curl -H "Accept: application/json" \
     -H "X-Site: meridian" \
     -H "X-Locale: en" \
     "http://localhost:8000/api/v1/public/pages/about"
```

Search:

```bash
curl -H "X-Site: meridian" \
     "http://localhost:8000/api/v1/public/search?q=automation&locale=en&limit=5"
```

Create a draft page as an authenticated editor:

```bash
curl -b cookies.txt \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "X-XSRF-TOKEN: $TOKEN" \
  -H "X-Site-Id: 1" \
  -d '{"title":"Supplier standards","slug":"supplier-standards","locale":"en"}' \
  http://localhost:8000/api/v1/admin/pages
```

## 8. Versioning

The prefix is `/api/v1`. Additive fields are allowed within a version; breaking
changes require `/api/v2` and a deprecation window. The public website and Studio
pin to `v1`.
