# Aura SEO Product and Technical Specification

**Status:** Proposal for review  
**Prepared for:** Enes and the Aura CMS team  
**Date:** 21 September 2026  
**Prototype:** `docs/prototypes/seo-product/index.html`

## 1. Decision requested

Approve the product structure, permissions, and implementation sequence for the next Aura SEO iteration. The proposal keeps SEO inside the normal Aura administration experience, makes problems actionable, and avoids rebuilding a full third-party SEO suite.

The accompanying prototype contains ten screens and three alternative overview layouts. The recommended overview is **Variant B - Workspace**, because it gives editors a compact health summary while keeping the next useful actions visible.

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
- A clear overview with setup state, health summary, and prioritized actions.
- Site identity, canonical URL, title pattern, locale, social-image defaults, and robots defaults.
- A per-Resource overview showing registration, indexing, sitemap, title source, description source, and default pattern.
- Per-Resource Team overrides for enablement and presentation defaults, while public enumeration remains registered in code.
- A focused SEO editor inside each participating record.
- Search and social previews using resolved metadata.
- AI-generated title and description suggestions through Aura's core AI connector.
- Diagnostics with filters, direct record links, suggested actions, and guarded bulk actions.
- Sitemap and robots status, route visibility, last generation information, and safe controls.
- Permission guidance for SEO management and diagnostic access.

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
| Overview | Health, setup progress, recent scan, and next actions |
| Site defaults | Site identity, title pattern, default description, locale, social images, and default robots behavior |
| Content types | Registered Resources and their Team-level SEO behavior |
| Diagnostics | Actionable site and record issues |
| Sitemap & robots | Public route status, source coverage, and robots rules |
| Permissions | Explain and assign the packaged permission capabilities when the host supports role management |

The Resource editor receives a single **SEO** tab. The tab is not a second settings system; it only stores record-level overrides.

## 5. Users and permissions

| User | Access |
| --- | --- |
| Super Admin / Global Admin | Full SEO settings and diagnostics access |
| User with `manage-aura-seo` | Edit site defaults, Resource overrides, sitemap/robots behavior, and record metadata |
| User with `diagnose-aura-seo` | View diagnostics and open affected records; cannot change global configuration |
| Resource editor without SEO permissions | Edit ordinary Resource fields; SEO tab is hidden or read-only according to the host policy |

Authorization must run server-side. Hiding navigation is not sufficient. AI generation requires the same ability used to edit the target SEO fields and keeps the existing rate limit.

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

These values should be stored as one map keyed by the stable Resource source key. Removing a registration leaves inert settings that can be pruned safely.

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
2. Team-level Resource default
3. Team-level site default
4. Registered Resource mapping
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

Aura SEO calls the stable core AI connector contract. It must not know whether the core implementation uses custom adapters, Laravel AI SDK, GLM, or another provider.

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
- Optional short explanation for the editor

### Interaction rules

- **Generate suggestion** never saves.
- Existing and suggested values are shown side by side.
- The editor may apply title, description, or both.
- Applying changes updates the form only; the normal record Save action persists them.
- Failure leaves current form values untouched and displays a retryable error.
- Inputs and outputs must not be logged with provider credentials.

## 11. Diagnostics

Diagnostics become a first-class settings tab instead of a detached report.

Each issue includes:

- Severity: error, warning, or notice
- Scope: site, Resource, or record
- Resource/source key
- Record identifier and display title, when available
- Human-readable problem
- Recommended action
- Direct action: open settings, open Resource defaults, or edit record

Initial checks:

- SEO disabled
- Missing or invalid canonical base URL
- Default indexing disabled
- Missing site description or social image
- Registered Resource missing required public-boundary callbacks
- Sitemap-capable Resource disabled or empty
- Record missing title or description
- Duplicate or invalid canonical URL
- Record excluded from index while present in sitemap

Safe bulk actions are limited to deterministic changes, such as applying inherited defaults to blank fields. AI generation remains per-record unless a later specification defines review and cost controls.

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
- No registered Resources: explain code registration and link to developer documentation.
- No diagnostic issues: show last scan time and a clear healthy state.
- AI unavailable: keep manual editing fully usable and link authorized administrators to AI settings.
- Sitemap unavailable: explain which registration requirement is missing.
- Unsaved settings: diagnostics clearly state that scans use last-saved values.

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
- Replace the basic diagnostic table with actionable issue rows and direct links.
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
8. Implement actionable diagnostics.
9. Implement Sitemap & robots administration.
10. Finish permissions, accessibility, responsive behavior, documentation, and release verification.

## 17. Review questions for Enes

1. Which overview variant should be implemented: Guided setup, Workspace, or Compact operations?
2. Should Team administrators be able to disable a code-registered Resource, or should that remain code-only?
3. Should sitemap and robots route toggles be editable per Team or deployment-wide only?
4. Is diagnostic-only access useful, or should all diagnostics require `manage-aura-seo`?
5. Should AI offer one suggestion or two alternatives?
6. Should social fields share one default set, with platform overrides hidden under Advanced as proposed?
7. Are deterministic bulk fixes acceptable, or should all fixes remain one-record-at-a-time for the first release?

