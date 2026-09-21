# Aura SEO Product and Technical Specification

**Status:** V1 implemented; pending product review
**Prepared for:** Enes and the Aura CMS team  
**Date:** 21 September 2026  
**Prototype:** `docs/prototypes/seo-product/index.html`

## 1. Decision requested

Approve the product structure, permissions, and implementation sequence for the next Aura SEO iteration. The proposal keeps SEO inside the normal Aura administration experience, makes problems actionable, and avoids rebuilding a full third-party SEO suite.

The accompanying prototype contains ten rough screen outlines and three alternative overview layouts. These outlines are for reviewing product structure and flow only; they are not approved production markup or final copy.

### Review decisions from 21 September 2026

- Production must use Aura's existing settings layout, fields, tabs, buttons, notices, role editor, responsive behavior, and styling wherever possible.
- Prototype HTML and CSS must not be copied into production. Custom markup or styling is appropriate only when Aura has no suitable component.
- End-user copy should explain the task and result, not internal storage, registration, authorization, or connector behavior.
- V1 diagnostics will be a simple list of items to review, with direct links to the relevant setting or record.
- Aura SEO registers its permissions. Permission assignment remains in Aura's existing role edit page; the plugin will not add a separate permissions settings tab or capability matrix.

## 2. Product outcome

Aura SEO should let an administrator answer four questions without leaving Aura:

1. Is SEO configured safely for this site and Team?
2. Which Resource types participate in SEO, and what defaults do they inherit?
3. What will a specific record look like in search and social previews?
4. What is wrong, where is it wrong, and what is the safest next action?

The plugin should remain fail-closed. Installation alone must not expose Resources, publish sitemap entries, or enable indexing.

## 3. Scope

### Included

- One Team-scoped SEO settings area under **Settings > SEO**.
- A clear overview with setup state and prioritized actions.
- Site identity, canonical URL, title pattern, locale, social-image defaults, and robots defaults.
- A per-Resource overview showing registration, indexing, sitemap, title source, description source, and default pattern.
- Per-Resource Team overrides for enablement and presentation defaults, while public enumeration remains registered in code.
- A focused SEO editor inside each participating record.
- Search and social previews using resolved metadata.
- AI-generated title and description suggestions through Aura's core AI connector.
- Simple SEO checks with direct links to the relevant setting or record.
- Sitemap and robots status, route visibility, last generation information, and safe controls.
- SEO permissions registered by the plugin and shown in Aura's existing role editor.

### Not included

- Keyword scoring, readability scoring, or traffic-light writing advice.
- Rank tracking, backlink monitoring, or search-volume research.
- Automatic public exposure of unregistered Resources.
- Automatic rewriting of record content.
- AI changes saved without an explicit editor action.
- Provider API keys or provider-specific logic inside Aura SEO.
- Redirect management; broken canonical targets may link to Aura Redirects when installed.

## 4. Information architecture

The SEO settings entry remains in the standard Aura settings sidebar. Inside the SEO page, use a local navigation row:

| Area | Purpose |
| --- | --- |
| Overview | Setup progress, last check, and next actions |
| Site defaults | Site identity, title pattern, default description, locale, social images, and default robots behavior |
| Content types | Registered Resources and their Team-level SEO behavior |
| SEO checks | A short, actionable list of site and record issues |
| Sitemap & robots | Public route status, source coverage, and robots rules |

The Resource editor receives a single **SEO** tab. The tab is not a second settings system; it only stores record-level overrides.

## 5. Users and permissions

| User | Access |
| --- | --- |
| Super Admin / Global Admin | Full SEO settings and diagnostics access |
| User with `manage-aura-seo` | Edit site defaults, Resource overrides, sitemap/robots behavior, and record metadata |
| User with `diagnose-aura-seo` | View diagnostics and open affected records; cannot change global configuration |
| Resource editor without SEO permissions | Edit ordinary Resource fields; SEO tab is hidden or read-only according to the host policy |

Aura SEO registers the packaged permissions during installation. Administrators assign them with all other permissions on Aura's existing role edit page. There is no standalone SEO permissions page or capability matrix.

Authorization must still be enforced by the implementation, but those internal rules should not be presented as explanatory UI copy. AI generation uses the same edit access as the target SEO fields and keeps the existing rate limit.

## 6. Configuration model

### 6.1 Site settings

Stored through the Aura core `SettingsStore`, scoped to the current Team:

- `enabled`
- `site_name`
- `canonical_base_url`
- `separator`
- `title_pattern`
- `default_description`
- `default_open_graph_image`
- `default_twitter_image`
- `locale`
- `robots_index`
- `robots_follow`
- `robots_rules`
- `sitemap_enabled`
- `robots_route_enabled`

The plugin must never render secret values and does not store provider credentials.

### 6.2 Resource definition

Each public Resource remains explicitly registered in application code with `SeoResourceDefinition`. Code registration owns security-sensitive behavior:

- Resource class and stable source key
- Public URL resolver
- Enumeration query
- Record visibility predicate
- Field mappings for title, description, image, and last-modified time
- Whether sitemap participation is technically supported

The administration UI must not let an editor invent an enumeration query or weaken the visibility predicate.

### 6.3 Team-level Resource overrides

For registered Resources only, settings may provide presentation and exposure overrides:

- SEO output enabled
- Sitemap inclusion enabled, if supported by the definition
- Default title pattern
- Default description
- Default social image
- Default index/follow values

These values are stored under stable setting keys derived from the Resource source key. Removing a registration leaves inert settings that can be pruned safely.

### 6.4 Record overrides

Record metadata continues to use Aura fields/meta:

- SEO slug
- Meta title and description
- Canonical override
- Index and follow overrides
- Open Graph title, description, and image
- Twitter title, description, image, and card type

Blank values inherit rather than erase defaults.

## 7. Resolution order

Resolved metadata must remain deterministic:

1. Record override
2. Registered Resource mapping
3. Team-level Resource default
4. Team-level site default
5. Application/config fallback
6. Fail-closed fallback

Security boundaries are never inherited from presentation fields. A Resource without both an enumeration query and a record visibility predicate is never exposed by sitemap or public metadata resolution.

## 8. Title patterns and automatic defaults

The default pattern is:

```text
[Post Title] [Separator] [Site Name]
```

Supported tokens for the first iteration:

- `[Post Title]`
- `[Separator]`
- `[Site Name]`

The editor shows a live example beside the pattern. New records may receive a calculated meta title on save only when the meta title is blank. An existing manual value is never overwritten.

## 9. Record editing experience

The SEO tab uses three sections rather than exposing every field at the same visual weight:

1. **Search** - meta title, meta description, slug, canonical URL, and Google-style preview.
2. **Social** - shared social title, description, and image first; platform-specific overrides are collapsed under Advanced.
3. **Advanced** - index/follow and platform-specific overrides.

The title and description show character guidance, not a hard quality score. Preview data is labeled as an approximation.

## 10. AI-assisted metadata

Aura SEO calls the stable core AI connector contract. Provider-specific behavior remains in Aura core rather than the SEO user interface.

### Input

- Resource type
- Record title
- Clean text excerpt from the mapped description/content field
- Site name and locale
- Existing metadata, when present
- Requested length constraints

### Output

- One suggested meta title, maximum 60 characters
- One suggested meta description, maximum 160 characters

### Interaction rules

- **Generate suggestion** never saves.
- Existing and suggested values are shown side by side.
- The editor may apply title, description, or both.
- Applying changes updates the form only; the normal record Save action persists them.
- Failure leaves current form values untouched and displays a retryable error.
- Inputs and outputs must not be logged with provider credentials.

## 11. Diagnostics

SEO checks become a first-class settings tab instead of a detached report. V1 deliberately stays small and task-focused.

Each issue includes:

- A short, human-readable problem
- The affected setting, content type, or record
- One direct action: open settings, open content defaults, or edit the record

Initial checks:

- SEO disabled
- Missing or invalid canonical base URL
- Default indexing disabled
- Missing site description or social image
- Sitemap-capable Resource disabled or empty
- Record missing title or description
- Duplicate or invalid canonical URL
- Record excluded from index while present in sitemap

V1 does not require health scoring, exports, severity filters, bulk fixes, or a separate technical detail screen. Selecting an item should normally open the existing setting or record editor. A small guidance view may be used only when the user needs a brief explanation before editing.

## 12. Sitemap and robots

The administration screen shows:

- Whether `/sitemap.xml` and `/robots.txt` are enabled and reachable
- Canonical host
- Registered sitemap sources and record counts
- Chunk size and estimated chunks
- Last cache invalidation or generation time when available
- A read-only preview of generated robots rules

Settings may disable the plugin routes. If a physical `public/robots.txt` shadows the Laravel route, diagnostics should show a warning with remediation text.

## 13. Empty, loading, and failure states

- New install: guided checklist with SEO output still disabled.
- No available content types: show a concise empty state and link to developer documentation.
- No SEO check issues: show the last check time and a clear healthy state.
- AI unavailable: keep manual editing fully usable and link authorized administrators to AI settings.
- Sitemap unavailable: explain what the administrator can do next.
- Unsaved settings: ask the administrator to save before running checks.

## 14. Technical changes from the current implementation

### Keep

- Core settings registration and Team-scoped store
- `SeoResourceDefinition` fail-closed registration
- `MetadataResolver`, preview factory, metadata renderer, sitemap generator, and robots generator
- Core AI connector dependency
- Existing permission slugs and rate limiting

### Change

- Split the current long settings form into local SEO sections without creating standalone admin Resources.
- Add a Team-level Resource override map.
- Add site route toggles to saved settings rather than config-only behavior.
- Extend resolution to include Resource defaults.
- Replace the detached diagnostic report with a simple actionable list and direct links.
- Reshape the record tab into Search, Social, and Advanced sections with a stable preview column.
- Replace direct AI pre-fill with suggestion review and selective apply.

### Compatibility

- Target Aura CMS `^1.0`; do not use a synthetic `2.0.0` package version.
- Read current settings keys as migration input.
- Default new controls to the current fail-closed behavior.
- Existing record meta fields remain valid.
- Resource registration remains code-first, so upgrades do not broaden public exposure.

## 15. Acceptance criteria

1. SEO appears as one normal Aura settings entry and uses the standard settings layout.
2. A fresh installation exposes no Resource and defaults to `noindex, nofollow`.
3. Settings and Resource overrides are isolated by Team.
4. Site defaults, Resource defaults, and record overrides resolve in the documented order.
5. A manual metadata value is never overwritten by default generation or AI without explicit approval.
6. Diagnostics link an authorized user to the exact settings section or record that can resolve an issue.
7. Users with diagnostic-only permission cannot mutate SEO settings or record metadata.
8. Sitemap output contains only explicitly registered and visible records.
9. The AI workflow remains usable with any provider supported by the core connector and remains optional.
10. Clean Composer installation succeeds against Aura CMS 1.0 without a fake `2.0.0` alias.
11. The experience works in light and dark Aura themes and at tablet widths.
12. Automated tests cover Team isolation, authorization, resolution precedence, diagnostic links, sitemap boundaries, and AI apply behavior.

## 16. Proposed implementation sequence

1. Approve the information architecture and one overview variant.
2. Correct the package constraint and establish a clean Aura 1.0 review application.
3. Add the Resource override data structure and resolution precedence tests.
4. Implement the settings sub-navigation and Site defaults screen.
5. Implement Content types and Resource defaults.
6. Refactor the record SEO tab and preview.
7. Change AI pre-fill into review-and-apply suggestions.
8. Implement the minimal V1 SEO checks list with direct links.
9. Implement Sitemap & robots administration.
10. Register SEO permissions in Aura's existing role editor, then finish accessibility, responsive behavior, documentation, and release verification.

## 17. Review questions for Enes

1. Which overview variant should be implemented: Guided setup, Workspace, or Compact operations?
2. Should Team administrators be able to disable a code-registered Resource, or should that remain code-only?
3. Should sitemap and robots route toggles be editable per Team or deployment-wide only?
4. Should AI offer one suggestion or two alternatives?
5. Should social fields share one default set, with platform overrides hidden under Advanced as proposed?
