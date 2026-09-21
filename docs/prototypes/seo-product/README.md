# Aura SEO product prototype

**ROUGH PRODUCT OUTLINES - not production code, markup, or final copy.**

This static prototype answers: **What should the production-ready Aura SEO settings and record-editing experience look like?**

It contains ten screens. The overview also contains three structurally different variants, switchable from the floating prototype bar or the `variant` query parameter:

- `01-overview.html?variant=guided`
- `01-overview.html?variant=workspace` (recommended)
- `01-overview.html?variant=compact`

Run from the `aura-seo` repository:

```bash
php -S 127.0.0.1:4173 -t docs/prototypes/seo-product
```

Then open <http://127.0.0.1:4173>.

The prototype is read-only apart from simulated interactions. It performs no persistence and sends no network requests.

## Implementation guardrails

- Treat the screens as product-flow references only. Do not copy their markup, CSS, or wording into the plugin.
- Build the production UI with Aura's existing settings layout, field components, cards, tabs, buttons, notices, role editor, and responsive conventions.
- Add custom markup or styling only when Aura has no suitable component, and keep it consistent with Aura's guidelines.
- Keep end-user copy focused on the task and outcome. Internal storage, authorization, registration, and connector details belong in developer documentation and tests.
- Keep V1 SEO checks simple: a short actionable list with links to the relevant setting or record. A separate detail screen is optional, not required.
- The plugin registers its permissions; administrators assign them in Aura's existing role edit page. `10-permissions.html` illustrates that integration and is not an SEO settings page.
