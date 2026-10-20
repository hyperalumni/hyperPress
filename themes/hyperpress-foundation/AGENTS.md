# hyperPress theme

Classic (non-block) WordPress theme forked from FoundationPress (Foundation 6; jQuery is a global, not bundled).
Requires PHP 8.3, WP 6.9, Node per `.nvmrc` (26.12.0), pnpm (enforced by `preinstall`; do not use npm/yarn).
Text domain `hyperpress`. `README.md` keeps the upstream FoundationPress text below a short hyperPress section (Node 6 instructions there are stale); `.travis.yml` is dead (PHP 5.x-7.3). Ignore both.

Part of the hyperPress monorepo (`themes/hyperpress-foundation/`); the root `../../AGENTS.md` also applies.

## Commands

- Node and pnpm: load them as in the root `../../AGENTS.md` (Environment), then run `pnpm`/`node` commands from this directory (`nvm use` reads this directory's `.nvmrc`).
- `pnpm start`: gulp dev server with Browsersync (needs `BROWSERSYNC.url` in a local `config.yml`)
- `pnpm dev` / `pnpm build`: gulp build `--dev` (sourcemaps) / `--production` (minify, optional revisioning)
- `pnpm phpcs` / `pnpm phpcbf`: PHPCS via `codesniffer.ruleset.xml` (WordPress standard, many exclusions)
- `pnpm package`: build, then write `packaged/hyperPress-<version>-<yyyy-mm-dd>.zip` (`<version>` is the nearest version tag, see Versioning; same-day runs overwrite). Every entry sits in a `hyperPress/` wrapper folder, so the zip is for WordPress admin upload only (it installs to `wp-content/themes/hyperPress/` whatever the zip is called; do not unzip it into `themes/`). Dev files are excluded via `PATHS.package`; a local `config.yml` replaces that list, so keep it in sync
- `pnpm test:node`: node tests for the packaging helper (`gulp/package-version.mjs`, tests in `tests/node/`)
- PHP tooling runs in Docker from this dir (never composer on the host): `docker compose run --rm php composer lint|test:unit|test:integration`. Set `HYPERPRESS_TEST_WITHOUT_EXTRACTED=1` to run the integration suite without hyperpress-shortcode and hyperpress-media. `lint` is gating (0 violations); security findings carry `// phpcs:ignore ... -- theme audit` comments.

## Versioning

- The release version is a git tag at the repo root (no `package.json` version; the field was removed). Tags look like `2026.0.0` (a leading `v` is accepted and stripped); other tags (`backup-1`) are ignored.
- `pnpm package` takes the nearest version tag reachable from `HEAD` (`git describe --tags --abbrev=0`, no commit suffix), names the zip with it and writes it into the zipped `style.css` `Version:` header only. The source `style.css` is never modified.
- The `Version:` header in the source `style.css` is only the development value and the fallback: with no version tag the package task prints `warning: no version tag found; using <version> from style.css` to stderr and uses it.
- `hyperpress_theme_version()` (`library/enqueue-scripts.php`) returns the parent theme's `Version:` header and versions the theme's own CSS/JS (`main-stylesheet`, `main-script`, `wp-admin-svg-support`). Foundation, jQuery and Font Awesome keep their own library versions in the same file.

## Layout

- `functions.php` requires modules from `library/`; register new modules there.
- Source assets: `src/assets/{js,scss,images,css}`. Webpack entries come from `PATHS.entries` in
  `config-default.yml`/`config.yml`, not `webpack.config.js`.
- `config.yml` (git-ignored) replaces `config-default.yml` entirely; it is not merged.
- `dist/`, `vendor/`, `packaged/` are generated and git-ignored. Never edit them.
- `.env` exists and is git-ignored. Do not read or print it.
- Templates are in the repo root plus `template-parts/` and `page-templates/`.

## Direction

Per `../../docs/superpowers/specs/2026-10-02-theme-cleanup-and-extraction-design.md` (plans theme-01 to theme-05, all completed), the theme is presentation-only. Shortcodes, SVG/AVIF support, NextGen code and HYPER plugin recommendations live in the plugins in `../../plugins`. Banner router, core breadcrumb trails, Customizer, nav, walkers and templates stay.

- Shortcode tags are `hyperpress_`-prefixed (`hyperpress_emphasis`, `hyperpress_banner`, `hyperpress_time_restrict`, ...). The theme gives plugins generic extension points and knows nothing about any plugin. Keep them unchanged:
  - Actions (no arguments): `hyperpress_after_banner` (the end of `template-parts/banner.php`, so every template that includes the banner fires it once), `hyperpress_before_content` / `hyperpress_after_content` (immediately before and after `.main-container`), `hyperpress_before_main` / `hyperpress_after_main` (first and last thing inside `<main class="main-content">`; the Kitchen Sink template has no `<main>`, so it only fires the content pair), and `hyperpress_labels`. The content and main hooks fire from `single.php`, `archive.php`, `index.php`, `search.php`, `page.php`, `front-page.php` and every `page-templates/*.php`. On the front page only, `hyperpress_page_before_entry_content` / `hyperpress_page_after_entry_content` surround the countdown block's entry content.
  - Filters: `hyperpress_banner_content`, `hyperpress_breadcrumbs_content`, `content_template` (a readable template path used instead of `template-parts/content-{post type}.php`, or `''`) and `hyperpress_posts_template` (in `archive.php` and `index.php`: a readable template path used instead of `template-parts/posts.php`, or `''`; the theme keeps the banner, container, breadcrumbs, pagination, sidebar and footer around it).
  - Labels moved to `hyperpress-label`: templates call `do_action( 'hyperpress_labels' )`, and `hyperpress-label` must be active for labels to show (the plugin applies the `hyperpress_labels_content` filter, the theme no longer fires it).
- Filter priority contract. `hyperpress_banner_content`: the theme's per-type routers (front, blog, page, post, attachment, search, 404) and the plugin banners (`HyperPress\Utils\Banner`, the season banner) run at the default priority 10; the program banner runs at 20; the theme's specific archive fallbacks (tag, category, author, year, month, day) run at 80, its generic archive fallback at 90 and the blog colour at 95. `hyperpress_breadcrumbs_content`: the theme's per-context routers and the plugins' crumb builders (utils, competition, season) run at the default priority 10, and the program and NextGen (media) crumbs run at 20, after them. A plugin that must see what the theme and other plugins produced should use a priority above 20 (banner: below 80 to claim an archive before the theme's fallbacks).
- Breadcrumb crumb shape (`hyperpress_breadcrumbs_content` items, rendered by `template-parts/breadcrumbs.php`): `title` (visible text), `classes` (`li` and `bread` class lists), optional `url` (a link when set, otherwise a span) and optional `title_attr` (the crumb's HTML `title` attribute, native hover text; a non-empty string, otherwise the `title` is used).
- Banner filter order: plugin banners run at priority 10 (`HyperPress\Utils\Banner`). The theme's specific archive fallbacks (tag, category, author, year, month, day) run at priority 80 and its generic archive fallback (`hyperpress_banner_archive`) at priority 90, both after the plugin banners. The specific ones are guarded by an empty banner type, so plugins claim their own archives first. The generic one sets the type only while the type is empty, but supplies the "Archive" title on any archive that has no title yet, so tag, category and date archives do not fall back to the first post's title in `template-parts/banner.php`. A last filter, `hyperpress_banner_blog_color` (`library/banner/blog-color.php`), runs at priority 95: for the banner types archive, category, tag, year, month, day and author it sets the Posts page's `hyperpress_banner_background_color` when no colour is set yet (a plugin or earlier filter colour is kept). The other cases (blog, front, page, post, attachment, search, 404) stay at 10: they cannot coincide with a plugin-owned request.
- Use the `hyperpress_` prefix for new code. Legacy `hyper_` names are being renamed; the `foundationpress_` hook names are gone.

## Gotchas

- The root `pnpm-workspace.yaml` (`../../pnpm-workspace.yaml`) `allowBuilds` decides which native build scripts run. Only `nx` is enabled; the image tools (`gifsicle`, `jpegtran-bin`, `mozjpeg`, `optipng-bin`) stay off, and the Foundation build still works without them.
- The working tree usually has unrelated uncommitted changes. Commit with explicit pathspecs, never `git add -A`.
- Gulp 5 `gulp.src` needs `{ encoding: false }` for any binary files (PNG, .mo, fonts), otherwise they are corrupted; `PackageConfigTest` guards the package, copy and images tasks.
- Never put secrets in the repo. `library/plugins.php` once held an API-key URL; do not reproduce it.

## Implementation plans

Follow the root `../../AGENTS.md`; plans are in `../../docs/superpowers/plans/`.
