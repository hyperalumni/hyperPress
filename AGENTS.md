# hyperPress theme

Classic (non-block) WordPress theme forked from FoundationPress (Foundation 6; jQuery is a global, not bundled).
Requires PHP 8.3, WP 6.9, Node >=24 (`.nvmrc`), pnpm (enforced by `preinstall`; do not use npm/yarn).
Text domain `hyperpress`. `README.md` is stale (Node 6, FoundationPress) and `.travis.yml` is dead (PHP 5.x-7.3). Ignore both.

## Commands
- Node comes from nvm and is not on the default PATH. Load it first in each shell:
  `source ~/.nvm/nvm.sh && nvm use` (reads `.nvmrc`), then run `pnpm`/`node` commands.
- `pnpm start`: gulp dev server with Browsersync (needs `BROWSERSYNC.url` in a local `config.yml`)
- `pnpm dev` / `pnpm build`: gulp build `--dev` (sourcemaps) / `--production` (minify, optional revisioning)
- `pnpm phpcs` / `pnpm phpcbf`: PHPCS via `codesniffer.ruleset.xml` (WordPress standard, many exclusions)
- `pnpm package`: zip into `packaged/`
- No test suite exists yet. `phpunit.xml.dist` points at a missing `tests/bootstrap.php`. The planned
  Docker/PHPUnit toolchain is `docs/superpowers/plans/2026-10-02-theme-01-tooling-and-baseline.md`.
  Until then verify PHP changes with `php -l <file>` and `pnpm phpcs`.

## Layout
- `functions.php` requires modules from `library/`; register new modules there.
- Source assets: `src/assets/{js,scss,images,css}`. Webpack entries come from `PATHS.entries` in
  `config-default.yml`/`config.yml`, not `webpack.config.js`. `TimeCircles.js` is listed there but
  the file does not exist.
- `config.yml` (git-ignored) replaces `config-default.yml` entirely; it is not merged.
- `dist/`, `vendor/`, `packaged/` are generated and git-ignored. Never edit them.
- `.env` exists and is git-ignored. Do not read or print it.
- Templates are in the repo root plus `template-parts/` and `page-templates/`.

## Direction
Per `docs/superpowers/specs/2026-10-02-theme-cleanup-and-extraction-design.md`, the theme is becoming
presentation-only. Shortcodes, SVG/AVIF support, NextGen code and HYPER plugin recommendations move to
plugins in `../plugins`. Banner router, core breadcrumb trails, Customizer, nav, walkers and templates stay.
- Keep shortcode tags and plugin-facing hooks unchanged: `hyperpress_banner_content`,
  `hyperpress_breadcrumbs_content`, `content_template`, `hyperpress_labels_content`.
- Use the `hyperpress_` prefix for new code. Legacy `foundationpress_` and `hyper_` names are being renamed.

## Gotchas
- `pnpm-workspace.yaml` `allowBuilds` whitelists native build scripts (only `mozjpeg` is enabled).
- The working tree usually has unrelated uncommitted changes. Commit with explicit pathspecs, never `git add -A`.
- Never put secrets in the repo. `library/plugins.php` once held an API-key URL; do not reproduce it.

## Implementation plans
Plans are in `docs/superpowers/plans/`, specs in `docs/superpowers/specs/` (see the plans README).
Commit a plan on its own before coding, tick its checkboxes in the same commit as each task's code,
and update any plan that mentions a file you change. Add a COMPLETED banner when a plan finishes.
