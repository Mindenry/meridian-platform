# Testing

## 1. Test layers

| Layer | Tool | Scope |
| --- | --- | --- |
| Unit | Pest / PHPUnit | Trivial unit sanity |
| Feature | Pest + Laravel HTTP | Auth, RBAC, workflow, scheduling, validation, media, forms, SEO, search, isolation |
| End-to-end | Playwright + Chrome | Public rendering, CMS publishing, real submissions, multi-site denial |
| Accessibility | Playwright + axe-core | WCAG 2.2 A/AA checks on ten templates, the mobile menu, and search |
| Performance | Playwright + PerformanceObserver | LCP, CLS, JS transfer budget, hero image decode |
| Build checks | Nuxt/Vite production builds, TypeScript | SSR bundle, Studio bundle, type errors |
| Static checks | Pint | PHP formatting |

## 2. Backend suite

30 tests / 126 assertions currently pass.

```bash
cd apps/api
php artisan test --compact
```

The suite seeds a fresh database for every test through `FeatureTestCase`, so
tests do not depend on local data.

| File | Covers |
| --- | --- |
| `PageWorkflowTest` | Login success/failure, scoped permissions, create → submit → approve → publish, editor cannot publish, cannot publish while in review, scheduled publication runs exactly once, public job detail with schema |
| `BlockValidationTest` | Unknown block type rejected, unsafe CTA URL rejected, scripts stripped from safe HTML embeds while allowed markup survives |
| `MediaTest` | Real image upload with generated WebP variants, MIME allow-list, alt/caption storage, delete blocked while referenced, unused delete |
| `FormsAndLeadsTest` | Public submission creates lead, required fields/consent enforced, authorised update and CSV export |
| `MultiSiteIsolationTest` | Cross-site admin request denied, public request resolves correct site, public content scoped, the second site has its own pages and nav |
| `SearchTest` | Locale-scoped grouped results, drafts/admin-only content excluded from public search |
| `SeoTest` | Deterministic audit with explainable issues, canonical + four hreflang entries, sitemap contains canonical public URLs |
| `BootstrapNavigationTest` | Public bootstrap exposes a stable `key` for page-backed navigation, with the contact entry carrying the correct localized label and URL in Thai, English, and Chinese |

Run a single file or test:

```bash
php artisan test tests/Feature/PageWorkflowTest.php
vendor/bin/pest --filter="publishes_a_scheduled_page"
```

## 3. End-to-end suite

41 Playwright tests using Google Chrome.

```bash
# prerequisites: API on 8000, website on 3002, Studio on 3003
cd apps/e2e
npx playwright test
```

| Spec | Scenario |
| --- | --- |
| `public-site.spec.ts` | Localized pages (`lang`, title, canonical, four hreflang links, parseable JSON-LD), sitemap index + three locale sitemaps + robots, search overlay results, public enquiry creates a lead visible in the CMS |
| `studio-workflow.spec.ts` | Administrator signs in and submits → approves → publishes in the UI; public URL serves the page. Content editor is denied approve/publish and the page stays private until an administrator completes the flow |
| `scheduling-and-isolation.spec.ts` | Schedule a page, run the real scheduler command, verify it publishes. Energy site admin cannot read Meridian admin pages; anonymous admin access returns 401; public content is site-scoped |
| `ux-regressions.spec.ts` | Desktop primary menu is visible with no “Index” label; mobile **เมนู** menu opens full-screen with navigation and language; header and mobile menu navigation follow the selected language in both directions; language switching after client navigation; mobile language switching; clean fallback URLs; Thai heading metrics; hero actions stay in the first screen; public job detail with structured data |
| `a11y.spec.ts` | axe-core WCAG 2.2 A/AA scan of twelve templates in three locales, the mobile menu, and the search overlay; visible keyboard focus on the skip link |
| `performance.spec.ts` | Local LCP below 3s, CLS below 0.1, JS transfer below 2.5 MB, hero image decoded |
| `content-and-consent.spec.ts` | Cookie preference dialog persists granular consent and reopens from the footer; search remembers recent queries; accessibility statement exists in all locales; RSS feeds return items and are advertised in the head; the second site has isolated navigation and pages |

The suite uses the seeded demo database and creates uniquely named fixtures. Page
and lead fixtures are deleted in `finally` blocks. The scheduling test runs
`php artisan content:publish-scheduled --sync` from the test process; set
`PHP_BIN` if PHP is not on `PATH`.

The suite totals 41 scenarios: 9 critical journeys, 10 UX regressions, 15
accessibility checks, 2 performance budgets, and 5 content/consent/feed checks.

To run the same suite against the Compose stack (PostgreSQL, Meilisearch, MinIO),
with the scheduler command executed inside the API container:

```bash
E2E_DOCKER=1 npx playwright test
```

This was verified with 41 passing tests against the running stack.

### Hydration and reveal handling

Two waits matter:

- `dismissConsent(page)` waits for the consent banner, which only appears after
  Nuxt hydration, then dismisses it. Filling before hydration can be undone by the
  client-side initialisation.
- The contact form is below the fold inside a GSAP reveal. The test scrolls it into
  view and waits for visibility before typing, matching how a person reaches it.

These are test-harness concerns, not production workarounds.

## 4. Frontend checks

```bash
pnpm lint
pnpm --filter @meridian/website build
pnpm --filter @meridian/studio build
```

`pnpm lint` runs `nuxt typecheck` and `vue-tsc -b`. The production builds are the
real gate for SSR and bundle errors; a dev server can hide a failure that only
appears in a production build.

## 5. CI

`.github/workflows/ci.yml` has three jobs:

1. **api** — PHP 8.5, Composer install, Pint test, Pest, Composer audit.
2. **frontend** — pnpm install, typecheck, website build, Studio build.
3. **e2e** — prepares SQLite and seeds, starts API/website/Studio, waits for
   readiness, installs Playwright's browser dependencies, runs the suite, uploads
   traces/screenshots and server logs on failure.

The E2E job depends on the API job so a broken backend fails fast.

## 6. Manual verification checklist

- [ ] Home renders in TH/EN/ZH-CN and the locale switcher preserves the page.
- [ ] Navigation, footer, and legal pages work in all locales.
- [ ] Search returns grouped results and the empty state is readable.
- [ ] Contact form submits, shows the reference, and appears in Leads.
- [ ] Media upload generates variants and refuses deletion while used.
- [ ] Draft preview opens with an expiring token and is `noindex`.
- [ ] Submit → request changes → resubmit → approve → publish works.
- [ ] Scheduled page publishes after `content:publish-scheduled`.
- [ ] Editor cannot publish; reviewer can approve but not publish unless granted.
- [ ] Energy admin cannot open Meridian pages.
- [ ] Sitemap and robots respond with the correct hosts.
- [ ] Reduced-motion mode removes reveal animation without hiding content.
- [ ] Mobile layout has no horizontal scroll and tap targets remain usable.

## 7. Known test limitations

- Tests run against SQLite; PostgreSQL-specific JSON behaviour is covered by
  Compose smoke tests, not the Pest suite.
- Meilisearch is not exercised when unavailable; the fallback path is what the
  suite asserts.
- Reverb realtime delivery is not tested.
- Third-party Google integrations are intentionally untested because no
  credentials exist.
- Playwright requires Chrome and three running servers; it is not a unit test
  substitute.
