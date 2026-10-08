# SHA Factorial Jobs

**Uso interno — Shawellness**

Plugin de WordPress que muestra las ofertas de empleo publicadas en Factorial HR (cuentas España y México): listado con filtros, vista lista/grid, detalle en la misma página y enlace a aplicar en Factorial.

## Requisitos

- WordPress 6.0+
- PHP 8.0+
- API key de Factorial por región (ES / MX) configurada en **Factorial Jobs** (wp-admin)

## Instalación

1. Copiar la carpeta `sha-factorial-jobs` en `wp-content/plugins/`.
2. Activar el plugin en WordPress.
3. Configurar conexiones y campos de tarjeta en el menú **Factorial Jobs**.
4. Insertar el shortcode en la página de empleo, por ejemplo: `[sha_factorial_jobs region="all" show_filters="true"]`.

## Documentación técnica

| Documento | Idioma |
|-----------|--------|
| [readme-dev.es.md](readme-dev.es.md) | Español (detalle completo) |
| [readme-dev.md](readme-dev.md) | English (developer notes) |

## Repositorio

Código: [github.com/raulfg/shawellness-factorial-integration-plugin](https://github.com/raulfg/shawellness-factorial-integration-plugin)

## Versión

La versión actual se define en `sha-factorial-jobs.php` (`SHA_FACTORIAL_JOBS_VERSION`).
