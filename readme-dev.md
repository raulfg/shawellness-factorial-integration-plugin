# SHA Factorial Jobs — Developer Notes

Custom plugin for Shawellness. Not intended for public distribution.

## SDD Artifacts

- `openspec/config.yaml` — project config
- `openspec/changes/sha-factorial-jobs/` — active change (explore → propose → spec → design → tasks → apply)

## Local Environment

```bash
docker compose up -d
# WordPress: http://localhost:8089
# phpMyAdmin: http://localhost:8080
```

Activate plugin:

```bash
docker compose --profile cli run --rm wpcli plugin activate sha-factorial-jobs
```

## Factorial API

- Docs: https://apidoc.factorialhr.com/v2026-10-01/docs/getting-started
- Job postings: `GET /resources/ats/job_postings`
- Auth: `x-api-key` header (one key per ES/MX account)

## Admin UX

Menu: **Factorial Jobs** (wp-admin). Features:

- Connection cards ES/MX with live **Probar conexión** (AJAX)
- Status badges, published job count, API version feedback
- Cache TTL + purge
- Shortcode copy buttons and API attribute reference

The visual style editor is currently hidden with
`SHA_FACTORIAL_JOBS_STYLE_EDITOR_ENABLED`. Existing settings remain stored.
Project-specific frontend overrides belong in `assets/css/sha-wellness.css`,
which loads after `public.css` and the generated CSS variables.
The plugin bundles the Graphik light and regular webfonts used by
`shawellness.com`, so its typography does not depend on the active theme.

## Shortcodes

```
[sha_factorial_jobs region="es"]
[sha_factorial_jobs region="mx" layout="grid"]
[sha_factorial_jobs region="all" show_filters="true"]
[sha_factorial_jobs_demo]
```

Job detail opens on the same page via query args: `?sfj_job={id}&sfj_region=es|mx` (linked automatically from each card).

## SEO (indexing)

Job postings change often. The intended model:

| What | URL | Indexed? |
|------|-----|----------|
| WordPress page (hero, blocks, copy **outside** the shortcode) | `/jobs/` | **Yes** — control with Yoast (or similar) on that page |
| Shortcode output (filters, cards, detail body) | same HTML | **Discouraged** — `data-nosnippet` on `.sfj-jobs` / `.sfj-job-single` |
| Job detail view | `/jobs/?sfj_job=…` | **No** — full response `noindex` + canonical to `/jobs/` |

**Important:** Google does not support “noindex only this `<div>`” on an otherwise indexable URL. The HTML of the list is still part of the page; `data-nosnippet` mainly affects **snippets** (and similar signals), not a hard guarantee that titles are never associated with the URL. Detail URLs are fully `noindex` because the whole response is a volatile job view.

Plugin behaviour:

- **`noindex`** only when `?sfj_job=` is present (meta `wp_robots`, `X-Robots-Tag`, canonical → list URL without args).
- **`data-nosnippet`** on shortcode markup (filter: `sha_factorial_jobs_exclude_shortcode_from_snippets`, default `true`).
- **`rel="nofollow"`** on card links to detail URLs (filter: `sha_factorial_jobs_detail_link_nofollow`, default `true`).
- Listing page **not** noindexed by default (`sha_factorial_jobs_listing_noindex` default `false`). Set to `true` only if you want the entire `/jobs/` page noindex.

Remove Yoast “no index” on `/jobs/` when you want that page in the index; keep detail URLs blocked via the plugin.

Verify detail:

```bash
curl -sI 'http://localhost:8089/jobs/?sfj_job=ID&sfj_region=es' | grep -i robots
```

`[sha_factorial_jobs_demo]` renders sample cards with every supported field.
It is intended only for the local style guide page.

## Internationalization

Source strings in PHP are **English**. Translations live in `languages/`:

| Locale | Files |
|--------|-------|
| `es_ES` | `sha-factorial-jobs-es_ES.po` / `.mo` |
| `es_MX` | `sha-factorial-jobs-es_MX.po` / `.mo` |
| `fr_FR` | `sha-factorial-jobs-fr_FR.po` / `.mo` |
| `ar` | `sha-factorial-jobs-ar.po` / `.mo` |

Frontend RTL: when the site locale is RTL (e.g. `ar`), shortcode output gets `dir="rtl"` and the `sfj-rtl` class.

Regenerate after adding strings:

```bash
python3 tools/build-translations.py
```

WordPress loads the matching `.mo` automatically from the site locale.

## Public listing

Shortcode jobs are sorted by **`published_at` descending** (newest first) in `Sha_Factorial_Jobs_Service::get_jobs_for_display()` via `sha_factorial_jobs_sort_jobs_by_published_at_desc()`.

## Next SDD Phase

Implementation in progress → `/sdd-verify sha-factorial-jobs` when API keys available
