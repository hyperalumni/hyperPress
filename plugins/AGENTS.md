# AGENTS.md

Companion WordPress plugins (PHP only) for the hyperPress theme. Each `hyperpress-*/` dir is an independent plugin. There is no root manifest, build step, CI or linter yet.

## Plans and specs
- Work is driven by `docs/superpowers/` (`specs/`, `plans/`, `audit/`). Read the relevant plan and spec before starting work.
- No tests, composer, phpcs or Docker exist yet. The test toolchain is planned, not built, so don't assume `composer` or `phpunit` commands work.
- Plans target 14 plugins: the 12 present plus `hyperpress-shortcodes` and `hyperpress-media`, which are extracted from the theme and don't exist yet.
- The host has PHP 8.5 and docker only. The planned convention is to run tooling through `docker compose run --rm php <cmd>` from this dir, and never run composer on the host. That `php` service is not built yet (no compose file or Dockerfile in this dir), so the command won't work until the toolchain plan lands. Until then only `php -l <file>` is available.

## Plugin conventions
- Layout: `hyperpress-<name>/<name>.php` is the main file, with modules in `lib/`. Main files use plain `require_once( __DIR__ . '/lib/...' )`, with no autoloader. Definitions (`custom-post-type`/`custom-taxonomy`) load before ancillary files (banner, breadcrumbs, meta-boxes, ...).
- Namespace `HYPER_Press_<Name>` (e.g. `HYPER_Press_Season`). Text domain `hyperpress`. Every file guards with `if ( ! defined( 'ABSPATH' ) ) { die( '-1' ); }`.
- Every header carries Version `2026.0.0`, Requires at least `6.9` and Requires PHP `8.3`. `Requires Plugins:` currently varies per plugin and is being normalized by the migration. The target is in `docs/superpowers/specs/2026-10-02-plugins-convention-and-shared-code-design.md`, so follow the spec rather than copying a neighbouring header.
- `hyperpress-utils` is the base dependency, and `hyperpress-season` is a base CPT/taxonomy that many plugins depend on. Meta boxes come from the `meta-box-aio` plugin.
- `hyperpress-label` and `hyperpress-student` are single-file plugins with no `lib/`.

## Git gotchas
- The index has unrelated staged deletions under `hyperPress-*/` (capital P, which doesn't match the on-disk `hyperpress-*/`). Never run a bare `git commit`. Use `git add <paths>` then `git commit -m "..." -- <paths>`.
- Commit style: terse imperative, one commit per plugin during migration.
