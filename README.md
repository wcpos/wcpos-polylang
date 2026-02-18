# WCPOS Polylang Integration

Adds Polylang-aware product filtering to WCPOS, including **fast sync route coverage** and a per-store language selector for WCPOS Pro stores.

## What it does

- Filters WCPOS product + variation REST queries by language.
- Intercepts WCPOS fast-sync routes (`posts_per_page=-1` + `fields`) so duplicate translated products are not returned.
- Free WCPOS stores default to Polylang default language.
- WCPOS Pro stores can save a store-specific language.

## Development

```bash
pnpm test
```
