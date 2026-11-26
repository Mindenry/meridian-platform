# Decisions

Short architecture decision records. Each entry states context, decision, and
consequences, including the cost.

---

## ADR-001 — Monorepo with three apps

**Context.** The website, CMS, and API share a content contract and must evolve
together.

**Decision.** One repository with `apps/api`, `apps/website`, `apps/studio`, and
`apps/e2e`, plus `infrastructure/` and `docs/`.

**Consequences.** Atomic changes across the contract; one lockfile and one CI
workflow. The trade-off is a larger checkout and the need for path-scoped
commands. We accept it because one team owns all three surfaces.

---

## ADR-002 — Modular monolith, not microservices

**Context.** Content publishing needs transactions across page state, revisions,
approvals, schedules, and audit logs.

**Decision.** A single Laravel application with domain folders and one versioned
API.

**Consequences.** Strong consistency and simple authorisation. Scaling is
per-process rather than per-service. If search or media grows, those dependencies
are already external services and can be extracted later.

---

## ADR-003 — Custom RBAC instead of a package

**Context.** Access is site-scoped: the same user can publish on one site and only
edit on another. Most role packages assume global roles.

**Decision.** Explicit `roles`, `permissions`, `role_user.site_id`, a permission
registry, middleware, and service-level checks.

**Consequences.** More code to maintain, but site isolation is visible and
testable. Role presets are data, so adding a role does not require a migration.

---

## ADR-004 — Structured page blocks with a server registry

**Context.** Editors need layout flexibility; the frontend needs a bounded, safe
schema.

**Decision.** `pages` + `page_translations` + `page_blocks`, with JSON props
validated and sanitised by `BlockRegistry` (27 block types).

**Consequences.** Flexible composition without arbitrary HTML. The registry is a
single place to add fields and rules. JSON props are less queryable than columns;
that is acceptable because blocks are read as a whole page.

---

## ADR-005 — Revisions are full snapshots

**Context.** Publishing is high-stakes; rollback must be predictable.

**Decision.** Store a complete JSON snapshot per locale/version. Restore
normalises the snapshot through the current registry.

**Consequences.** Larger rows, but deterministic restores and simple diffs. No
event-sourcing complexity.

---

## ADR-006 — Explicit workflow state machine

**Context.** "Published" must mean reviewed, and scheduled publishing must be
idempotent.

**Decision.** Statuses on the page, workflow instances/approvals, transactional
transitions, and a scheduler command that locks due rows.

**Consequences.** More tables and tests, but no ambiguous states. The UI can show
a real review trail. Running the scheduler twice is safe.

---

## ADR-007 — Sanctum SPA cookies, not tokens in localStorage

**Context.** The CMS is a first-party SPA. Storing bearer tokens in JavaScript
increases XSS impact.

**Decision.** Sanctum cookie sessions with CSRF, restricted to the Studio origin.
The public API remains stateless.

**Consequences.** Requires correct CORS and stateful-domain configuration. The
Studio must bootstrap CSRF and handle `419` retries. This is implemented centrally
in the axios client.

---

## ADR-008 — SQLite locally, PostgreSQL in Compose

**Context.** Fast local iteration and CI, but production needs concurrency and
JSONB.

**Decision.** SQLite for local/tests, PostgreSQL 17 for Compose and production.
Migrations stay portable; one query branches on driver.

**Consequences.** Some PostgreSQL behaviour is only covered by Compose smoke
tests. JSON columns and case-insensitive search may need tuning at scale.

---

## ADR-009 — Search with a database fallback

**Context.** Meilisearch improves relevance but is another service that may be
absent.

**Decision.** `SearchService` uses Meilisearch when reachable, otherwise a
deterministic database query. The response states which engine served it.

**Consequences.** The site never breaks because search is down. Relevance differs
between environments; the UI is honest about the engine. Indexing is a separate
operational concern: `search:index` rebuilds the published-content documents and
the API container runs it on boot.

---

## ADR-010 — Media on Laravel disks with optional S3

**Context.** Local development should not require object storage, but production
should not store media on the app server.

**Decision.** Use Laravel's filesystem abstraction: `public` locally, `s3`
(MinIO/AWS) in Compose. Generate WebP variants and store their metadata as JSON.

**Consequences.** Same code path in both environments. Variant generation is
CPU-heavy and may need queueing at scale. Seeded media uses the `public` disk even
in Compose, so `/storage` is served by nginx.

---

## ADR-011 — Selective GSAP, progressive enhancement

**Context.** Motion supports the premium brand, but it must not block content or
accessibility.

**Decision.** GSAP handles hero reveals, mask reveals, counters, and one editorial
moment. Content is server-rendered; animation runs after hydration and respects
`prefers-reduced-motion`.

**Consequences.** No motion for reduced-motion users and no content hidden if JS
fails to initialise. The trade-off is that below-fold elements can be hidden until
their scroll trigger fires; tests scroll explicitly.

---

## ADR-012 — Preview tokens in the database

**Context.** Editors need to review unpublished layouts without exposing them.

**Decision.** Issue a short-lived, DB-backed token per page/locale. The public
preview endpoint resolves the page and marks it `noindex,nofollow`.

**Consequences.** Revocable and auditable, unlike a pure signed URL. Requires a
cleanup path for expired tokens.

---

## ADR-013 — Honest integration states

**Context.** Google Search Console, GA4, and GTM cannot be verified without
credentials.

**Decision.** Store integration status and show `disconnected`/`unverified`.
Never fabricate traffic, rankings, or conversions. Test connection before marking
connected.

**Consequences.** The analytics screens show seeded first-party demo data only,
clearly labelled. A reviewer sees a real boundary rather than a mock dashboard
pretending to be live.

---

## ADR-014 — Licensed photography instead of generated imagery

**Context.** Image generation was unavailable (billing), and fake client
photography would be misleading.

**Decision.** Use CC-licensed Wikimedia Commons photography, process it to WebP,
credit the creator and licence on the website and in `CREDITS.md`, and never
claim it depicts a Meridian facility.

**Consequences.** Real, high-quality imagery with legal attribution. The visual
language is documentary rather than bespoke; colour grading and layout carry the
brand.

---

## ADR-015 — Ports 3002 and 3003

**Context.** Local Docker already occupied 3000 and 3001.

**Decision.** Pin the website to 3002 and Studio to 3003 in package scripts and
Nuxt config, and use the same ports in Compose.

**Consequences.** Stable docs, screenshots, CORS, and Sanctum configuration. The
trade-off is a non-default Nuxt port, which is explicit everywhere.

---

## ADR-016 — Demo content is labelled at the data layer

**Context.** A polished site can make fictional claims look real.

**Decision.** Seed records carry `is_demo` and disclaimers; figures include an
illustrative note; legal copy is marked sample; the footer states the brand is
fictional.

**Consequences.** Some UI copy is longer than a real site's. That is the correct
trade for a portfolio demonstration.

---

## ADR-017 — Polling as the realtime fallback

**Context.** Reverb is installed but not always running, especially on Windows.

**Decision.** Package Reverb behind a Compose profile and keep polling in the CMS.
No workflow depends on a socket.

**Consequences.** Slightly stale dashboards when polling, but no broken flows when
Reverb is absent. When enabled, Reverb can be added without changing the domain
model.

---

## ADR-018 — Block renderer uses static imports

**Context.** Dynamic imports would create a network waterfall and complicate SSR.

**Decision.** `BlockRenderer.vue` imports every block component statically.

**Consequences.** A predictable SSR render and no loading flicker. The bundle
includes all block components; at this scale that is preferable to waterfall
latency. Code-splitting can be revisited if the block library grows substantially.

---

## ADR-019 — Editorial art direction with conventional, visible navigation

**Context.** The first visual pass read as a familiar dark hero, pill navigation,
card grid, and oversized sans headline. It did not feel specific to an industrial
engineering practice. A later "field index" experiment replaced the primary menu
with an **Index** button and instrument-style decoration. Users read that as
unclear, badly translated, and more like a portfolio gimmick than a company site.

**Decision.** Keep the editorial art direction but restore conventional wayfinding:

- Desktop shows the primary navigation as visible top-level links with simple
  dropdown panels; there is no "Index" button and no clock or status strip.
- Mobile shows a labelled **เมนู / Menu / 菜单** button that opens a full-screen
  menu with primary links, grouped capabilities, sectors, company links, search,
  and language choices.
- Serif display type (Fraunces for Latin, Noto Serif Thai for Thai), restrained
  offset image frames, row hover states, and a serif footer wordmark carry the
  visual identity instead of numbered rails and instrument labels.
- All visible labels are written in the user's language; internal CSS class names
  may keep historical prefixes but must never surface as copy.

**Consequences.** The site keeps a distinct editorial look without asking visitors
to decode a metaphor before they can navigate. A regression test asserts that the
desktop primary menu is visible, that no header item is labelled "Index", and that
the mobile menu opens full-height with its language controls. Page transitions were
removed because a fast language switch could interrupt the `out-in` transition and
leave two pages half-mounted; a thin loading indicator now covers route latency.

---

## ADR-020 — Locale-specific typography instead of global tracking

**Context.** Global negative tracking and `0.92–0.98` leading suit Latin display
type but collide with Thai stacked vowels, tone marks, and tall ascenders. Thai
headings were visibly cramped.

**Decision.** Use script-aware typography:

- Latin display: Fraunces, `-0.02em` to `-0.025em`, leading `1.02–1.06`.
- Thai display: Noto Serif Thai, **zero tracking**, leading `1.22–1.28`, with
  `!important` overrides so scoped component styles cannot reintroduce negative
  tracking.
- Chinese: sans fallback with relaxed leading and only slight negative tracking.
- Thai body copy keeps `1.6` leading; Thai labels drop the uppercase tracking
  used for Latin mono labels.

**Consequences.** Thai and Latin each look intentional, but the design system has
two display faces and stricter rules to maintain. The regression suite asserts
Thai computed letter-spacing and leading, so a future global style change cannot
silently compress Thai again.

---

## ADR-021 — Navigation data is keyed by locale, not fetched once

**Context.** The header kept the first language's navigation after a language
switch. `useBootstrap` stored the payload in a shared `useState` but only wrote it
while the composable body ran; because the layout persists across route changes,
the body never ran again. A refetch triggered by the locale watcher updated
`useAsyncData` but not the shared state, so the menu could stay Chinese while the
rest of the page was Thai.

**Decision.** Cache bootstrap payloads per locale and mirror every fetch into the
shared state:

- `useAsyncData` is keyed by the active locale, so returning to a language reuses
  its cached navigation immediately.
- A watcher on the fetched payload writes it into `useState('site-bootstrap')`
  on first load and on every locale change.
- The API exposes a locale-independent `key` for page-backed navigation items,
  resolved from the page's fallback-locale slug. The header uses that key to find
  the contact entry, so the CTA points at the localized contact page instead of
  a hardcoded English slug.
- The header no longer closes the full-screen menu on route change; menu links
  and language buttons close it themselves. The previous watcher could close a
  menu the user opened immediately after switching language.

**Consequences.** Header navigation, the mobile menu, and the contact CTA all
follow the selected language in both directions. Regression tests switch
Thai → Chinese → Thai on desktop and mobile and assert the navigation labels,
and a feature test asserts the contact key and localized URL for all three
locales.

---

## ADR-022 — Measured accessibility instead of visual-only polish

**Context.** Automated checks found text below 4.5:1, labels as small as 8.8px,
sub-40px touch targets, a `/` separator leaking into the language button names,
and input focus that removed the global outline. These are common in editorial
designs but fail WCAG AA for real users.

**Decision.**

- Separate brand accent (`#f0521f`) from text accent (`#b23a12`) so accent text
  passes on light backgrounds while decorative accents stay on dark surfaces.
- Darken the muted text tokens (`steel` 2.67:1 → 5.0:1 on ivory; `steel-dark`
  to 6.89:1).
- Enforce a 12px floor for mono/label text and 44px for primary controls.
- Move language separators into `aria-hidden` spans and remove the logo's
  mismatched `aria-label`.
- Restore a visible focus ring on inputs.
- Gate the result with axe-core on ten templates, the mobile menu, and search,
  plus an LCP/CLS/JS-transfer performance budget in the same Playwright run.

**Consequences.** Accessibility is now measurable and fails CI, not a matter of
opinion. The visual language is slightly denser and darker in secondary text;
the trade is legibility. The suite has 13 accessibility checks and 2
performance budgets. Automated checks cannot replace an external audit, and the
accessibility statement says so.

---

## ADR-023 — Granular consent, feeds, and a real second site

**Context.** The consent banner was binary, the insights section had no feed,
and the second website existed only as a site record with no pages, which made
multi-site claims hard to verify.

**Decision.**

- Store consent as a preference object (`analytics`, `updatedAt`) with the
  legacy boolean kept in sync, and expose a re-openable preference dialog from
  the footer.
- Publish per-locale RSS feeds backed by the same public articles API, and
  advertise them with `<link rel="alternate">`.
- Seed Meridian Energy Systems with three published pages, its own navigation,
  contact settings, and search documents. It stays a fictional demonstration
  site and is marked as illustrative.
- Add a static accessibility statement in all three locales rather than a CMS
  page, so it ships with the code that implements it.

**Consequences.** Consent is auditable, feeds are real, and multi-site isolation
is demonstrable end to end. Content editors can still change page copy in the
CMS; the statement and feed are code-owned.
