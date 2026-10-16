# Verdict: Nx and Oxc for WordPress work

Date: 2026-10-16. Branch: `feature/uikit-stages-5-7`. Spec: `docs/superpowers/specs/2026-10-07-uikit-nx-wordpress-design.md`.

## Purpose and scope

The experiment asked whether Nx, UIkit (consumed from Sass source), Vite, Timber and the Oxc tools make a good workflow for the hyperPress WordPress theme and plugins. Everything below was run in this repository on Node 26.12.0 and pnpm 12.12.1.

Tested: Nx 23.3.0 with `@nx/vite` 23.3.0 and `@nx/oxlint` 23.3.0, UIkit 3.25.26 from `uikit/src/scss`, Vite 8.3.4, Timber 2.5.1, Oxlint 1.88.0, Oxfmt 0.73.0, Stylelint 17.16.0 and PHPCS with WordPress Coding Standards 3.4.1 and PHPCompatibilityWP 3.0.0-alpha2. Not tested (out of scope): the block editor and `theme.json`, content migration, CI, Vite+, Twig linting, the Foundation theme's own build moving to Nx-managed Vite.

The experiment theme is `themes/hyperpress-uikit`. It is not a replacement for the Foundation theme.

## What was built

Stage 0 turned the theme and plugins repos into one pnpm workspace with history kept, and stage 0b moved the `hyperalumni` and `hyperonline` Docker stacks into a shared `docker/` folder. Stage 1 added Nx and registered the existing projects. Stages 2-4 added a Timber theme (`themes/hyperpress-uikit`), two libraries (`libs/uikit-tokens` for Sass, `libs/ui` for TypeScript), a Vite build with manifest-based asset loading, a `uikit` Docker site, and a Vite dev server with hot reload against the Dockerized WordPress. Stage 5 added the stub plugin `plugins/hyperpress-example`, which builds the shared Sass into its own `dist/uikit.css` and renders `[hp_uikit_card]`. Stage 6 added Oxfmt (root `fmt` and `fmt:check`, one whole-repo reformat) and the Nx targets `lint-js` (Oxlint), `lint-css` (Stylelint) and `lint-php` (PHPCS for the uikit theme). Stage 7 added `nx package @hyper/press-uikit` (a deterministic zip) and `nx setup @hyper/press-uikit` (a fresh clone to a rendered site).

## Measured numbers

Nx 23.3.0, 22 projects, daemon off, wall seconds (from the stage 5 Task 7 run unless noted).

| Run                                                   | Seconds   | Cache   |
| ----------------------------------------------------- | --------- | ------- |
| `run-many -t build --skip-nx-cache` (cold, 7 tasks)   | 6.3       | skipped |
| `run-many -t build` (cached, second run)              | 0.8       | 7/7 hit |
| `run-many -t build` after one token value changed     | 2.7       | 3/7 hit |
| `run-many -t lint-js` cold / cached (5 tasks)         | 1.3 / 0.8 | 5/5 hit |
| `run-many -t lint-css` cold / cached (2 tasks)        | 1.5 / 0.8 | 2/2 hit |
| `pnpm fmt:check` (Oxfmt, 144 files, not cached by Nx) | 0.4       | n/a     |

Final gate (this task): `nx run-many -t lint-js lint-css lint-php lint build test typecheck` finished 21 tasks, all green, in 10.6 s with 18 of 21 from the cache (the three uncached tasks were `@hyper/press-uikit:typecheck`, `@hyper/press-uikit:lint-php` and `plugins-tooling:lint`; the PHP ones run Docker). `pnpm fmt:check` took 0.2 s on 147 files.

Affected sets (`nx show projects --affected --base=HEAD` with one uncommitted edit, reverted each time): a token Sass comment gives `@hyper/uikit-tokens`, `@hyper/press-uikit` and `hyperpress-example`; `libs/ui/src/index.ts` gives `@hyper/ui` and `@hyper/press-uikit`; a PHP file in one plugin gives that plugin only. Changing one token value rebuilt the tokens, the plugin and the theme's Vite build; the Foundation build and the Composer install were cache hits, and the new colour appeared in both the plugin's `dist/uikit.css` and the theme's hashed CSS.

Built output of the uikit theme: CSS 28.67 kB (5.44 kB gzip), JS 221.62 kB (74.48 kB gzip). The package zip holds 408 files, 677,813 bytes (2,178,663 unpacked), and packaging twice gives identical bytes.

Counts at the gate: Vitest 4 (`uikit-tokens`) + 2 (`ui`) + 5 (uikit theme), Foundation `test:node` 8, plugin unit 176 tests (481 assertions), plugin integration 1,128 tests (3,348 assertions, 6 skipped), uikit theme PHPUnit 18 tests (32 assertions).

## Success criteria

| Criterion                                                                                              | Evidence                                                                                                                                                                                                                                                                                                                                                                                                     |
| ------------------------------------------------------------------------------------------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| Stage 0: one repo, history intact, one root `pnpm install`, Foundation builds as before                | Met. Stage 0 gate in `docs/superpowers/plans/2026-10-11-stage-0-monorepo-restructure.md`; the Foundation `dist` file list and hashes were unchanged after the Oxfmt reformat, and `pnpm install --frozen-lockfile` passes now.                                                                                                                                                                               |
| Changing `libs/uikit-tokens` rebuilds only the new theme and the plugin; a repeat build hits the cache | Met. Affected set and the 3/7-hit partial rebuild above; second build 7/7 hit (0.8 s against 6.3 s cold).                                                                                                                                                                                                                                                                                                    |
| HMR works against the Dockerized WordPress                                                             | Partially met: Sass hot reload was confirmed in a browser by the owner on 2026-10-16 (plus an automated websocket proof); the Twig full reload and the marker removal on Ctrl+C were verified by script only, not by eye.                                                                                                                                                                                    |
| A fresh clone goes from `pnpm install` to a rendered site in one command                               | Met with prerequisites: copy the committed local env example for `docker/uikit`, export `UIKIT_ADMIN_USER`, `UIKIT_ADMIN_PASSWORD` and `UIKIT_ADMIN_EMAIL`, then `pnpm exec nx setup @hyper/press-uikit`. Verified on a scratch project with fresh volumes (about 32 s), idempotent on the second run (about 15 s); the throwaway password was found in no log, file, shell history or sampled process list. |
| A written verdict on Nx and Oxc for WordPress work                                                     | This document. The `Owner verdict:` lines are filled in (owner, 2026-10-16).                                                                                                                                                                                                                                                                                                                                 |

## Friction log

- **Node and PATH.** The shell had Node 26.11.1 under nvm while the repo pins 26.12.0, and pnpm was not on `PATH` in the agent shell. Resolution: install the pinned version through nvm and export `$HOME/.local/share/pnpm/bin` in each command; recorded in `AGENTS.md`.
- **Ignored build scripts.** `pnpm install` failed with `ERR_PNPM_IGNORED_BUILDS` for `nx` and `@parcel/watcher`. Resolution in `allowBuilds`: `nx: true` (the owner allowed Nx's postinstall) and `'@parcel/watcher': false` (Sass uses it only for `--watch` and ships prebuilt binaries). The stage 1 notes had said `nx: false`; that is corrected.
- **Release-age window.** `minimumReleaseAgeStrict: true` blocked vitest 5.0.5 because it was inside the 24 h window; the pin stayed at 5.0.4. Every other pin was at least three days old, so no `minimumReleaseAgeExclude` entry was needed.
- **Deprecated Vite executor and extra targets.** The `@nx/vite:build` executor is deprecated and failed on Nx 23.3. The inferred `@nx/vite/plugin` replaced it, but it adds 7 extra `vite-*` targets per project that cannot be switched off individually in 23.3 (16 targets in all on the theme).
- **Cached noop replaced the theme folder.** The theme's `build` (`nx:noop`) had no `outputs`, so Nx defaulted to `dist/{projectRoot}`, restored that folder from the cache and recreated `dist/themes/hyperpress-uikit`, which left the WordPress bind mount stale. Fix: `outputs: []`. Anything that recreates the folder still needs `docker compose --profile local up -d --force-recreate wordpress`.
- **Compose profile quirks.** `COMPOSE_PROFILES` from the included env file is not honoured by the Compose CLI, so every site command needs `--profile local`; `--profile tools` breaks the tooling one-off run, which must be started without it.
- **Root-owned tooling output.** The tooling container runs as root, so `vendor/` and `dist/` files came out root-owned. Fix: always pass `--user "$(id -u):$(id -g)" -e COMPOSER_HOME=/tmp/composer`.
- **Vite host check.** Vite blocks unknown `Host` headers, so `host.docker.internal` is in `server.allowedHosts`, and the shared `wordpress` service needs `extra_hosts: host.docker.internal:host-gateway` on Linux. The dev server binds `0.0.0.0` and is therefore reachable from the LAN while it runs.
- **Probe bug found only live.** `Assets::defaultProbe` requested the Vite root, which returns 404, so the dev marker was never trusted. Unit tests with a fake probe did not catch it; it now requests `<internal>/@vite/client`.
- **Composer platform.** The tooling image is PHP 8.5, so the first lock file resolved for 8.5. A platform pin `php 8.3.0` keeps the lock valid for the declared `>=8.3` floor.
- **Sass `@import` noise.** UIkit's Sass source still uses `@import`; the deprecation warnings are silenced. A move to `@use` is pending before Dart Sass 3 and the silence hides it.
- **jsdom to happy-dom.** The owner replaced jsdom with happy-dom for the `libs/ui` tests; the lockfile carried stale entries from the first install, cleaned in a later commit.
- **Package target and git-ignored `dist/`.** Nx does not hash git-ignored files, so a view edit left `package` served from the cache with a stale zip. Fix: `inputs` with `dependentTasksOutputFiles` so the upstream task outputs are hashed.
- **Timber `post.preview`.** The template printed `post.preview`, which strips shortcodes, so the card never reached the page. It now prints `post.content`.
- **wp-cli `--prompt`.** Feeding the admin password through `--prompt` echoes the answer and the full command to stdout. `setup` passes the password by environment variable name instead; it is still visible briefly in the `wp` process arguments inside the throwaway container.
- **Repo-wide Oxfmt reformat.** It touched 82 files (36 Markdown, 31 Foundation, plus libs, docker, plugins and root files) and needed guards: Foundation's `gulpfile.mjs` is ignored because `PackageConfigTest` regex-matches its source, Foundation `*.yml` and the compose files keep double quotes because `BuildConfigTest` matches them, and vendored or fixture paths are ignored. `sortPackageJson` is off so key order is not rewritten.
- **Lint target names.** `lint` already meant composer PHPCS on `plugins-tooling`, so the new targets are `lint-js`, `lint-css` and `lint-php`. The `composer.json` files stay 2-space indented (Composer's own style) and `composer.lock` is ignored by Oxfmt.
- **Branch state.** The stage branches are stacked and none is merged to `master`: `feature/uikit-stage-1-nx`, then `feature/uikit-stages-2-4`, then `feature/uikit-stages-5-7` (dozens of commits ahead of `master`). A separate worktree for another feature lives in `.worktrees/` and is not discovered by Nx.

## Recommendations

### Nx task graph, cache and affected

Cold build 6.3 s against 0.8 s cached, and `affected` named exactly the three projects that depend on the token library. The graph also shows the plugin-to-library edge. Costs: two traps (a cached noop without `outputs` replaced the bind-mounted folder, and git-ignored `dist/` is not hashed unless `dependentTasksOutputFiles` is declared), and the Docker-based PHP targets cannot be cached.

Recommendation: adopt with caveats

Owner verdict: Adopt with caveats (owner, 2026-10-16).

### `@nx/vite` plugin

The inferred plugin works where the deprecated executor failed, and `vite-build` merges cleanly into the explicit target. It adds 7 inferred targets that cannot be turned off, and the inferred `vite-serve` does not write the `.vite-dev` marker, so an explicit `serve` target is needed for the WordPress loop.

Recommendation: adopt with caveats

Owner verdict: Adopt with caveats (owner, 2026-10-16).

### UIkit from Sass source

Overriding variables and importing only used components produced a theme stylesheet of 28.67 kB (5.44 kB gzip), and a token change reached both the theme and the plugin in 2.7 s. The cost is that UIkit's own Sass still uses `@import`, so the deprecation is silenced until a `@use` migration. The theme's JS bundle is 221.62 kB (74.48 kB gzip); this experiment did not check how much of it is UIkit.

Recommendation: adopt with caveats

Owner verdict: Adopt with caveats (owner, 2026-10-16).

### Vite

Hashed assets, a manifest and Sass hot reload against Dockerized WordPress all work; the owner confirmed Sass HMR in the browser. The Docker boundary needs deliberate setup (`allowedHosts`, `extra_hosts`, a LAN-reachable dev server, a dev marker with browser and container URLs), and the probe bug only showed up live. Twig full reload and marker cleanup on Ctrl+C are unconfirmed by eye.

Recommendation: adopt with caveats

Owner verdict: Adopt with caveats (owner, 2026-10-16).

### Timber

Timber 2.5.1 rendered the theme and the shortcode card through Twig, and 18 PHPUnit tests cover the `Assets` class. Gaps: `post.preview` strips shortcodes, Twig autoescape of `post.title`, `post.link` and `site.url` is unverified, there is no Twig lint or format tool, and `vendor/` has to be built in the pipeline (Composer in the tooling container).

Recommendation: adopt with caveats

Owner verdict: Adopt with caveats (owner, 2026-10-16).

### Oxlint with `@nx/oxlint`

Five cached `lint-js` targets run in 1.3 s cold and 0.8 s cached, with errors on `correctness` and `suspicious` for new code and warnings for the Foundation legacy code. A type-aware run found one real floating promise in `bin/serve.mjs`, now fixed, but the Nx executor cannot enable `--type-aware` as an option, so that check is not in the gate. The scope is JS and TS only.

Recommendation: adopt with caveats

Owner verdict: Adopt with caveats (owner, 2026-10-16).

### Oxfmt

`pnpm fmt:check` takes 0.2-0.4 s on 144-147 files. The one-time reformat changed 82 files without behaviour change (Foundation `dist` hashes and all test counts equal), but it needed an ignore list and quote overrides because Foundation tests regex-match source text, and Oxfmt does not format PHP or Twig. It is version 0.73.0 and runs outside Nx, so it is not cached or part of `run-many`.

Recommendation: adopt with caveats

Owner verdict: Adopt with caveats (owner, 2026-10-16).

### Stylelint

Stylelint 17 with `stylelint-config-standard-scss` needed no rule relaxations for UIkit's `@import` paths; three findings in our own Sass were fixed. The cached `lint-css` target covers the token library and the uikit theme only (4 files); Foundation's legacy Sass is not covered.

Recommendation: adopt

Owner verdict: Adopt (owner, 2026-10-16).

### PHPCS

WordPress standard plus PHPCompatibilityWP `testVersion 8.3-` found real docblock and syntax issues in the theme and the stub plugin, and the compatibility sniff does catch an 8.4-only construct. It runs only in Docker, needs relaxations for PSR-4 class files and camelCase names in namespaced classes, and depends on alpha releases of the PHPCompatibility packages.

Recommendation: adopt with caveats

Owner verdict: Adopt with caveats (owner, 2026-10-16).

## Open questions

- Production PHP version. Recommendation (owner decision, 2026-10-16: the stack runs PHP 8.3 now, and the owner may move to 8.5 if recommended): keep production on 8.3 and the `composer.json` floor at `>=8.3` for now. Our own code already passes on PHP 8.5 (the plugins' unit and integration suites and the theme's unit tests run on the 8.5 tooling image; PHPCompatibilityWP checks `8.3-`). WordPress core's PHP 8.5 support level was not verified here. Before moving production, check the third-party plugins in the WordPress volume (for example Meta Box AIO and Envato Market) and the versions the host supports. PHP 8.4 is the lower-risk step if the host offers it. When production moves, raise the floor repo-wide in one change: plugins, Foundation, the uikit theme, the `composer.lock` platform pin and the PHPCS `testVersion`.
- Merge strategy: decided by the owner on 2026-10-16. The stacked branches (stage 1, stages 2-4, stages 5-7) are merged into `master` right after this commit.
- Keep `plugins/hyperpress-example`? Decided by the owner on 2026-10-16: it stays in the repo as an example of a plugin that consumes the shared Sass. The plugins' `bin/package` would still zip it without a built `dist/uikit.css`, so build it first or exclude it.
