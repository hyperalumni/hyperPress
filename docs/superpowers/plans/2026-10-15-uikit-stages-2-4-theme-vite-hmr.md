# UIkit Stages 2-4: Timber Theme, Vite Build, Dev HMR Implementation Plan

> **STATUS: ✅ COMPLETED** (2026-10-15). Gate passed: `nx run-many -t build test typecheck` (10 tasks), `nx up` and `nx smoke` against the running `hyperuikit` stack, the Foundation build and `test:node`, `plugins-tooling:lint` and `test:unit`, the uikit theme's PHPUnit (15 tests), and `pnpm install --frozen-lockfile`. Automated HMR proof passed (websocket css update and full reload on a Twig edit); browser confirmation by the owner pending (Task 8 Step 4 stays unticked).

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** A `hyperpress-uikit` Timber theme built with Vite and styled with UIkit from Sass source, served by a Dockerized WordPress site, with hot reload working against it.

**Architecture:** `libs/uikit-tokens` (Sass) and `libs/ui` (TypeScript) feed the theme's Vite entry. `nx build hyperpress-uikit` assembles the finished theme in `dist/themes/hyperpress-uikit/` (copied PHP/Twig, Vite output plus manifest, Composer `vendor/` from a tooling container). A new `docker/uikit` site mounts that folder. A PHP `Assets` class reads the manifest in production and prints Vite dev-server tags while `nx serve` runs.

**Tech Stack:** Nx 23.3.0, Vite, Sass, TypeScript, Vitest, UIkit (Sass source), Timber 2.x, PHPUnit 9.6, Docker Compose, Node 26.12.0, pnpm 12.12.1.

**Spec:** `docs/superpowers/specs/2026-10-07-uikit-nx-wordpress-design.md` (stages 2, 3, 4; "Build and asset loading", "Docker stack", "Error handling", "Testing and verification"). Plan A (stage 1, Nx) is done: `2026-10-15-uikit-stage-1-nx.md`. Plan C (stages 5-7) follows. Stage 1 must be merged into `master` (or this branch cut from it) before execution.

## Global Constraints

- Project names: root `@hyper/press`, theme `@hyper/press-foundation`, new theme `@hyper/press-uikit` (directory `themes/hyperpress-uikit`, WordPress slug `hyperpress-uikit`), libs `@hyper/uikit-tokens` and `@hyper/ui`.
- Nx project config (targets, cache, tags) lives in each project's `project.json`; `package.json` holds scripts and dependencies only. Nx and every `@nx/*` package stay on the same 23.x version via `"catalog:"`. Any dependency used by more than one project (vite, sass, typescript, vitest, uikit) is also pinned in the `catalog:` in `pnpm-workspace.yaml`.
- Node 26.12.0, pnpm only; shell setup per command: `source ~/.nvm/nvm.sh && nvm use && export PATH="$HOME/.local/share/pnpm/bin:$PATH"` (no pipe after `nvm use`). Registry lookups use `pnpm view`.
- PHP and Composer run in Docker only. Real `.env*` files are never read or printed; the owner copies them, and the agent checks key names with EnvSitter.
- Two `dist/` conventions. The new projects build an assembled artifact into the repo-level, git-ignored `dist/<type>/<name>` (e.g. `dist/themes/hyperpress-uikit`, `dist/libs/uikit-tokens`), and compose mounts that artifact. Foundation (`themes/hyperpress-foundation`) is deployable from its own source folder, so it keeps its in-theme `dist/` unchanged. UIkit is consumed from `uikit/src/scss`; variables are overridden and only the used components are imported.
- Missing manifest or missing `vendor/` shows an admin notice, never a fatal error. A stale dev marker with no Vite server running falls back to production assets after a short reachability check.
- Dev: Vite runs on the host at a fixed port with `strictPort`; PHP and Twig changes trigger a full reload.
- Commit from the root with explicit pathspecs written out (`git add <paths>` then `git commit -m "..." -- <paths>`); never `git add -A`. Nothing is pushed. Work is on branch `feature/uikit-stages-2-4`.
- Out of scope here: Oxlint, Stylelint, PHPCS targets and Oxfmt (stage 6, plan C), the stub or real plugin (stage 5).

## Pins (re-verified in Task 1 before use)

Verified 2026-10-15 (Task 1) against the npm registry (`pnpm view`) and Packagist, with pnpm 12.12.1 on Node 26.12.0. Release age is checked against pnpm's default `minimumReleaseAge` of 1440 minutes (24 h; the setting is not set in `pnpm-workspace.yaml`, but `minimumReleaseAgeStrict: true` is), cutoff at check time 2026-10-14T20:58Z.

| Package | Pin | Published | Notes |
| --- | --- | --- | --- |
| `vite` | 8.3.4 | 2026-10-08 | `engines.node` `^20.19.0 \|\| >=22.12.0` allows Node 26.12.0 |
| `@nx/vite` | 23.3.0 | 2026-10-07 | peer `vite ^5 \|\| ^6 \|\| ^7 \|\| ^8`, allows 8.3.4; provides the `@nx/vite:build` executor |
| `@nx/vitest` | 23.3.0 | 2026-10-07 | exists; peers `vite ^5..^8`, `vitest ^3 \|\| ^4 \|\| ^5`, `@nx/eslint 23.3.0`. Not needed unless we use its inferred plugin or executor; run vitest through a plain `vitest run` script. Skip it |
| `uikit` | 3.25.26 | 2026-10-05 | dist-tag `latest`; `dev` is 3.25.27-dev (do not use) |
| `@types/uikit` | 3.23.4 | 2026-10-12 | lags uikit by two minors; `export = UIkit` |
| `sass` | 1.105.1 | 2026-09-29 | |
| `typescript` | 7.0.2 | 2026-07-08 | dist-tag `latest` is 7.0.2 (7.1.0-dev is `next`) |
| `vitest` | **5.0.4** | 2026-10-12 | the reported 5.0.5 was published 2026-10-15T14:29Z, inside the 24 h window, so `pnpm add` fails with `minimumReleaseAge` (reproduced in a probe). Use 5.0.4, or 5.0.5 after 2026-10-16T14:29Z. `engines.node` `^22.12 \|\| ^24 \|\| >=26` allows Node 26.12.0; peers `vite ^6.4 \|\| ^7 \|\| ^8` |
| `oxlint` | 1.88.0 | 2026-10-13 | latest on the registry; stage 6 only, not added here |
| `timber/timber` | ^2.5.1 | 2026-05-29 | requires PHP ^8.2 |
| `phpunit/phpunit` | ^9.6 | 9.6.38 on 2026-10-05 | latest 9.x is 9.6.38 (the repo's 9.6.37 is current-ish; the earlier "9.6.9" claim was wrong). Packagist's newest overall is 13.4.2; stay on `^9.6` to match plugins and theme |
| `yoast/phpunit-polyfills` | ^2.0 | n/a | plugins and theme use `^2.0`; the newest is 4.0.0. Match `^2.0` for consistency (PHPUnit 9 supports it) |

Release age: no pin other than `vitest@5.0.5` is younger than 24 h, so with `vitest` at 5.0.4 **no `minimumReleaseAgeExclude` entries are needed**. If a task bumps `vitest` to 5.0.5 before 2026-10-16T14:29Z, it needs a `vitest` entry in `minimumReleaseAgeExclude`.

UIkit package contents (probe install under `/tmp/opencode/uikit-probe`, deleted afterwards): the npm package ships `src/scss/uikit.scss` (a single `@import "components/_import.scss"`), `variables.scss` (1191 lines of `!default` variables), `mixins.scss`, the theme variants `uikit-theme.scss`, `variables-theme.scss`, `mixins-theme.scss`, and 77 files in `src/scss/components/` (including `variables.scss` and `_import.scss`). It also ships `src/js` (listing showed `api`, `component.js`, `components`), `src/less`, and prebuilt `dist/js` and `dist/css`. The earlier claim that `src/js` is not shipped is wrong, but we use `dist/js` anyway.

- **JS import path (Task 3):** `package.json` has only `"main": "dist/js/uikit.js"` (no `exports`, no `module`, no `types`); it is a UMD bundle (`module.exports = factory()`). Use `import UIkit from 'uikit'` (resolves to `dist/js/uikit.js`; Vite interops UMD default) and `import Icons from 'uikit/dist/js/uikit-icons'` (resolves to `dist/js/uikit-icons.js`, also UMD), then `UIkit.use(Icons)`.
- **Types:** `@types/uikit@3.23.4` provides `index.d.ts` with `export = UIkit`, so TypeScript needs `esModuleInterop` (or `allowSyntheticDefaultImports`) for the default import. It has no declaration for `uikit/dist/js/uikit-icons`; add `declare module 'uikit/dist/js/uikit-icons'` in a local `.d.ts` (typed as a UIkit plugin).
- **Sass import order (Task 3):** UIkit's Sass uses `@import`. `components/_import.scss` does not pull in the root `variables.scss` or `mixins.scss`; compiling `@import "uikit/src/scss/uikit"` alone fails with `Undefined variable`. The order that works, and lets overrides win because every UIkit variable is `!default`, is: (1) our variable overrides (`$global-primary-background: ...;`), (2) `@import "uikit/src/scss/variables";`, (3) `@import "uikit/src/scss/mixins";`, (4) `@import "uikit/src/scss/uikit";`. Probe result: override `#ff0066` appears 18 times in the 381 KB output, a font override appears 4 times. Compile with `--load-path=node_modules`. Without silencing, sass prints about 5 `[import]` deprecation warnings plus "75 repetitive deprecation warnings omitted"; silence with `silenceDeprecations: ['import', 'global-builtin', 'color-functions']` in Vite's `css.preprocessorOptions.scss` and `quietDeps: true`. `mixed-decls` is obsolete in sass 1.105.1 (naming it only prints a warning), so do not list it.

## Review Focus

- UIkit's Sass uses `@import`; Sass deprecation warnings must not fail the build or flood the output.
- Manifest entry with nested `css` and `imports` chunks: every CSS file of the entry and its imported chunks must be enqueued.
- Vite `emptyOutDir` wiping copied PHP/Twig or `vendor/` from `dist` when targets run in the wrong order.
- Dev server reachable from the browser (`localhost`) but not from the WordPress container (needs `host.docker.internal`, which needs `extra_hosts` on Linux): the marker must carry both URLs.
- `dist/themes/hyperpress-uikit` missing when compose starts: Docker Desktop creates an empty root-owned folder (see root `AGENTS.md`).
- Unused UIkit components leaking into the CSS, and unconfigured WordPress (installer page) making the smoke test 302.

---

### Task 1: Verify pins and branch hygiene

**Files:**
- Modify: this plan ("Pins" table, with the verified values)

**Interfaces:**
- Produces: the exact versions every later task adds to `pnpm-workspace.yaml` `catalog:` and `composer.json`.

- [x] **Step 1: Re-check each pin** with `pnpm view <pkg> version` (and `pnpm view uikit dist-tags`); packagist versions via `curl -s https://repo.packagist.org/p2/<vendor>/<pkg>.json`. Check `pnpm view vite engines` allows Node 26.12.0, and `pnpm view @nx/vite@23.3.0 peerDependencies` allows the chosen vite. Replace the Pins paragraph with a table of verified values.
- [x] **Step 2: Check the UIkit package contents** in a throwaway install under `/tmp/opencode/uikit-probe` (not in the repo): confirm `src/scss/uikit.scss` and `components/*.scss`, whether `src/js` exists, and what `main`/`exports` give a TypeScript `import UIkit from 'uikit'`. Record the import path to use in Task 3.
- [x] **Step 3: Check `minimumReleaseAge`.** For each pin run a dry `pnpm add -D -w --dry-run`-style check or note any `ERR_PNPM_NO_MATURE_MATCHING_VERSION`; list needed `minimumReleaseAgeExclude` entries.
- [x] **Step 4: Commit** `docs(plans): verify stage 2-4 version pins` over this plan.

### Task 2: `libs/uikit-tokens`

**Files:**
- Create: `libs/uikit-tokens/{package.json,project.json,src/_variables.scss,src/_components.scss,src/index.scss,tests/tokens.test.ts}`
- Modify: `pnpm-workspace.yaml` (`libs/*` in `packages`; `uikit`, `sass`, `vitest`, `typescript` in `catalog:`)

**Interfaces:**
- Produces: package `@hyper/uikit-tokens` with Sass entry `@hyper/uikit-tokens/src/index.scss` (variable overrides forwarded before UIkit's `variables`, then the used components only) and Nx targets `build` (compiles to `dist/libs/uikit-tokens/index.css`, cached) and `test` (Vitest, cached).

- [x] **Step 1: Write the failing test** `tests/tokens.test.ts`: compile `src/index.scss` with `sass` (`loadPaths: ['node_modules']`, `quietDeps: true`, `silenceDeprecations: ['import']`) and assert (a) the CSS contains the overridden `$global-primary-background` colour inside `.uk-button-primary`, (b) it contains `.uk-button` and `.uk-card`, (c) it contains no `.uk-slider` rule, (d) compiling emits no error.
- [x] **Step 2: Run** `pnpm exec nx test @hyper/uikit-tokens`. Expected: FAIL (no entry file).
- [x] **Step 3: Implement** `_variables.scss` (override at least `$global-primary-background` and `$global-font-family`), `_components.scss` (import UIkit `variables`, `mixins`, and only: base, container, grid, section, nav, navbar, button, card, utility), `index.scss` (variables first, then components). Add `package.json`, `project.json` (targets `build`, `test`, cache true, outputs `{workspaceRoot}/dist/libs/uikit-tokens`).
- [x] **Step 4: Run** the test again, then `pnpm exec nx build @hyper/uikit-tokens` twice (second is a cache hit). Expected: PASS and `dist/libs/uikit-tokens/index.css` exists.
- [x] **Step 5: Commit** `feat(libs): add uikit-tokens Sass library` over `libs/uikit-tokens pnpm-workspace.yaml pnpm-lock.yaml package.json`.

### Task 3: `libs/ui`

**Files:**
- Create: `libs/ui/{package.json,project.json,tsconfig.json,src/index.ts,src/uikit.ts,src/types.d.ts,tests/uikit.test.ts}`

**Interfaces:**
- Produces: `@hyper/ui` exporting `initUikit(root?: Document): void` (loads UIkit and its Icons plugin, calls `UIkit.use(Icons)` once, idempotent) and the ambient `declare module 'uikit'` types. Targets: `test` (Vitest with happy-dom, cached), `typecheck` (`tsc --noEmit`, cached).

- [x] **Step 1: Write the failing test:** `initUikit()` twice calls `UIkit.use` exactly once (spy), and after init a `<span uk-icon="icon: home">` in the document gains an `svg` child.
- [x] **Step 2: Run** `pnpm exec nx test @hyper/ui`. Expected: FAIL.
- [x] **Step 3: Implement** `initUikit` using the import path recorded in Task 1; `@hyper/ui` is TypeScript only and does not depend on `@hyper/uikit-tokens`.
- [x] **Step 4: Run** `pnpm exec nx run-many -t test typecheck -p @hyper/ui`. Expected: PASS.
- [x] **Step 5: Commit** `feat(libs): add ui library with UIkit init` over `libs/ui pnpm-workspace.yaml pnpm-lock.yaml`.

### Task 4: Docker tooling service and `docker/uikit` site

**Files:**
- Create: `docker/tooling/Dockerfile`, `docker/uikit/compose.yaml`, `docker/uikit/.env.local.example`
- Modify: `docker/compose.main.yaml` (add `tooling` service, profile `tools`), root `.gitignore` if `docker/uikit/.env.local` is not already covered

**Interfaces:**
- Produces: `docker compose run --rm --user "$(id -u):$(id -g)" -e COMPOSER_HOME=/tmp/composer [-w /themes/<theme>] tooling <cmd>` (no `--profile tools`) from `docker/uikit/` (image `php:8.5-cli` + Composer 2 + `git unzip libicu-dev` + `intl`, matching `plugins/Dockerfile`; mounts the repo `themes/` and `dist/` at `/themes` and `/dist`), and a site with project name `hyperuikit`, `THEME_SRC=../../dist/themes/hyperpress-uikit`, `THEME_SLUG=hyperpress-uikit`, `extra_hosts: ["host.docker.internal:host-gateway"]` on the `wordpress` service, ports not clashing with 3080/3090/4080/4090.

- [x] **Step 1: Write the failing check:** `docker compose -f docker/uikit/compose.yaml --profile local config --format json` must parse and show the theme bind source ending in `dist/themes/hyperpress-uikit`; today it fails (no file).
- [x] **Step 2: Create the files.** Copy the `hyperalumni` wrapper pattern (`include` with `project_directory: .`, `env_file: [../.env, .env.local, ../.env.versions]`). Keep the existing services, volume names and mounts unchanged; the `extra_hosts` addition applies to all sites and must not change their `config` output otherwise (compare `hyperalumni` before and after).
- [x] **Step 3: Owner step.** The owner creates `docker/uikit/.env.local` from the example, choosing free ports (command block in the execution notes; the agent verifies key names with EnvSitter, never values).
- [x] **Step 4: Verify.** `mkdir -p dist/themes/hyperpress-uikit`; `docker compose run --rm --user "$(id -u):$(id -g)" -e COMPOSER_HOME=/tmp/composer tooling composer --version` (no `--profile tools`: with it alone `wpcli` needs `db`, which lives in the `local` profile; `COMPOSE_PROFILES=local` comes from `.env.local` and running the service enables its own profile) prints Composer 2.x; `config` for `hyperalumni` and `hyperonline` is unchanged apart from `extra_hosts`.
- [x] **Step 5: Commit** `feat(docker): add tooling service and the uikit site` over `docker/tooling docker/uikit/compose.yaml docker/uikit/.env.local.example docker/compose.main.yaml .gitignore`.

### Task 5: Theme `Assets` class (TDD)

**Files:**
- Create: `themes/hyperpress-uikit/{composer.json,phpunit.xml.dist,inc/Assets.php,tests/bootstrap-unit.php,tests/unit/AssetsTest.php,.gitignore}`

**Interfaces:**
- Produces: `namespace HyperPress\Uikit; final class Assets` with
  `__construct(string $themeDir, string $themeUri, ?callable $probe = null)` (`$probe(string $url): bool` reachability, default a 500 ms `wp_remote_get`),
  `isDev(): bool` (true when constant `VITE_DEV` is true, or `.vite-dev` exists and the probe reaches its `internal` URL),
  `devServer(): ?array{browser: string, internal: string}` (parsed from the JSON marker `.vite-dev`),
  `entry(string $key): ?array{file: string, css: list<string>}` (from `.vite/manifest.json`, including CSS of the entry's `imports` chunks, URLs relative to `$themeUri`),
  `notice(): ?string` (message when the manifest or `vendor/autoload.php` is missing and not in dev mode),
  `register(): void` (hooks `wp_enqueue_scripts`, `admin_notices`, `script_loader_tag` to add `type="module"`).

- [x] **Step 1: Write failing tests** in `AssetsTest.php` using a temp directory per test: `testEntryReturnsFileAndCssIncludingImportedChunks`, `testEntryIsNullWhenManifestMissing`, `testNoticeWhenManifestMissing`, `testNoticeWhenVendorMissing`, `testIsDevTrueWhenMarkerAndProbeSucceeds`, `testIsDevFalseWhenMarkerButProbeFails` (stale marker), `testIsDevFalseWhenMarkerIsInvalidJson`, `testDevServerReturnsBothUrls`, `testIsDevTrueWhenConstantDefined` (run in a separate process).
- [x] **Step 2: Run** `docker compose run --rm --user "$(id -u):$(id -g)" -e COMPOSER_HOME=/tmp/composer -w /themes/hyperpress-uikit tooling composer test:unit` from `docker/uikit/`. Expected: FAIL (class not found).
- [x] **Step 3: Implement** `Assets` to satisfy the tests; `composer.json` requires `php >=8.3`, `timber/timber` (Task 1 pin), dev `phpunit/phpunit ^9.6` and `yoast/phpunit-polyfills`, scripts `test:unit`, PSR-4 `HyperPress\Uikit\` -> `inc/`; commit the generated `composer.lock`.
- [x] **Step 4: Run** the tests again. Expected: PASS.
- [x] **Step 5: Commit** `feat(uikit-theme): add the Assets class` over `themes/hyperpress-uikit`.

### Task 6: Theme skeleton, Vite entry, Nx build

**Files:**
- Create: `themes/hyperpress-uikit/{style.css,functions.php,index.php,views/base.twig,views/index.twig,src/main.ts,src/main.scss,vite.config.ts,tsconfig.json,package.json,project.json,bin/copy-theme.mjs}`

**Interfaces:**
- Consumes: `Assets` (Task 5), `@hyper/ui` `initUikit` (Task 3), `@hyper/uikit-tokens/src/index.scss` (Task 2).
- Produces: Nx project `@hyper/press-uikit` with targets `copy-theme` (copies `style.css functions.php index.php inc views composer.json composer.lock` to `dist/themes/hyperpress-uikit`), `vite-build` (`build.manifest: true`, input `src/main.ts`, `outDir` `dist/themes/hyperpress-uikit`, `assetsDir: 'assets'`, `emptyOutDir: false`, so the manifest lands at `.vite/manifest.json`), `composer-install` (tooling container, `composer install --no-dev --optimize-autoloader` in the dist theme), and `build` depending on all three in that order, with `dependsOn` the two libs' `build`. `functions.php` boots Timber (shows the notice, not a fatal, when `vendor/autoload.php` is missing) and `new Assets(...)->register()`. Implementation note: `vite-build` runs `pnpm exec vite build` through `nx:run-commands` because the deprecated `@nx/vite:build` executor fails on Nx 23.3 with "Cannot read properties of undefined (reading 'readFile')"; `copy-theme`, `vite-build` and `composer-install` declare disjoint cache outputs under `dist/themes/hyperpress-uikit`.

- [x] **Step 1: Write the failing check:** `pnpm exec nx build @hyper/press-uikit` fails (project missing).
- [x] **Step 2: Implement** the files. `views/base.twig` prints `wp_head`/`wp_footer`, loads UIkit classes (`uk-container`, `uk-navbar`, `uk-card`) so the smoke test can detect them. `main.ts` imports `main.scss` and calls `initUikit()`. Set `css.preprocessorOptions.scss` `quietDeps: true` and `silenceDeprecations: ['import']`.
- [x] **Step 3: Verify the build.** `pnpm exec nx build @hyper/press-uikit`, then assert `dist/themes/hyperpress-uikit` contains `style.css functions.php index.php views/ vendor/autoload.php .vite/manifest.json assets/*.css assets/*.js`, the manifest has key `src/main.ts` with a `css` list, the CSS has `.uk-button` and no `.uk-slider`, and a second build is a cache hit with the files intact (order check for `emptyOutDir`).
- [x] **Step 4: Verify `nx affected`:** after touching `libs/uikit-tokens/src/_variables.scss`, `pnpm exec nx show projects --affected --base=HEAD` includes `@hyper/uikit-tokens` and `@hyper/press-uikit`, not `@hyper/press-foundation` or the plugins.
- [x] **Step 5: Commit** `feat(uikit-theme): add the Timber theme skeleton and Vite build` over `themes/hyperpress-uikit pnpm-workspace.yaml pnpm-lock.yaml`.

### Task 7: Site up and smoke test

**Files:**
- Create: `docker/uikit/smoke.mjs`
- Modify: `themes/hyperpress-uikit/project.json` (targets `up`, `down`, `smoke`; `build` gets `outputs: []`)
- Modify: `themes/hyperpress-uikit/index.php` (graceful fallback page when Timber/`vendor/` is missing)

**Interfaces:**
- Produces: `nx up @hyper/press-uikit` (runs `build`, then `docker compose --profile local up -d` in `docker/uikit/`), `nx smoke @hyper/press-uikit` (uncached) running `node docker/uikit/smoke.mjs [base-url]`: the homepage returns 200, every URL in `.vite/manifest.json` (entry file and css) returns 200, the body contains `uk-` classes; exit non-zero with the failing URL otherwise.

- [x] **Step 1: Write `smoke.mjs`** and run it against the stopped stack. Expected: FAIL with a connection error.
- [x] **Step 2: Add the targets,** then `pnpm exec nx up @hyper/press-uikit`.
- [x] **Step 3: Owner step.** The owner runs the WordPress install and theme activation from `docker/uikit/` (placeholders for the admin values; use real ones locally, never commit them):
  `docker compose --profile local run --rm wpcli core install --url=http://localhost:5080 --title="HyperPress UIkit" --admin_user=<USER> --admin_password=<PASSWORD> --admin_email=<EMAIL> --skip-email`, then `docker compose --profile local run --rm wpcli theme activate hyperpress-uikit`. The theme mount and the 16 plugin mounts exist in the container.
- [x] **Step 4: Run** `pnpm exec nx smoke @hyper/press-uikit`. Expected: exit 0. With `dist/themes/hyperpress-uikit/vendor` deleted, the homepage still returns 200 (fallback page naming `vendor/` and `nx build @hyper/press-uikit`, no PHP fatal) and with `.vite` moved aside it returns 200 without production assets. The admin notice itself is covered by the `Assets` unit tests, not checked in a browser.
- [x] **Step 5: Commit** `feat(uikit-theme): add up, down and smoke targets`, then `fix(uikit-theme): bound the smoke test retries by time` `fix(uikit-theme): keep the theme bind mount valid across builds` and `fix(uikit-theme): verify the bind mount across cache restores`.

**Task 7 notes:**
- Compose needs `--profile local` for every command (`up`, `stop`, `run wpcli`, `ps`): `COMPOSE_PROFILES` in the env file only feeds the included file's interpolation and does not select profiles; never use `--profile tools`.
- `smoke.mjs` bounds every request at 3 s and retries connection errors/timeouts until `SMOKE_RETRY_SECONDS` (default 30; `0` = single attempt; non-numeric falls back to 30).
- Fallback behavior: without Timber/`vendor/`, `index.php` serves HTTP 200 with a minimal page (`wp_head()`/`wp_footer()` still print the asset tags); without `.vite/manifest.json` the page renders but prints no production assets.
- Bind-mount gotcha: Docker bind-mounts the directory itself, so any event that recreates `dist/themes/hyperpress-uikit` leaves the running container with an empty, stale folder (homepage returns an empty 200, assets 404). Cause found: the `nx:noop` `build` target had no `outputs`, so Nx used its default `{workspaceRoot}/dist/{projectRoot}`, and every cache restore of `build` replaced the whole theme directory. `build` now declares `"outputs": []`. Tested with a live mount, each time with the target restored from cache: removing `assets`+`.vite`, removing the `copy-theme` outputs, removing `vendor`, and emptying the directory while keeping it. In all four the inode stayed the same and the container still served the files (HTTP 200, 18986 bytes). Still unavoidable: a full `rm -rf dist` or `rm -rf dist/themes/hyperpress-uikit` followed by a build/`nx up` recreates the directory (verified: container empty, HTTP 200 with a 0-byte body); recover with `docker compose --profile local up -d --force-recreate wordpress` (a plain `restart` fails with "no such file or directory").
- `build` runs composer inside Docker only when `composer.json`/`composer.lock` change or `vendor` is missing (cached target); a fully cached `nx build` takes about 0.5 s, a forced composer install about 5 s.

### Task 8: Dev server and HMR

**Files:**
- Create: `themes/hyperpress-uikit/bin/serve.mjs`
- Modify: `themes/hyperpress-uikit/vite.config.ts` (`server`: fixed `port` 5173, `strictPort: true`, `host: true`, `cors` allowing the site origin, `origin` set to the browser URL), `project.json` (`serve` target), `docker/uikit/.env.local.example` if the origin needs a key

**Interfaces:**
- Produces: `nx serve @hyper/press-uikit`: runs `build` once, writes `dist/themes/hyperpress-uikit/.vite-dev` (`{"browser":"http://localhost:5173","internal":"http://host.docker.internal:5173"}`), starts Vite, and removes the marker on SIGINT/SIGTERM and on exit. Sass and TS changes hot-reload; PHP and Twig changes (watched in `src`-side copies) re-run `copy-theme` and trigger a full reload.

- [x] **Step 1: CORS and networking spike.** With the site up and `serve` running, record: the browser origin of the site; `curl -H "Origin: <site origin>" -I http://localhost:5173/@vite/client` returns 200 with an `access-control-allow-origin` header; `docker compose exec wordpress curl -sI http://host.docker.internal:5173/@vite/client` returns 200. Write the result into the execution notes, and adjust `server.cors`/`host` until both pass.
- [x] **Step 2: Failing check:** with the marker present and no server running, `Assets::isDev()` is false (covered by Task 5) and the homepage serves production assets; verify this live by creating the marker by hand and loading the page.
- [x] **Step 3: Implement** `serve.mjs` and the target; start `nx serve`, then fetch the homepage and assert the HTML contains `/@vite/client` and `src/main.ts` script tags with the browser URL and no hashed `assets/` file.
- [ ] **Step 4: HMR proof (owner, browser).** Edit `libs/uikit-tokens/src/_variables.scss` colour and see the page restyle without reload; edit `views/index.twig` and see a full reload. Ctrl+C the server: the marker is removed and the page falls back to production assets.
- [x] **Step 5: Commit** `feat(uikit-theme): add the Vite dev server with HMR` over the files above.

**Task 8 notes** (execution, 2026-10-15):
- Run `pnpm exec nx serve @hyper/press-uikit` (continuous, depends on `build`). It listens on :5173 and writes `dist/themes/hyperpress-uikit/.vite-dev`; stop it with Ctrl+C or SIGTERM and the marker is removed. A second `serve` fails fast on the busy port (`strictPort`) and also removes the marker it wrote.
- CORS: the site origin is `http://localhost:5080` (override with `UIKIT_WP_ORIGIN`). `/@vite/client` with that Origin returns 200 with `Access-Control-Allow-Origin: http://localhost:5080`; with `Origin: http://evil.example` it returns 200 without an allow-origin header. `server.allowedHosts` must list `host.docker.internal`; the container's `curl -sI http://host.docker.internal:5173/@vite/client` returns 200, and a foreign `Host` header gets 403.
- Defect found and fixed in `Assets::defaultProbe`: it probed the dev server root, which answers 404 (no `index.html`), so `isDev()` stayed false against a healthy server. It now probes `<internal>/@vite/client`. With a stale marker and no server the page serves the hashed production assets.
- Live homepage with the server up has one `type="module"` script each for `http://localhost:5173/@vite/client` and `http://localhost:5173/src/main.ts` and no `assets/main-*`; `/src/main.scss` is transformed to CSS (200).
- HMR over Vite's websocket (automated, `/tmp/opencode/task8/hmr.mjs`, not committed): changing a colour in `libs/uikit-tokens/src/_variables.scss` produced an `update` message for `/src/main.scss` (no reload); editing `views/index.twig` produced `full-reload` and the dist copy contained the edit. Both edits were reverted.
- Step 4 (browser confirmation) is left unticked: only this automated proof exists. Owner: run `nx serve`, open http://localhost:5080, edit a colour in `_variables.scss` and watch the page restyle without a reload; edit `views/index.twig` and watch a full reload; Ctrl+C the server, the marker disappears and a refresh serves the production assets.
- Plugin decision: `@nx/vite/plugin` is registered in `nx.json` (limited with `include` to `themes/hyperpress-uikit/**/*`, every inferred target given a `vite-*` name). The plugin must resolve from the workspace root, so `@nx/vite` moved from the theme's devDependencies to the root. No target of other projects changed; on `@hyper/press-uikit` the only additions are `serve` and 7 new inferred targets (`vite-build` is the 8th inferred name and merges into the explicit one): `vite-serve`, `vite-dev`, `vite-preview`, `vite-serve-static`, `vite-typecheck`, `vite-build-deps`, `vite-watch-deps`. The explicit `vite-build` and `build` targets are unchanged and the dist file list is identical.

### Task 9: Gate and close-out

**Files:**
- Modify: root `AGENTS.md` (uikit commands, `docker/uikit`, tooling service, the dist mount rule), the spec (stages 2-4 done, PHP 8.5 tooling image instead of 8.3, results of the CORS spike), this plan (boxes, banner, execution notes)

- [x] **Step 1: Gate.** `pnpm exec nx run-many -t build test typecheck` passes for all projects; `nx up` then `nx smoke` passes; the Foundation theme build and plugin `composer lint`/`test:unit` still pass; `pnpm install --frozen-lockfile` exits 0; `git status --short` is clean (no real `.env*`, no `dist/`, no root-owned stray folders per the `find ... -user root` check in `AGENTS.md`).
- [x] **Step 2: Docs** per the Files list, then add `> **STATUS: ✅ COMPLETED** (<date>). <what passed>` and an "Outcome / execution notes" section.
- [x] **Step 3: Commit** `docs: describe the uikit theme and mark stages 2-4 completed` over the three docs.

## Outcome / execution notes

- **Pins used** (Pins table): vite 8.3.4, `@nx/vite` 23.3.0, uikit 3.25.26, `@types/uikit` 3.23.4, sass 1.105.1, typescript 7.0.2, vitest 5.0.4 (5.0.5 was inside the 24 h release-age window), Timber ^2.5.1, PHPUnit ^9.6, `yoast/phpunit-polyfills` ^2.0. The owner chose happy-dom 21.0.1 instead of jsdom for `libs/ui`. `@nx/vitest` was skipped. No `minimumReleaseAgeExclude` entries were needed.
- **`allowBuilds`:** `@parcel/watcher: false` (sass uses it only for `--watch` and ships prebuilt binaries), in line with the existing all-false policy.
- **Owner steps performed:** the local env file for `docker/uikit` (ports 5080/5090) and the WordPress install and theme activation with `wpcli`. The browser HMR check (Task 8 Step 4) is still the owner's.
- **PHP floor:** kept `>=8.3` (matches plugins and Foundation); the theme's `composer.lock` has a platform pin of php 8.3.0; the tooling image is PHP 8.5. The production PHP version is unknown; revisit repo-wide if hosts run 8.4+.
- **`@nx/vite`:** the `@nx/vite:build` executor is deprecated and broke on Nx 23.3, so the inferred `@nx/vite/plugin` is adopted (8 inferred `vite-*` names, of which `vite-build` merges into the explicit target and 7 are new, plus `serve`: 16 targets in all; individual targets cannot be omitted in 23.3). `vite-build` stays disjoint from the other build outputs.
- **Cached noop finding:** the `build` target (`nx:noop`) had no `outputs`, so Nx defaulted to `dist/{projectRoot}`, restored it from cache and recreated `dist/themes/hyperpress-uikit`, which left the WordPress bind mount stale. Fixed with `outputs: []`. Anything that recreates that folder itself needs `docker compose --profile local up -d --force-recreate wordpress` from `docker/uikit`.
- **`Assets::defaultProbe` fix:** the dev-server reachability probe now requests `<internal>/@vite/client`, because the Vite root URL returns 404.
- **CORS/host spike:** Vite blocks unknown Host headers, so `host.docker.internal` is in `allowedHosts`; `extra_hosts` is added to the shared `wordpress` service for Linux. The existing sites' rendered config differs only by that `extra_hosts`.

### Known follow-ups (deferred minors)

- `Assets::defaultProbe` is still public and only covered through `probeUrl()`; no test for a trailing-slash `$themeUri`.
- `vite-build` inputs are too broad (PHP/Twig edits re-run Vite); `libs/uikit-tokens` inputs likewise; `vite-typecheck` duplicates the explicit `typecheck`.
- `script_loader_tag` replaces every ` src=` and does not drop an existing `type=`; Twig `post.title` may be printed unescaped (Timber 2 autoescape unverified).
- The smoke script's retry loop is shared by asset URLs, and `SMOKE_RETRY_SECONDS` accepts floats; the admin notice for a missing manifest or `vendor/` is only unit-tested (owner visual check).
- Stage 7 packaging must strip `.vite-dev` and rebuild `assets/` and `.vite/` from clean (`emptyOutDir` is false, so stale hashed files accumulate).
- `copy-theme` does rm+cp per entry, so the served theme is briefly incomplete during a copy.
- Twig prints `post.title`, `post.link` and `site.url` unescaped and Timber 2's autoescape default is unverified.
- Sass `@import` must move to `@use` before Dart Sass 3 (the silenced `import` deprecation hides it).
- Add PHPCompatibilityWP with `testVersion 8.3` in stage 6 (the theme code is only exercised on PHP 8.5).
- `script_loader_tag` could use WordPress 6.5 script modules instead of string replacement.
- The owner's browser HMR confirmation (Task 8 Step 4) is still pending.
