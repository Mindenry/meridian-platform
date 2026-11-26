# Page builder

MERIDIAN STUDIO's visual builder edits the same structured blocks the website
renders. It is not a freeform HTML editor.

## 1. Server authority

`BlockRegistry` is the single contract:

- Allowed types and categories.
- Field definitions: type, label, default, options, min/max.
- Normalisation: unknown fields dropped, defaults applied, limits enforced.
- Sanitisation: rich text and HTML embeds pass through Symfony HTML Sanitizer;
  unsafe URLs are rejected.
- Version: `SCHEMA_VERSION` is stored on `page_translations` and used when
  restoring old revisions.

The Studio fetches `GET /admin/block-types` and renders the palette and inspector
from that schema. Adding a field on the server is enough for it to appear in the
inspector; no hand-written form is needed.

## 2. Layout

```
┌───────────────┬───────────────────────────────┬──────────────────┐
│ Block palette │ Canvas                        │ Inspector        │
│ search        │ device width (desktop/tablet/ │ selected block   │
│ categories    │ mobile), drag handles,        │ fields, repeaters│
│ click or drag │ inline preview, duplicate,    │ remove / reset   │
│               │ delete                        │                  │
└───────────────┴───────────────────────────────┴──────────────────┘
```

- The palette groups blocks by category and supports text search.
- Blocks can be added by click or by dragging from the palette onto the canvas.
- Canvas order is the published order; `vue-draggable-plus` handles reordering.
- The device switcher constrains the canvas width to preview responsive layout.
- Selecting a block opens its inspector with the registry-driven fields.
- Image fields open the media library; link fields show label/URL/style.

## 3. Editing behaviour

- **Autosave:** changes are debounced (1.2s) and sent as a full ordered block
  array for the current locale. A manual save button is also available.
- **History:** the builder keeps up to 60 snapshots in memory. Undo/redo restore
  a snapshot and trigger autosave. History resets when the page/locale loads.
- **Dirty/save state:** `saved`, `unsaved`, `saving`, and `error` are shown; a
  failed save raises a toast with the API message.
- **Revisions:** every save creates a server-side revision snapshot. The page
  editor's History tab lists versions and can restore one.
- **Optimistic concurrency:** page-level edits send `lock_version`; a conflict
  returns `409` rather than overwriting another editor.
- **Preview:** a short-lived token opens the public preview URL with
  `noindex,nofollow`, so an unpublished layout can be reviewed without exposing it.
- **Locale:** the builder edits one locale at a time. Missing translations are
  flagged; the page editor can add them.

## 4. Rendering parity

`BlockRenderer.vue` maps each block type to a component:

| Type | Component |
| --- | --- |
| hero | `BlocksHero` |
| split_hero | `BlocksSplitHero` |
| statement | `BlocksStatement` |
| capabilities | `BlocksCapabilities` |
| services_grid | `BlocksSolutionsGrid` |
| featured_project | `BlocksFeaturedProject` |
| project_grid | `BlocksProjectGrid` |
| industry_grid | `BlocksIndustryGrid` |
| statistics | `BlocksStatistics` |
| technology_ecosystem | `BlocksTechnology` |
| latest_insights / article_list | `BlocksInsights` |
| cta | `BlocksCta` |
| rich_text | `BlocksRichText` |
| image / image_gallery | `BlocksImage` / `BlocksImageGallery` |
| video | `BlocksVideo` |
| timeline | `BlocksTimeline` |
| team_grid | `BlocksTeamGrid` |
| quote | `BlocksQuote` |
| mission_values | `BlocksMissionValues` |
| contact_form | `BlocksContactForm` |
| contact_cards | `BlocksContactCards` |
| job_list | `BlocksJobs` |
| document_list | `BlocksDocumentList` |
| investor_highlights | `BlocksInvestorHighlights` |
| html_embed | `BlocksHtml` |

The preview uses the same public presenter and block components, so "it looks
right in the builder" and "it looks right live" are the same statement.

## 5. Responsive behaviour

Each block stores `is_visible_desktop`, `is_visible_tablet`, `is_visible_mobile`,
and optional overrides (spacing, background, alignment). The API normalises these
flags; the website applies visibility classes and layout tokens. The device
switcher is a preview aid, not a separate document.

## 6. Accessibility

- The palette is keyboard reachable and searchable.
- Drag handles are buttons with labels; reordering is also possible with the
  block's move actions.
- The inspector uses labelled inputs and error text tied to fields.
- The preview keeps the website's focus styles, skip link, and reduced-motion
  behaviour.
- Images require alt text at the media level; empty alt is allowed only for
  decorative images and is explicit.

## 7. Constraints

- Maximum 40 blocks per page/locale (enforced server-side).
- Repeaters have min/max item counts per block definition.
- Rich text is sanitised; scripts, event handlers, and unsafe schemes are removed.
- HTML embeds are allow-listed and iframes restricted to approved hosts.
- Slug uniqueness is enforced per site + locale, with clear errors.
- Published pages require `pages.publish` to edit; the builder disables editing
  when the API says the user lacks the capability.

## 8. Adding a block type

1. Add the definition to `BlockRegistry::definitions()` with fields, defaults,
   category, and limits.
2. Add sanitisation rules for new rich fields and URL options.
3. Create a Vue component in `apps/website/app/components/BlocksX.vue`.
4. Register it in `BlockRenderer.vue`.
5. Add resolution logic in `BlockResolver` if the block references other content.
6. Add a Pest test for validation and a rendering check if the block has logic.
7. Add a seeded example and update this document.

The Studio palette and inspector pick up the new definition automatically.
