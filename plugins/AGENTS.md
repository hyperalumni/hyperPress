# AGENTS.md

Companion WordPress plugins (PHP only) for the hyperPress theme. Each `hyperpress-*/` dir is an independent plugin. There is a root `composer.json`, a Docker test toolchain, phpcs and PHPUnit. There is no CI.

## Plans and specs
- Work is driven by `docs/superpowers/` (`specs/`, `plans/`, `audit/`). Read the relevant plan and spec before starting work.
- Tooling: the host has PHP 8.5 and docker only. Run everything through `docker compose run --rm php <cmd>` from this dir (never composer on the host): `composer lint`, `composer test:unit`, `composer test:integration`.
- There are 14 plugins, including `hyperpress-shortcodes` and `hyperpress-media`, which were extracted from the theme.

## Plugin conventions
- Layout: `hyperpress-<name>/hyperpress-<name>.php` (header, ABSPATH guard, PSR-4 autoloader, hook `Plugin::boot` on `plugins_loaded`) plus `src/` (namespace `HyperPress\<Name>`, one class per concern). Registered keys are `hyper_*`; other identifiers are `hyperpress_{plugin}_{field}`. Text domain `hyperpress`.
- Every header carries Version `2026.0.0`, Requires at least `6.9` and Requires PHP `8.3`; `Requires Plugins` follows the table in `docs/superpowers/specs/2026-10-02-plugins-convention-and-shared-code-design.md`.
- `hyperpress-utils` is the base dependency, and `hyperpress-season` is a base CPT/taxonomy that many plugins depend on. Meta boxes come from the `meta-box-aio` plugin.
- `hyperpress-label` and `hyperpress-student` are single-file placeholders with no behavior.

## Git gotchas
- The index has unrelated staged deletions under `hyperPress-*/` (capital P, which doesn't match the on-disk `hyperpress-*/`). Never run a bare `git commit`. Use `git add <paths>` then `git commit -m "..." -- <paths>`.
- Commit style: terse imperative, one commit per plugin during migration.
