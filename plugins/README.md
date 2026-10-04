# hyperPress plugins

Companion WordPress plugins for the hyperPress theme. Each `hyperpress-*/` directory is an independent plugin.

| Plugin | Purpose |
|---|---|
| `hyperpress-utils` | Shared code: post type and taxonomy registrars, capabilities, breadcrumbs, banner, Customizer controls |
| `hyperpress-season`, `hyperpress-program` | Taxonomies that other content attaches to (`hyper_season`, `hyper_program`) |
| `hyperpress-award`, `-competition`, `-countdown`, `-newsletter`, `-robot`, `-sponsor` | Custom post types (`hyper_*`) |
| `hyperpress-socials` | Social links (Customizer section and widget) |
| `hyperpress-shortcodes` | Content shortcodes moved out of the theme (time-restrict, date distance, raw, emphasis, banner) |
| `hyperpress-media` | SVG and AVIF uploads and NextGen Gallery integration, moved out of the theme |
| `hyperpress-label`, `hyperpress-student` | Placeholders with no behaviour yet |

## Development

Tooling runs in Docker (PHP 8.5 image, MariaDB). Do not run Composer on the host.

```bash
docker compose run --rm php composer install
docker compose run --rm php composer lint              # php -l and phpcs (WordPress + PHPCompatibilityWP)
docker compose run --rm php composer test:unit         # no WordPress needed
docker compose run --rm php composer test:integration  # boots WordPress with all plugins; Meta Box is stubbed
```

The plugin headers keep `Requires PHP: 8.3` as the floor and `Requires at least: 6.9`.

## Conventions

- Registered post type and taxonomy keys are `hyper_*` (at most 20 characters). Public URLs do not use the key.
- Every other identifier (meta box, field, Customizer setting, section, control, widget id, script handle) is `hyperpress_{plugin}_{field}`. A Customizer control id equals its setting id.
- Shared code lives in `hyperpress-utils` (`HyperPress\Utils`). Each plugin has the layout `hyperpress-x/hyperpress-x.php` plus `src/` (namespace `HyperPress\X`, PSR-4, own autoloader).
- Plugins boot on `plugins_loaded`, so they may call `HyperPress\Utils` only from then on, never while their main file is being included.
- `Requires Plugins` in each header lists its dependencies; the target table is in `docs/superpowers/specs/2026-10-02-plugins-convention-and-shared-code-design.md`.

## Extracted plugins

`hyperpress-shortcodes` and `hyperpress-media` were moved out of the theme. Sites whose content uses the `time-restrict`, `hyper_emphasis`, `raw`, `hyper_banner` or `hyper_date_distance` shortcodes must activate `hyperpress-shortcodes`.

## Upgrading from the pre-`hyper_` keys

- Deactivate and reactivate each plugin (main files were renamed, which changes the plugin basename).
- Existing dev content and stored meta are orphaned by the key rename, so reset the local database. There is no migration tool by design.
- Flush permalinks (Settings, Permalinks, Save).
- Re-select the chosen post types in the Customizer (season, program).

## Specs and plans

See `docs/superpowers/` (`specs/`, `plans/`, `audit/`).
