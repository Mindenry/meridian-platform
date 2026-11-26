# Architecture

## 1. Shape of the system

MERIDIAN is a modular monolith with three deployable surfaces over one content
model:

```
                         ┌────────────────────────────┐
   visitors ───────────▶ │  apps/website (Nuxt SSR)   │
                         │  TH / EN / ZH-CN routes    │
                         └──────────────┬─────────────┘
                                        │ public REST, X-Site + X-Locale
                                        ▼
┌───────────────────┐          ┌────────────────────────────┐
│ apps/studio (Vue) │ ◀──────▶ │  apps/api (Laravel 13)     │
│ Sanctum cookie    │  admin   │  Cms / PublicApi / Jobs    │
└───────────────────┘  REST    └──────┬──────────┬──────────┘
                                      │          │
                         PostgreSQL / SQLite   Redis, Meilisearch,
                         object storage        queue, scheduler
```

- **Website** renders server-side for metadata and first paint, then hydrates.
  All content comes from the public API; it has no database access.
- **Studio** is a static SPA. It authenticates with Sanctum's cookie flow and
  calls the admin API with `withCredentials`, CSRF headers, and `X-Site-Id`.
- **API** owns all authorisation, validation, persistence, publishing, media,
  search, and analytics. The frontend is never the security boundary.

The same block payload produced by the CMS is rendered by the website and by the
Studio preview, so there is one rendering contract rather than two.

## 2. Backend modules

```
app/
  Cms/
    Blocks/BlockRegistry.php   block schema, defaults, validation, sanitisation
    PageService.php            page CRUD, slugs, blocks, revisions
    WorkflowService.php        review, approval, publish, schedule, unpublish
    ContentPresenter.php       public payloads, SEO, alternates, sitemaps
    BlockResolver.php          resolves media, forms, taxonomies into blocks
    MediaService.php           uploads, validation, variants, usage reports
    SearchService.php          Meilisearch query with database fallback
    SearchIndexer.php          rebuilds the published-content index
    SeoAuditService.php        deterministic issue detection and scoring
    AnalyticsService.php       first-party event aggregation
    SiteContext.php            current site, locale, and helpers
    PermissionRegistry.php     permissions, role presets, inheritance
  Http/
    Controllers/Api/V1/
      Admin/                   CMS endpoints, one controller per domain
      PublicApi/               stateless public read and submission endpoints
    Middleware/               ResolveSite, EnsureSiteAccess, EnsurePermission,
                              SetLocale
  Models/                      Eloquent models, site scopes, casts
  Jobs/PublishScheduledPage.php
  Console/Commands/PublishScheduledContent.php
  Support/AuditLogger.php
```

Controllers stay thin: validate, authorise, delegate to a service, present a
payload. Domain rules live in `Cms/` so the same operation can be called from a
controller, a command, a job, or a test.

## 3. Request flows

### Public page

1. `ResolveSite` reads `X-Site` (or the `X-Site-Id` used by Studio) and loads the
   site. `SetLocale` resolves `X-Locale` / `?locale=` against enabled locales.
2. `PublicPageController` finds a **published** page with a translation for that
   locale. If the localized slug is missing it falls back to another published
   translation and marks the payload `is_fallback`.
3. `ContentPresenter` assembles title, blocks, SEO, breadcrumbs, alternates, and
   JSON-LD. It records canonical paths from the locale configuration, not from
   the request host.
4. `BlockResolver` resolves references (media URLs/variants, form schema,
   related articles/projects) in batches.
5. Nuxt renders the block list, emits `<head>` metadata from the payload, and
   hydrates GSAP reveals.

### CMS write

1. Studio calls `GET /sanctum/csrf-cookie`, then `POST /api/v1/auth/login`.
   Laravel regenerates the session and returns user + site-scoped permissions.
2. Every content call sends the session cookie, `X-XSRF-TOKEN`, and `X-Site-Id`.
3. `EnsureSiteAccess` compares the requested site with the user's `role_user`
   assignments. `permission:` middleware checks the granular permission for that
   site.
4. Controllers enforce ownership/capability rules too (for example editors may
   only edit their own drafts, and published content requires `pages.publish`).
5. Services write inside a transaction, bump `lock_version`, create a revision
   snapshot, write an audit entry, and notify the right people.

### Scheduled publication

`WorkflowService::schedule` writes a `scheduled_publications` row and moves the
page to `scheduled`. `content:publish-scheduled` selects due rows and either
dispatches `PublishScheduledPage` or, with `--sync`, runs it inline.
`publishScheduled` locks the row, verifies it is still pending and due, then
publishes and marks the schedule. Running it twice is a no-op.

### Search

`SearchIndexer` builds one document per published localized URL (page, article,
case study, solution, industry, job) and writes it to the `meridian_content`
index. `search:index` rebuilds it, and the API container runs that command after
migrations/seed. `SearchService` queries Meilisearch with a site + locale filter;
if the service is unreachable or the index is empty it falls back to the
deterministic database query and reports which engine served the request.

## 4. Tenancy

Multi-site isolation is explicit, not a global scope:

- `SiteContext` holds the current site for the request.
- Models expose `forSite()` scopes used by every query.
- `role_user.site_id` binds a role to a site; `is_super_admin` bypasses site
  assignment but is still audited.
- Public queries are always filtered by the resolved site.
- Tests cover a site administrator requesting another site (403) and public
  content scoped to the requested site.

This makes accidental cross-site reads visible in code review rather than hidden
inside a framework scope.

## 5. Content model and rendering

- `pages` + `page_translations` + `page_blocks` represent structured pages.
- Editorial content (articles, case studies, solutions, industries, jobs) uses
  dedicated tables with translation tables, because their fields and queries
  differ from pages.
- Blocks are stored as a type plus a JSON `props` object. `BlockRegistry` is the
  authority for allowed types, field schemas, defaults, normalisation, and
  sanitisation. Unknown types are rejected, not silently dropped.
- Revisions store a full snapshot so restore is deterministic even after schema
  evolution (the registry normalises restored data).
- Media assets record disk, path, checksum, dimensions, focal point, localized
  alt/caption, credit, licence, source, and generated variants. Uploads are
  deduplicated per site by checksum.

## 6. Frontend architecture

### Website

- `app/pages/[locale]` provides locale-prefixed routes; the root page redirects
  to the default locale.
- The header shows conventional primary navigation on desktop and a labelled
  mobile **เมนู / Menu** button; `MenuOverlay.vue` is a body-teleported full-screen
  menu. This keeps the mobile menu out of the sticky header's `backdrop-filter`
  containing block and guarantees full-height behaviour.
- Display type is Fraunces for Latin and Noto Serif Thai for Thai; script-aware
  overrides remove negative tracking and add leading for Thai. Chinese falls back
  to the body sans stack.
- CMS-driven pages render through `[page].vue`; collections and detail pages use
  dedicated routes for stronger typing and richer layouts.
- `BlockRenderer.vue` statically imports every block component, avoiding dynamic
  import waterfalls while keeping the bundle tree-shakeable per block.
- `useApi` is the only HTTP entry point; it always sends `X-Site`.
- `useBootstrap` caches navigation per locale and mirrors every fetch into shared
  state, so the header changes language with the page rather than once per visit.
- `useSeo` owns canonical, hreflang, Open Graph, robots, and JSON-LD.
- `server/routes/[locale]/rss.xml.ts` renders per-locale insights feeds from the
  same public API; `/rss.xml` serves the default locale.
- Consent lives in `useConsent` (analytics flag plus timestamp) and is edited in
  `CookiePreferences.vue`, which can be reopened from the footer.
- `pages/[locale]/accessibility.vue` is a code-owned statement in three locales.
- `useReveal` runs GSAP only on the client, respects `prefers-reduced-motion`, and
  cancels ScrollTriggers on route change.

### Studio

- `src/api/client.ts` centralises base URL, CSRF bootstrap, retry on 419, the
  current site header, and 401 handling.
- Pinia-style stores (`auth`, `blocks`, `toast`) keep session and definitions
  shared across views.
- `PageBuilderView` is a three-pane editor: block palette, canvas, inspector. It
  autosaves blocks, supports undo/redo, device preview widths, reordering, and a
  focused block inspector. Media and forms are picked from the API.
- Editors are plain Vue views; there is no hidden client-side authorisation. The
  API returns capabilities and rejects disallowed actions independently.

## 7. Failure behaviour

| Dependency | Missing behaviour |
| --- | --- |
| Meilisearch | `SearchService` falls back to database search and reports `engine: database`. |
| S3 / MinIO | Laravel disk abstraction; local `public` disk works when S3 is absent. |
| Redis | Cache/session/queue can be switched to database drivers. |
| Reverb | CMS polling still refreshes state; no workflow depends on websockets. |
| Google integrations | Stored state stays `disconnected`/`unverified`; no fake metrics. |

## 8. Deliberate trade-offs

- A monolith keeps transactions and authorisation simple. The module boundaries
  are folder-level, not network-level, which is appropriate for one content team.
- JSON block props trade strict SQL typing for editor flexibility. The server-side
  registry and sanitisation are the guardrail.
- SQLite keeps local verification fast and portable; PostgreSQL is the production
  target and the Compose default.
- Preview tokens are database-backed and expiring rather than signed URLs only,
  so revocation and auditing are possible.

## 9. Operations

`infrastructure/backup.sh` dumps PostgreSQL and archives the MinIO and application
storage volumes. `infrastructure/restore.sh` restores them, restarts services, and
rebuilds the search index. [RUNBOOK.md](RUNBOOK.md) documents health checks, deploy
steps, queue recovery, the restore drill, and the known single-node limitations.

See [DECISIONS.md](DECISIONS.md) for the full decision log.
