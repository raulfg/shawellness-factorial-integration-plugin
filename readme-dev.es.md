# SHA Factorial Jobs — Notas para desarrolladores

Plugin a medida para Shawellness. Uso interno; no está pensado para distribución pública.

Versión en inglés: [readme-dev.md](readme-dev.md)

## Artefactos SDD

- `openspec/config.yaml` — configuración del proyecto
- `openspec/changes/sha-factorial-jobs/` — cambio activo (explore → propose → spec → design → tasks → apply)

## Entorno local

```bash
docker compose up -d
# WordPress: http://localhost:8089
# phpMyAdmin: http://localhost:8080
```

Activar el plugin:

```bash
docker compose --profile cli run --rm wpcli plugin activate sha-factorial-jobs
```

## API de Factorial

- Documentación: https://apidoc.factorialhr.com/v2026-10-01/docs/getting-started
- Ofertas: `GET /resources/ats/job_postings`
- Autenticación: cabecera `x-api-key` (una clave por cuenta ES/MX)

## UX de administración

Menú: **Factorial Jobs** (wp-admin). Incluye:

- Tarjetas de conexión ES/MX con **Probar conexión** en vivo (AJAX)
- Badges de estado, conteo de ofertas publicadas, feedback de versión de API
- TTL de caché y purga
- Botones para copiar shortcodes y referencia de atributos de la API

El editor visual de estilos está oculto con
`SHA_FACTORIAL_JOBS_STYLE_EDITOR_ENABLED`. Los ajustes guardados siguen en la base de datos.
Los overrides de frontend del proyecto van en `assets/css/sha-wellness.css`,
que se carga después de `public.css` y de las variables CSS generadas.
El plugin incluye las webfonts Graphik light y regular usadas en
`shawellness.com`, de modo que la tipografía no depende del tema activo.

## Shortcodes

```
[sha_factorial_jobs region="es"]
[sha_factorial_jobs region="mx" layout="grid"]
[sha_factorial_jobs region="all" show_filters="true"]
[sha_factorial_jobs_demo]
```

El detalle de la oferta se abre en la misma página con query args: `?sfj_job={id}&sfj_region=es|mx` (enlazado automáticamente desde cada tarjeta).

## SEO (indexación)

Las ofertas cambian con frecuencia. Modelo previsto:

| Qué | URL | ¿Indexable? |
|-----|-----|-------------|
| Página de WordPress (hero, bloques, copy **fuera** del shortcode) | `/jobs/` | **Sí** — controlar con Yoast (u otro) en esa página |
| Salida del shortcode (filtros, tarjetas, cuerpo del detalle) | mismo HTML | **Desaconsejado** — `data-nosnippet` en `.sfj-jobs` / `.sfj-job-single` |
| Vista de detalle | `/jobs/?sfj_job=…` | **No** — respuesta completa `noindex` + canonical a `/jobs/` |

**Importante:** Google no permite “noindex solo en este `<div>`” en una URL que por lo demás es indexable. El HTML del listado sigue formando parte de la página; `data-nosnippet` afecta sobre todo a **snippets** (y señales similares), no garantiza al 100 % que títulos de ofertas no se asocien a la URL. Las URLs de detalle van con `noindex` completo porque toda la respuesta es una vista volátil de la oferta.

Comportamiento del plugin:

- **`noindex`** solo cuando hay `?sfj_job=` (meta `wp_robots`, `X-Robots-Tag`, canonical → URL del listado sin parámetros).
- **`data-nosnippet`** en el markup del shortcode (filtro: `sha_factorial_jobs_exclude_shortcode_from_snippets`, por defecto `true`).
- **`rel="nofollow"`** en enlaces de tarjeta al detalle (filtro: `sha_factorial_jobs_detail_link_nofollow`, por defecto `true`).
- La página de listado **no** lleva `noindex` por defecto (`sha_factorial_jobs_listing_noindex` por defecto `false`). Pon `true` solo si quieres `noindex` en toda `/jobs/`.

Quita el “no index” de Yoast en `/jobs/` cuando quieras indexar esa página; deja las URLs de detalle bloqueadas vía el plugin.

Comprobar detalle:

```bash
curl -sI 'http://localhost:8089/jobs/?sfj_job=ID&sfj_region=es' | grep -i robots
```

`[sha_factorial_jobs_demo]` muestra tarjetas de ejemplo con todos los campos soportados.
Solo para la página local de guía de estilos.

## Internacionalización

Los textos fuente en PHP están en **inglés**. Las traducciones están en `languages/`:

| Locale | Archivos |
|--------|----------|
| `es_ES` | `sha-factorial-jobs-es_ES.po` / `.mo` |
| `es_MX` | `sha-factorial-jobs-es_MX.po` / `.mo` |
| `fr_FR` | `sha-factorial-jobs-fr_FR.po` / `.mo` |
| `ar` | `sha-factorial-jobs-ar.po` / `.mo` |

RTL en frontend: si el locale del sitio es RTL (p. ej. `ar`), la salida del shortcode lleva `dir="rtl"` y la clase `sfj-rtl`.

Regenerar tras añadir cadenas:

```bash
python3 tools/build-translations.py
```

WordPress carga el `.mo` correspondiente según el locale del sitio.

## Listado público

Las ofertas del shortcode se ordenan por **`published_at` descendente** (más recientes primero) en `Sha_Factorial_Jobs_Service::get_jobs_for_display()` vía `sha_factorial_jobs_sort_jobs_by_published_at_desc()`.

## Siguiente fase SDD

Implementación en curso → `/sdd-verify sha-factorial-jobs` cuando haya API keys disponibles
