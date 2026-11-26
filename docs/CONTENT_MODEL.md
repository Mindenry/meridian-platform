# Content model

## 1. Principles

1. **Localized by default.** Every reader-facing string belongs to a locale. Thai
   is the default; English and Simplified Chinese are enabled per site.
2. **Structured over freeform.** Pages are ordered blocks, not one HTML blob.
   Editors can compose, but the server owns the schema.
3. **Publish state is explicit.** Draft, in review, changes requested, approved,
   scheduled, published, archived. Public queries only see published rows.
4. **Illustrative content is labelled.** Demo records carry `is_demo` or a
   disclaimer, and seeded figures are never presented as real results.
5. **One source of truth per concept.** Solutions, industries, case studies,
   articles, jobs, documents, and team members are entities, not page copies.

## 2. Site and locale

A `site` has a slug, optional domain, default locale, enabled locales, brand
tokens, theme tokens, and SEO defaults. The demo has `meridian` (primary) and
`meridian-energy` (a second site used to prove isolation).

Localized routes are configured centrally:

| Locale code | Route | HTML lang |
| --- | --- | --- |
| `th` | `/th` | `th` |
| `en` | `/en` | `en` |
| `zh-CN` | `/zh-cn` | `zh-CN` |

Slugs are localized too. A page can be `about` in English and `เกี่ยวกับเรา` in
Thai. The fallback rule is: requested locale → any published translation, with
`is_fallback` set.

## 3. Pages

`pages` stores identity and publishing state; `page_translations` stores title,
slug, excerpt, and block schema version; `page_blocks` stores ordered blocks per
locale.

A page has:

- Template: `default`, `legal`, or a collection-specific template.
- Parent (for hierarchy and breadcrumbs).
- Author/editor references.
- Featured image.
- Lock version for optimistic concurrency.
- `is_home` for the single homepage.
- Publish and schedule timestamps.

Page kinds in the seed:

- Home, About, Contact, Careers, Investor relations.
- Four legal pages: privacy, cookies, terms, data protection.
- Collection landing pages where a CMS page is useful.

Collection detail pages (a solution, article, case study, job) are **not** pages;
they are their own entities with dedicated routes and layouts.

## 4. Blocks

A block is `{ type, props, responsive }`. The canonical types are defined in
`BlockRegistry` (27 total), grouped as:

- **Headers:** hero, split hero, page header.
- **Editorial:** statement, rich text, quote, image, image gallery, video,
  timeline, mission and values.
- **Services:** capability index, solutions grid, technology ecosystem.
- **Portfolio:** featured project, project grid, industry grid, case-study list.
- **People and proof:** statistics, team grid, investor highlights.
- **Conversion:** CTA, contact form, contact cards, job list, document list.
- **Utility:** HTML embed (sanitised), latest insights, article list.

Each field has a type, label, default, and validation rules. Repeaters have item
schemas and min/max counts. Image fields store `media_id`, URL, and alt text.

Resolution: `BlockResolver` turns references into display data in batches, so a
`solutions_grid` block does not issue one query per card.

## 5. Editorial entities

| Entity | Localized fields | Relationships |
| --- | --- | --- |
| Solution | name, slug, summary, body, outcomes | many industries, many case studies |
| Industry | name, slug, summary, body | many solutions |
| Case study | title, slug, summary, challenge, approach, outcome, results | industry, solutions, media |
| Article | title, slug, excerpt, body, type (insight/news), published date | category, tags, author, media |
| Job | title, slug, location, type, description, requirements | applications |
| Document | title, year, type, file | media |
| Team member | name, role, bio, focus | photo, order |
| Milestone | year, title, description | — |

Results and financial figures are stored as text/structured rows with an explicit
disclaimer. The model does not contain a field for "verified client logo" or
"certification" because the brand has none.

## 6. Media

Media assets store:

- Disk (`public` or `s3`), path, filename, original name, MIME, extension, size.
- Width/height, focal point.
- Localized alt and caption.
- Credit, copyright, licence, source URL.
- Generated variants (thumb/medium/large) as JSON.
- Checksum for deduplication and usage count.

Folders and collections organise assets. Explicit `media_usages` complement the
JSON reference scan used to warn before deletion.

## 7. Navigation

Menus are per site and per locale, with items that can point to internal paths or
external URLs and can nest one level. The website builds the header and footer
from `/public/bootstrap`; the Studio edits the same records. Labels are localized;
URLs usually include the locale prefix but the website normalizes them.

## 8. Forms and leads

A form has a key (`contact`, `partnership`, `general`), localized name and
description, a JSON field schema, and settings:

- `creates_lead` — whether an accepted submission becomes a lead.
- `confirmation_message` — shown after submission.
- `notification_email` — displayed/used by the notification boundary.

Fields support text, email, phone, number, date, textarea, select, radio,
checkbox, consent, and file, with required, width, placeholder, and option labels.

A submission stores raw data, locale, page, source, elapsed time, IP, user agent,
and spam score. An accepted submission creates a lead with status, priority,
source, and an initial activity. The inbox supports assignment, status, priority,
follow-up date, notes, and CSV export.

## 9. Workflow

Each site can have workflow definitions. The default page workflow is a single
editorial approval step; the service supports multiple steps.

States and transitions:

```
draft ──submit──▶ in_review ──approve──▶ approved ──publish──▶ published
  ▲                   │                                  │
  │                   └──request changes──▶ changes_requested
  └───────────────────────────────────────────────────────┘ unpublish
approved ──schedule──▶ scheduled ──scheduler──▶ published
```

Every transition writes a workflow approval or audit entry, bumps the lock
version, and notifies the relevant users.

## 10. Demo data rules

- Company name, people, clients, projects, certifications, addresses, emails, and
  phone numbers are fictional.
- Financial figures, efficiency percentages, and project outcomes are clearly
  labelled illustrative.
- Photography is licensed documentary imagery, not Meridian facilities; credits
  are on the website and in `CREDITS.md`.
- Seeded analytics are generated demo events, never presented as real traffic.
- Legal pages contain sample copy and a disclaimer; they are not legal advice.

## 11. Adding a content type

1. Add a migration for the entity and translation tables, with site and locale
   indexes.
2. Add models, casts, relationships, and a `forSite` scope.
3. Add an admin controller following the existing list/detail/create/update/delete
   pattern and permission slugs.
4. Add public endpoints and a presenter method.
5. Add a Nuxt route/component and, if reusable, a block type.
6. Seed demo data with an explicit illustrative label.
7. Add a Pest feature test and, if it is a key journey, a Playwright scenario.
8. Update this document and the OpenAPI contract.
