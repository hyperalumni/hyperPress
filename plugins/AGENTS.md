# AGENTS.md

Companion WordPress plugins for the hyperPress theme (PHP; only `hyperpress-example` has a small Node build step). Each `hyperpress-*/` dir is an independent plugin. There is a root `composer.json`, a Docker test toolchain, phpcs and PHPUnit. There is no CI.

Part of the hyperPress monorepo (`plugins/`); the root `../AGENTS.md` also applies.

## Plans and specs

- Work is driven by `../docs/superpowers/` (`specs/`, `plans/`, `audit/`). Read the relevant plan and spec before starting work.
- Tooling: PHP runs in Docker only. Run everything through `docker compose run --rm php <cmd>` from this dir (never composer on the host): `composer lint`, `composer test:unit`, `composer test:integration`.
- PHP versions: the suites run and pass on PHP 8.3, 8.4 and 8.5, and `composer.lock` is pinned with `config.platform.php = 8.3.0` so it installs on 8.3. To repeat: build throwaway `php:8.3-cli` and `php:8.4-cli` images from `plugins/Dockerfile` with only the base tag changed, run the suites in them against a throwaway MariaDB 11.4, then remove the images and containers.
- There are 17 plugin directories: 16 plugins, including `hyperpress-shortcode` and `hyperpress-media`, which were extracted from the theme, plus the `hyperpress-example` stub (it consumes the shared Sass lib and is not part of the plugin conventions below).
- Packaging: `bin/package [plugin ...]` runs on the host (needs git and unzip). It writes `packaged/<plugin>-<version>.zip` from committed files only; with no args it packages every `hyperpress-*/` directory, including `hyperpress-example`, whose git-ignored `dist/uikit.css` is then missing from its zip, so name the plugins you want. `<version>` is the nearest ancestor version tag of HEAD (`2027.1.0` or `v2027.1.0`; leading `v` dropped, no commit suffix; other tags are ignored), written into the `Version:` header of the zipped main file only (sources are untouched). With no version tag it warns on stderr and uses each plugin's committed header version. Tag the release commit before packaging.

## Plugin conventions

- Layout: `hyperpress-<name>/hyperpress-<name>.php` (header, ABSPATH guard, PSR-4 autoloader, hook `Plugin::boot` on `plugins_loaded`) plus `src/` (namespace `HyperPress\<Name>`, one class per concern). Registered keys are `hyper_*`; other identifiers are `hyperpress_{plugin}_{field}`. Text domain `hyperpress`.
- Every header carries Version `2026.0.0` (a dev/fallback value only: `bin/package` stamps the nearest version tag into the packaged copy), Requires at least `6.9` and Requires PHP `8.3`; `Requires Plugins` follows the table in `../docs/superpowers/specs/2026-10-02-plugins-convention-and-shared-code-design.md`.
- `hyperpress-utils` is the base dependency, and `hyperpress-season` is a base CPT/taxonomy that many plugins depend on. Meta boxes come from the `meta-box-aio` plugin.
- The top-level "hyperPress" wp-admin menu lives in `hyperpress-utils` (`AdminMenu`); other plugins attach their settings pages to it with `parent => AdminMenu::SLUG`.
- `hyperpress-student` is a single-file placeholder with no behavior.
- Labels: `hyperpress-label` owns the registry, the Labels settings page and the renderer. Season, program, competition (district, week, type) and sponsor register their own. Plugins register labels on the `hyperpress_label_register` action; theme templates call `do_action( 'hyperpress_labels' )`.

## Git

- Commit from the repo root with explicit pathspecs: `git add <paths>` then `git commit -m "..." -- <paths>`. Never a bare `git commit`.
- Commit style follows the root `../AGENTS.md` (`type(scope): lowercase summary`, e.g. `fix(shortcode): …`).
