# AGENTS.md

Companion WordPress plugins (PHP only) for the hyperPress theme. Each `hyperpress-*/` dir is an independent plugin. There is a root `composer.json`, a Docker test toolchain, phpcs and PHPUnit. There is no CI.

Part of the hyperPress monorepo (`plugins/`); the root `../AGENTS.md` also applies.

## Plans and specs
- Work is driven by `../docs/superpowers/` (`specs/`, `plans/`, `audit/`). Read the relevant plan and spec before starting work.
- Tooling: the host has PHP 8.5 and docker only. Run everything through `docker compose run --rm php <cmd>` from this dir (never composer on the host): `composer lint`, `composer test:unit`, `composer test:integration`.
- There are 16 plugins, including `hyperpress-shortcode` and `hyperpress-media`, which were extracted from the theme.
- Packaging: `bin/package [plugin ...]` runs on the host (needs git and unzip). It writes `packaged/<plugin>-<version>.zip` from committed files only; with no args it packages all plugins. `<version>` is the nearest ancestor version tag of HEAD (`2027.1.0` or `v2027.1.0`; leading `v` dropped, no commit suffix; other tags are ignored), written into the `Version:` header of the zipped main file only (sources are untouched). With no version tag it warns on stderr and uses each plugin's committed header version. Tag the release commit before packaging.

## Plugin conventions
- Layout: `hyperpress-<name>/hyperpress-<name>.php` (header, ABSPATH guard, PSR-4 autoloader, hook `Plugin::boot` on `plugins_loaded`) plus `src/` (namespace `HyperPress\<Name>`, one class per concern). Registered keys are `hyper_*`; other identifiers are `hyperpress_{plugin}_{field}`. Text domain `hyperpress`.
- Every header carries Version `2026.0.0` (a dev/fallback value only: `bin/package` stamps the nearest version tag into the packaged copy), Requires at least `6.9` and Requires PHP `8.3`; `Requires Plugins` follows the table in `../docs/superpowers/specs/2026-10-02-plugins-convention-and-shared-code-design.md`.
- `hyperpress-utils` is the base dependency, and `hyperpress-season` is a base CPT/taxonomy that many plugins depend on. Meta boxes come from the `meta-box-aio` plugin.
- The top-level "hyperPress" wp-admin menu lives in `hyperpress-utils` (`AdminMenu`); other plugins attach their settings pages to it with `parent => AdminMenu::SLUG`.
- `hyperpress-student` is a single-file placeholder with no behavior.
- Labels: `hyperpress-label` owns the registry, the Labels settings page and the renderer. Season, program, competition (district, week, type) and sponsor register their own. Plugins register labels on the `hyperpress_label_register` action; theme templates call `do_action( 'hyperpress_labels' )`.

## Git gotchas
- The index has unrelated staged deletions under `hyperPress-*/` (capital P, which doesn't match the on-disk `hyperpress-*/`). Never run a bare `git commit`. Use `git add <paths>` then `git commit -m "..." -- <paths>`.
- Commit style: terse imperative, one commit per plugin during migration.
