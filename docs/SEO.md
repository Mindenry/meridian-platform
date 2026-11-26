# SEO

SEO is generated from stored metadata and the real publish state. Nothing is
hard-coded per page in the frontend, and no metric is invented.

## 1. Localized URLs

| Locale | HTML `lang` | Route prefix |
| --- | --- | --- |
| Thai (default) | `th` | `/th` |
| English | `en` | `/en` |
| Simplified Chinese | `zh-CN` | `/zh-cn` |

`/` redirects to `/th`. CMS pages use localized slugs, so the Thai and English
URLs for the same page can differ. Missing translations fall back to another
published translation and the payload sets `is_fallback: true`; the canonical
still uses the requested locale path.

## 2. Canonical and hreflang

For each published page the API returns:

- `seo.canonical` — absolute URL built from `PUBLIC_SITE_URL` plus the localized
  path, independent of the incoming host or query string.
- `alternates[]` — one entry per translation plus `x-default` pointing at the
  default locale.

Nuxt emits these as `<link rel="canonical">` and `<link rel="alternate"
hreflang="…">`. The E2E suite asserts four alternates (`th`, `en`, `zh-CN`,
`x-default`) on the English home page.

Previews are excluded: preview payloads set `robots: noindex,nofollow` and the
preview route is disallowed in `robots.txt`.

## 3. Metadata sources

| Field | Source |
| --- | --- |
| `<title>` / `description` | `seo_metadata` for the page + locale |
| `robots` | Stored per page; legal pages default to `noindex,follow` |
| Open Graph title/description | Stored `og_title` / `og_description`, falling back to SEO title/description |
| Open Graph image | Selected media asset, converted to an absolute URL |
| JSON-LD | `schema_type` plus page/collection data; `Organization` comes from site bootstrap |

The Studio exposes these fields in the SEO tab with a live search-result preview
and a character-count warning. Saving writes through `PUT
/admin/pages/{page}/seo`.

## 4. Structured data

- `Organization` for the brand, with fictional contact data clearly labelled.
- `WebPage` / `AboutPage` / `CollectionPage` / `Article` / `JobPosting` depending on
  the content type.
- `BreadcrumbList` from the breadcrumb payload.
- Article and case-study schema only uses stored fields; no ratings, reviews, or
  awards are fabricated.

JSON is escaped (`<` → `\u003c`) before being embedded to avoid breaking out of
the script tag.

## 5. Sitemaps and robots

The website exposes:

- `/sitemap.xml` — sitemap index with `th`, `en`, `zh-cn` children.
- `/sitemaps/{locale}.xml` — URLs for that locale, including pages and published
  editorial content.
- `/robots.txt` — allows public pages, disallows `/api/`, `/preview/`, and
  `/search`, and points at the sitemap index.

Sitemap URLs come from the API (`/public/sitemap`), so unpublished, scheduled, or
deleted content disappears automatically. `lastmod` uses content update time.
Sitemap responses are cached publicly for 15 minutes.

## 6. Redirects

`redirects` supports site-wide or locale-specific `from_path` → `to_path` with
301/302 and hit counting. The public resolver (`/public/resolve`) checks a page
first, then an active redirect, and the website navigates accordingly. Redirects
are managed in Studio → SEO. Deleting content does not silently create a redirect;
an editor must add one, which keeps intent explicit.

## 7. SEO audit

`SeoAuditService` runs deterministic checks and stores issues with severity:

- Missing or duplicate title/description.
- Title and description length outside recommended ranges.
- Missing canonical or canonical pointing at another host.
- Missing focus keyword or keyword absent from title/description.
- Noindex on a page that is meant to be public.
- Missing Open Graph image or description.
- Missing localized translations for enabled locales.
- Orphan pages (no navigation or internal link reference).
- Broken internal links found in resolved blocks.
- Images without alt text.

The Studio SEO screen shows a score, issue counts, filters, and a "run audit"
action. Scores are computed from the stored issue set, not from a third-party
service.

## 8. Performance and rendering

- The website is SSR, so crawlers receive metadata and body content in the first
  response.
- Fonts are self-hosted through Fontsource; no third-party font request.
- Images are served as WebP with width/height attributes and responsive variants.
- GSAP animation is progressive: content exists in HTML before motion runs, and
  `prefers-reduced-motion` skips non-essential motion.
- The hero image is preloaded and non-critical sections use lazy loading.

## 9. Integrations

Google Search Console, GA4, and GTM require credentials that are not available in
this environment. The CMS stores an explicit `disconnected` state and never shows
placeholder traffic, rankings, or conversion numbers. When credentials are added,
the integration test endpoint validates the connection before marking it
`connected`.

First-party analytics are separate: the website records consented, privacy-conscious
events to `/public/analytics/events`. The Studio labels all seeded values as
illustrative demo data.

## 10. Verification

- Pest: metadata payload, canonical, alternates, sitemap entries, audit issues.
- Playwright: `lang`, title, canonical, four hreflang links, JSON-LD parse,
  sitemap index and locale sitemap, robots directive.
- Manual: preview tokens return `noindex`; legal pages are `noindex,follow`;
  unpublished pages return 404 on the public site.
