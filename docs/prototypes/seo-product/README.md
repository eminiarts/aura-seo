# Aura SEO product prototype

**PROTOTYPE - not production code.**

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

