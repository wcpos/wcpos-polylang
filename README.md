# WCPOS Polylang Integration

Adds Polylang-aware product filtering to WCPOS, including **fast sync route coverage** and a per-store language selector for WCPOS Pro stores.

## What it does

- Filters WCPOS product + variation REST queries by language.
- Intercepts WCPOS fast-sync routes (`posts_per_page=-1` + `fields`) so duplicate translated products are not returned.
- Free WCPOS stores default to Polylang default language.
- WCPOS Pro stores can save a store-specific language.
- Store language selector UI only loads when Polylang is active and languages are available.
- Plugin strings use the `wcpos-polylang` text domain.
- PHP integration now no-ops when Polylang is unavailable.
- Optional minimum version gate via `wcpos_polylang_minimum_version` filter.

## Development

```bash
pnpm test
```
