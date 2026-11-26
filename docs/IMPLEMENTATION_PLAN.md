# MERIDIAN — Implementation Plan

> Living document. Status is updated as work is verified, never assumed.

## 1. What is built

| Surface | Stack | Local port | Purpose |
| --- | --- | --- | --- |
| `apps/api` | Laravel 13, Sanctum, Pest 5, SQLite locally / PostgreSQL in Compose | 8000 | Public content API, CMS API, publishing, SEO, media, leads |
| `apps/website` | Nuxt 4, Vue 3.5, TypeScript, Tailwind 4, GSAP | 3002 | SSR corporate site in Thai, English, Simplified Chinese |
| `apps/studio` | Vue 3.5, Vite, TypeScript, Tailwind 4, Tiptap 3 | 3003 | MERIDIAN STUDIO CMS and visual builder |
| `apps/e2e` | Playwright + Chrome | — | Critical browser journeys |
| `infrastructure` | Docker Compose: PostgreSQL, Redis, Meilisearch, MinIO, API, queue, scheduler, nginx, website, Studio | — | Reproducible environment |

Brand content is fictional and labelled as illustrative. No real client,
certification, financial result, or partnership is claimed.

## 2. Repository decisions

- **Modular monolith, not microservices.** One Laravel app with domain folders
  (`Cms/`, `Http/Controllers/Api/V1/PublicApi`, `Admin`, models, jobs, commands)
  and a versioned `/api/v1`.
- **Custom RBAC** with site-scoped assignments (`role_user.site_id`), granular
  permissions, middleware, and service-level checks.
- **Site-scoped queries** resolved from `X-Site`/`X-Site-Id`; no global scope magic.
- **Structured pages** in `pages` + `page_translations` + `page_blocks`, validated
  and sanitised by `BlockRegistry`.
- **Revisions are snapshots**; restore is deterministic.
- **Publishing is a state machine** with approvals, audit logs, and an idempotent
  scheduler command.
- **Sanctum SPA cookie auth** for Studio, stateless public API.
- **PostgreSQL in Compose, SQLite locally/tests**, with portable migrations.
- **Meilisearch optional** with a deterministic database fallback.
- **S3/MinIO optional** through Laravel disks and generated WebP variants.
- **GSAP selective**, progressive, and reduced-motion aware.

## 3. Delivery status

### Phase 0 — Inspection and toolchain
- [x] Toolchain verified: PHP 8.6.0, Composer 2.11, Node 26, pnpm 10, Docker 29.9.
- [x] Laravel 13.44.1, Pest 5.3, PHPUnit 13.4.7 installed.
- [x] Image generation unavailable (billing); licensed Commons photography
      sourced, processed to WebP, and credited.

### Phase 1 — Foundation
- [x] Monorepo skeleton and workspace scripts.
- [x] Full domain schema, models, RBAC, seeders.
- [x] Nuxt website shell, design tokens, fonts.
- [x] Studio shell, tokens, auth.
- [x] Dockerfiles, Compose stack, CI workflow.

### Phase 2 — Public website
- [x] Home, About, Solutions, Industries, Case studies, Insights, Careers,
      Investor relations, Contact, Search, four legal pages, credits, 404.
- [x] TH / EN / ZH-CN routing, localized navigation, metadata, and content.
- [x] SSR SEO, hreflang, canonical, OG, JSON-LD, sitemap index + locale sitemaps,
      robots.
- [x] GSAP motion with reduced-motion support and responsive compositions.
- [x] Distinct editorial art direction: serif display type, visible primary
      navigation with dropdown panels, a labelled full-screen mobile menu,
      script-aware Thai typography, and no instrument-style or index gimmicks.

### Phase 3 — CMS
- [x] Login, session, RBAC-aware navigation.
- [x] Dashboard with persisted metrics and demo labelling.
- [x] Pages, revisions, preview, scheduling, workflow.
- [x] Articles, case studies, solutions, industries, jobs, documents.
- [x] Media, navigation, forms, leads, SEO, analytics, access, sites, settings.

### Phase 4 — Visual builder
- [x] Server block registry (27 types) with validation, sanitisation, defaults.
- [x] Drag/drop canvas, inspector, undo/redo, autosave, device widths.
- [x] Same presenter and block components for preview and published output.

### Phase 5 — Enterprise workflow
- [x] Submit / approve / request changes / publish / schedule / unpublish.
- [x] Idempotent scheduler command and queued job.
- [x] Notifications, audit log, approval history.
- [x] Multi-site isolation tests and E2E denial.

### Phase 6 — SEO and search
- [x] Metadata editor and deterministic audit with explainable issues.
- [x] Redirects, broken-link and orphan checks.
- [x] Database/Meilisearch search with grouped results and engine disclosure.
- [x] Structured data and locale-aware canonical/hreflang.

### Phase 7 — Leads and analytics
- [x] Form schema, public submissions, spam score, rate limits.
- [x] Lead inbox, assignment, status, notes, CSV export.
- [x] First-party analytics events and date-range dashboard.
- [x] GSC/GA4/GTM integration boundary with honest disconnected states.

### Phase 8 — Testing and security
- [x] Pest: auth, RBAC, isolation, page CRUD, publishing, scheduling, blocks,
      uploads, forms, leads, SEO, search.
- [x] Playwright: publishing flow, locales/hreflang, sitemap/robots, search,
      form → lead, scheduled publish, cross-site denial, UX regressions,
      axe accessibility checks (12 templates), LCP/CLS budgets, consent, RSS, and the second site.
- [x] Accessibility hardening: 4.5:1 text contrast, 12px minimum labels,
      44px primary touch targets, visible input focus, script-aware typography,
      and no accessible-name mismatches.
- [x] Cookie preference centre with persisted analytics choice, accessibility
      statement page, RSS feeds per locale, print styles, and a seeded second site.
- [x] Studio guard for unsaved changes (in-app and browser), discard action,
      bulk actions, and readable 12px minimum UI text.
- [x] Operations runbook, database/media backup and restore scripts.
- [x] Security pass: headers, rate limits, upload guards, sanitisation, CSRF.
- [x] Responsive and accessibility review through automated axe scans, keyboard
      tests, screenshots, and manual checks; no external WCAG audit was purchased.

### Phase 9 — Delivery
- [x] README and required docs (architecture, database, API, SEO, security,
      deployment, testing, content model, page builder, decisions, credits).
- [x] OpenAPI contract.
- [x] Verified screenshots in `apps/e2e/screenshots`.
- [x] Final requirement audit with honest gaps (sections 4–5).

## 4. Verification log

| Check | Command | Result |
| --- | --- | --- |
| PHP runtime | `php -v` | 8.6.0 NTS, required extensions enabled |
| Laravel boot | `php artisan --version` | 13.44.1 |
| Fresh migrate + seed | `php artisan migrate:fresh --seed` | Pass, all seeders complete |
| Backend tests | `php artisan test --compact` | 30 passed, 126 assertions |
| PHP format | `vendor/bin/pint --test` | Passes on changed files |
| Website typecheck | `pnpm --filter @meridian/website typecheck` | Exit 0 |
| Studio typecheck | `pnpm --filter @meridian/studio typecheck` | Exit 0 |
| Website build | `pnpm --filter @meridian/website build` | Pass |
| Studio build | `pnpm --filter @meridian/studio build` | Pass |
| E2E | `npx playwright test` | 41 passed |
| Compose config | `docker compose -f infrastructure/compose.yaml config` | Valid |
| Docker images | `docker compose ... build` | API, nginx, website, Studio built |
| Full Compose stack | `docker compose ... up -d --build` | PostgreSQL, Redis, Meilisearch, MinIO, API, queue, scheduler, nginx, website, Studio all up |
| Compose E2E | `E2E_DOCKER=1 npx playwright test` | 41 passed against the containerised stack |
| Meilisearch | public search request | `engine: meilisearch`, 100 documents for Meridian and 3 for the second site |
| Object storage | write/read through the `s3` disk | Pass (MinIO) |
| Cleanup | force-deleted E2E fixtures | 11 pages, 0 orphan E2E translations |

## 5. Known constraints

- Local verification uses SQLite because no PostgreSQL service is installed on the
  host; Compose provides PostgreSQL 17 for parity.
- Reverb is configured and packaged but not started in the default Compose
  profile. The CMS also polls, so no workflow depends on it.
- Google Search Console / GA4 credentials are not available; the integration UI
  reports a genuine disconnected state.
- The seeded demo content is intentionally concise, and the SEO audit reports
  real thin-content and translation findings rather than a fabricated perfect
  score. False positives (template H1, navigation links, default OG image,
  locale-less CTA paths) were fixed; genuine gaps remain visible.
- Demo accounts share a password and Compose credentials are demo values; they
  must be rotated before shared deployment.
- Images are CC-licensed documentary photographs, colour-graded in the interface;
  attribution lives in `CREDITS.md` and on the website.
