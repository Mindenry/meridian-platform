# Database

The schema is portable between SQLite (local and tests) and PostgreSQL 17
(Compose and production). Migrations avoid engine-specific SQL; the only
driver-aware query is the JSON text search in `MediaService::usageReport`.

## 1. Entity groups

### Identity and tenancy

| Table | Purpose | Key constraints |
| --- | --- | --- |
| `users` | CMS accounts plus profile fields, soft deletes | unique `email` |
| `sites` | One row per website; brand, theme, locales, SEO defaults | unique `slug`, unique nullable `domain` |
| `site_locales` | Enabled locales per site with labels and ordering | unique `(site_id, locale)` |
| `roles` | System role presets with a numeric level | unique `slug` |
| `permissions` | Granular capability catalogue | unique `slug` |
| `permission_role` | Role → permission | composite primary key |
| `role_user` | User → role → **site** | unique `(user_id, role_id, site_id)` |
| `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `personal_access_tokens` | Framework infrastructure | — |

`role_user.site_id` is nullable only for global roles such as `super_admin`.
Application code still checks `is_super_admin` explicitly.

### Media

| Table | Purpose |
| --- | --- |
| `media_folders` | Hierarchical folders per site |
| `media_assets` | File metadata: disk, path, checksum, MIME, dimensions, alt/caption JSON, credit, licence, source, focal point, variants JSON, usage count |
| `media_collections`, `media_collection_items` | Curated sets |
| `media_usages` | Polymorphic explicit references |

Assets are deduplicated per site by `checksum`. Variants are stored as JSON, not
rows, because they are always read with the parent asset.

### Pages and SEO

| Table | Purpose | Notable columns |
| --- | --- | --- |
| `pages` | Page identity, template, status, author, featured image, schedule, lock version, soft deletes | indexes on `(site_id, status)` and `(site_id, parent_id)` |
| `page_translations` | Localized title, slug, excerpt, block schema version | unique `(page_id, locale)` and `(site_id, locale, slug)` |
| `page_blocks` | Ordered block instances per locale | unique `(page_id, locale, block_uid)`, index `(page_id, locale, sort_order)` |
| `page_revisions` | Full JSON snapshot per locale/version | unique `(page_id, locale, version)` |
| `page_preview_tokens` | Expiring preview links | unique `token` |
| `scheduled_publications` | Pending/processing/published/failed schedules | index `(status, publish_at)` |
| `seo_metadata` | Polymorphic localized SEO for pages and editorial content | index on `(seoable_type, seoable_id, locale)` |
| `seo_audits` | Audit run summary and score |
| `seo_issues` | Deterministic issue rows with severity and resolution state |
| `redirects` | Localized or site-wide redirects with hit counts | unique `(site_id, locale, from_path)` |

`pages.status` and `scheduled_publications.status` are strings rather than database
enums so the state machine can evolve without a destructive migration.

### Editorial content

| Table | Purpose |
| --- | --- |
| `solutions`, `solution_translations` | Six capability areas |
| `industries`, `industry_translations` | Five sectors |
| `industry_solution` | Many-to-many relevance between sectors and capabilities |
| `case_studies`, `case_study_translations` | Six illustrative projects |
| `case_study_solution` | Project → capability links |
| `article_categories`, `articles`, `article_translations` | Insights and news |
| `tags`, `article_tag` | Editorial tags |
| `content_revisions` | Snapshots for editorial types |
| `team_members`, `milestones` | Leadership and company timeline |
| `job_postings`, `job_posting_translations`, `job_applications` | Careers and applications |
| `documents`, `financial_highlights` | Investor relations material |
| `navigation_menus`, `navigation_items` | Localized header/footer menus |

Editorial tables keep their own translation tables instead of sharing the page
tables. Their fields and query patterns differ enough that a shared polymorphic
translation table would add joins without reducing code.

### Forms, leads, workflow, analytics

| Table | Purpose |
| --- | --- |
| `forms` | Key, localized name/description, JSON field schema, settings |
| `form_submissions` | Raw submission data, locale, page, IP, user agent, spam score, status |
| `leads` | Qualified enquiry with owner, status, priority, follow-up |
| `lead_activities` | Notes, calls, emails, status changes |
| `workflow_definitions` | Review steps per site/content type |
| `workflow_instances` | One active review per submission |
| `workflow_approvals` | Submitted/approved/changes-requested decisions with notes |
| `analytics_events` | First-party events: name, path, locale, referrer, metadata |
| `audit_logs` | Who did what, to which subject, with IP/user agent |
| `settings`, `integration_settings` | Public/site settings and honest integration state |

## 2. Relationships (simplified)

```
sites 1─* site_locales
sites 1─* pages 1─* page_translations
               1─* page_blocks
               1─* page_revisions
               1─* scheduled_publications
sites 1─* articles 1─* article_translations
sites 1─* case_studies 1─* case_study_translations
sites 1─* media_assets 1─* media_usages
users *─* roles *─* permissions        (role_user carries site_id)
sites 1─* forms 1─* form_submissions 1─0..1 leads 1─* lead_activities
pages 1─* workflow_instances 1─* workflow_approvals
```

Polymorphic links use `seoable_type`/`seoable_id` for SEO and
`usable_type`/`usable_id` for explicit media usage. All polymorphic writes are
site-checked before they are stored.

## 3. Indexing strategy

- Every tenant table starts with `site_id`; composite indexes lead with it.
- Localized slug lookups use the unique `(site_id, locale, slug)` index on
  `page_translations` and equivalent indexes on editorial translations.
- Publishing queries use `(site_id, status)` and
  `(status, publish_at)` for due schedules.
- Audit and analytics tables index `(site_id, created_at)` / event name + date.
- JSON columns are not indexed; filters on JSON happen only in admin lists with
  bounded result sets, or through Meilisearch.

## 4. Migrations and seeding

Migrations run in filename order. The domain migrations are:

```
2026_11_26_110000_create_sites_and_access_tables
2026_11_26_110100_create_media_tables
2026_11_26_110200_create_pages_and_seo_tables
2026_11_26_110300_create_editorial_tables
2026_11_26_110400_create_cms_operations_tables
```

`DatabaseSeeder` runs, in order: sites, access, media, solutions/industries, case
studies, insights, company, careers, pages, navigation, forms, operations,
analytics, SEO. The seeders use `updateOrCreate` where re-running is intended to be
safe. Demo business data is labelled `is_demo` or carries an illustrative note.

To rebuild local data:

```bash
cd apps/api
php artisan migrate:fresh --seed
php artisan storage:link
```

## 5. PostgreSQL notes

- JSON columns map to `jsonb` in PostgreSQL. The usage-report query uses
  `props::text like ?`; SQLite uses `props like ?`.
- Case-insensitive search uses `like`; performance is acceptable at demo scale.
  For production scale, move article/page filters to Meilisearch or add
  `pg_trgm` indexes.
- The Compose stack uses PostgreSQL 17 with a named volume. Backups should use
  `pg_dump -Fc` and be restored with `pg_restore`.
- SQLite is intentionally not used for concurrent production writes.

## 6. Data retention and deletion

- Users, sites, pages, and media use soft deletes where an undo path matters.
- Deleting a page cascades translations, blocks, revisions, preview tokens, and
  schedules. Audit logs keep the action with nullable subject references.
- Media deletion is blocked while references exist, returning `media_in_use`.
- A right-to-be-forgotten workflow is documented but not automated; submission and
  lead deletion can be performed through the CMS or a queued maintenance job.

See [CONTENT_MODEL.md](CONTENT_MODEL.md) for how these tables are used in the
editorial workflow.
