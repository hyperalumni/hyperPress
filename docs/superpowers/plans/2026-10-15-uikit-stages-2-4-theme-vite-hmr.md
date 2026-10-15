# UIkit Stages 2-4: Timber Theme, Vite Build, Dev HMR Implementation Plan

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

Reported at planning time, unverified: vite 8.3.4, uikit 3.25.26, sass 1.105.1, typescript 7.0.2, vitest 5.0.5, `@nx/vite` and `@nx/vitest` 23.3.0, timber/timber 2.5.1 (PHP ^8.2). UIkit ships `src/scss/uikit.scss`, `variables.scss`, `mixins.scss` and 77 files in `src/scss/components/`; the report claims `src/js` is not shipped.

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

- [ ] **Step 1: Re-check each pin** with `pnpm view <pkg> version` (and `pnpm view uikit dist-tags`); packagist versions via `curl -s https://repo.packagist.org/p2/<vendor>/<pkg>.json`. Check `pnpm view vite engines` allows Node 26.12.0, and `pnpm view @nx/vite@23.3.0 peerDependencies` allows the chosen vite. Replace the Pins paragraph with a table of verified values.
- [ ] **Step 2: Check the UIkit package contents** in a throwaway install under `/tmp/opencode/uikit-probe` (not in the repo): confirm `src/scss/uikit.scss` and `components/*.scss`, whether `src/js` exists, and what `main`/`exports` give a TypeScript `import UIkit from 'uikit'`. Record the import path to use in Task 3.
- [ ] **Step 3: Check `minimumReleaseAge`.** For each pin run a dry `pnpm add -D -w --dry-run`-style check or note any `ERR_PNPM_NO_MATURE_MATCHING_VERSION`; list needed `minimumReleaseAgeExclude` entries.
- [ ] **Step 4: Commit** `docs(plans): verify stage 2-4 version pins` over this plan.

### Task 2: `libs/uikit-tokens`

**Files:**
- Create: `libs/uikit-tokens/{package.json,project.json,src/_variables.scss,src/_components.scss,src/index.scss,tests/tokens.test.ts}`
- Modify: `pnpm-workspace.yaml` (`libs/*` in `packages`; `uikit`, `sass`, `vitest`, `typescript` in `catalog:`)

**Interfaces:**
- Produces: package `@hyper/uikit-tokens` with Sass entry `@hyper/uikit-tokens/src/index.scss` (variable overrides forwarded before UIkit's `variables`, then the used components only) and Nx targets `build` (compiles to `dist/libs/uikit-tokens/index.css`, cached) and `test` (Vitest, cached).

- [ ] **Step 1: Write the failing test** `tests/tokens.test.ts`: compile `src/index.scss` with `sass` (`loadPaths: ['node_modules']`, `quietDeps: true`, `silenceDeprecations: ['import']`) and assert (a) the CSS contains the overridden `$global-primary-background` colour inside `.uk-button-primary`, (b) it contains `.uk-button` and `.uk-card`, (c) it contains no `.uk-slider` rule, (d) compiling emits no error.
- [ ] **Step 2: Run** `pnpm exec nx test @hyper/uikit-tokens`. Expected: FAIL (no entry file).
- [ ] **Step 3: Implement** `_variables.scss` (override at least `$global-primary-background` and `$global-font-family`), `_components.scss` (import UIkit `variables`, `mixins`, and only: base, container, grid, section, nav, navbar, button, card, utility), `index.scss` (variables first, then components). Add `package.json`, `project.json` (targets `build`, `test`, cache true, outputs `{workspaceRoot}/dist/libs/uikit-tokens`).
- [ ] **Step 4: Run** the test again, then `pnpm exec nx build @hyper/uikit-tokens` twice (second is a cache hit). Expected: PASS and `dist/libs/uikit-tokens/index.css` exists.
- [ ] **Step 5: Commit** `feat(libs): add uikit-tokens Sass library` over `libs/uikit-tokens pnpm-workspace.yaml pnpm-lock.yaml package.json`.

### Task 3: `libs/ui`

**Files:**
- Create: `libs/ui/{package.json,project.json,tsconfig.json,src/index.ts,src/uikit.ts,src/types.d.ts,tests/uikit.test.ts}`

**Interfaces:**
- Produces: `@hyper/ui` exporting `initUikit(root?: Document): void` (loads UIkit and its Icons plugin, calls `UIkit.use(Icons)` once, idempotent) and the ambient `declare module 'uikit'` types. Targets: `test` (Vitest with jsdom or happy-dom, cached), `typecheck` (`tsc --noEmit`, cached).

- [ ] **Step 1: Write the failing test:** `initUikit()` twice calls `UIkit.use` exactly once (spy), and after init a `<span uk-icon="icon: home">` in the document gains an `svg` child.
- [ ] **Step 2: Run** `pnpm exec nx test @hyper/ui`. Expected: FAIL.
- [ ] **Step 3: Implement** `initUikit` using the import path recorded in Task 1; `@hyper/ui` is TypeScript only and does not depend on `@hyper/uikit-tokens`.
- [ ] **Step 4: Run** `pnpm exec nx run-many -t test typecheck -p @hyper/ui`. Expected: PASS.
- [ ] **Step 5: Commit** `feat(libs): add ui library with UIkit init` over `libs/ui pnpm-workspace.yaml pnpm-lock.yaml`.

### Task 4: Docker tooling service and `docker/uikit` site

**Files:**
- Create: `docker/tooling/Dockerfile`, `docker/uikit/compose.yaml`, `docker/uikit/.env.local.example`
- Modify: `docker/compose.main.yaml` (add `tooling` service, profile `tools`), root `.gitignore` if `docker/uikit/.env.local` is not already covered

**Interfaces:**
- Produces: `docker compose --profile tools run --rm tooling <cmd>` from `docker/uikit/` (image `php:8.5-cli` + Composer 2 + `git unzip libicu-dev` + `intl`, matching `plugins/Dockerfile`; mounts the repo `themes/` and `dist/` at `/themes` and `/dist`), and a site with project name `hyperuikit`, `THEME_SRC=../../dist/themes/hyperpress-uikit`, `THEME_SLUG=hyperpress-uikit`, `extra_hosts: ["host.docker.internal:host-gateway"]` on the `wordpress` service, ports not clashing with 3080/3090/4080/4090.

- [ ] **Step 1: Write the failing check:** `docker compose -f docker/uikit/compose.yaml --profile local config --format json` must parse and show the theme bind source ending in `dist/themes/hyperpress-uikit`; today it fails (no file).
- [ ] **Step 2: Create the files.** Copy the `hyperalumni` wrapper pattern (`include` with `project_directory: .`, `env_file: [../.env, .env.local, ../.env.versions]`). Keep the existing services, volume names and mounts unchanged; the `extra_hosts` addition applies to all sites and must not change their `config` output otherwise (compare `hyperalumni` before and after).
- [ ] **Step 3: Owner step.** The owner creates `docker/uikit/.env.local` from the example, choosing free ports (command block in the execution notes; the agent verifies key names with EnvSitter, never values).
- [ ] **Step 4: Verify.** `mkdir -p dist/themes/hyperpress-uikit`; `docker compose --profile tools run --rm tooling composer --version` prints Composer 2.x; `config` for `hyperalumni` and `hyperonline` is unchanged apart from `extra_hosts`.
- [ ] **Step 5: Commit** `feat(docker): add tooling service and the uikit site` over `docker/tooling docker/uikit/compose.yaml docker/uikit/.env.local.example docker/compose.main.yaml .gitignore`.

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

- [ ] **Step 1: Write failing tests** in `AssetsTest.php` using a temp directory per test: `testEntryReturnsFileAndCssIncludingImportedChunks`, `testEntryIsNullWhenManifestMissing`, `testNoticeWhenManifestMissing`, `testNoticeWhenVendorMissing`, `testIsDevTrueWhenMarkerAndProbeSucceeds`, `testIsDevFalseWhenMarkerButProbeFails` (stale marker), `testIsDevFalseWhenMarkerIsInvalidJson`, `testDevServerReturnsBothUrls`, `testIsDevTrueWhenConstantDefined` (run in a separate process).
- [ ] **Step 2: Run** `docker compose --profile tools run --rm tooling composer test:unit` from `docker/uikit/`. Expected: FAIL (class not found).
- [ ] **Step 3: Implement** `Assets` to satisfy the tests; `composer.json` requires `php >=8.3`, `timber/timber` (Task 1 pin), dev `phpunit/phpunit ^9.6` and `yoast/phpunit-polyfills`, scripts `test:unit`, PSR-4 `HyperPress\Uikit\` -> `inc/`; commit the generated `composer.lock`.
- [ ] **Step 4: Run** the tests again. Expected: PASS.
- [ ] **Step 5: Commit** `feat(uikit-theme): add the Assets class` over `themes/hyperpress-uikit`.

### Task 6: Theme skeleton, Vite entry, Nx build

**Files:**
- Create: `themes/hyperpress-uikit/{style.css,functions.php,index.php,views/base.twig,views/index.twig,src/main.ts,src/main.scss,vite.config.ts,tsconfig.json,package.json,project.json,bin/copy-theme.mjs}`

**Interfaces:**
- Consumes: `Assets` (Task 5), `@hyper/ui` `initUikit` (Task 3), `@hyper/uikit-tokens/src/index.scss` (Task 2).
- Produces: Nx project `@hyper/press-uikit` with targets `copy-theme` (copies `style.css functions.php index.php inc views composer.json composer.lock` to `dist/themes/hyperpress-uikit`), `vite-build` (`build.manifest: true`, input `src/main.ts`, `outDir` `dist/themes/hyperpress-uikit`, `assetsDir: 'assets'`, `emptyOutDir: false`, so the manifest lands at `.vite/manifest.json`), `composer-install` (tooling container, `composer install --no-dev --optimize-autoloader` in the dist theme), and `build` depending on all three in that order, with `dependsOn` the two libs' `build`. `functions.php` boots Timber (shows the notice, not a fatal, when `vendor/autoload.php` is missing) and `new Assets(...)->register()`.

- [ ] **Step 1: Write the failing check:** `pnpm exec nx build @hyper/press-uikit` fails (project missing).
- [ ] **Step 2: Implement** the files. `views/base.twig` prints `wp_head`/`wp_footer`, loads UIkit classes (`uk-container`, `uk-navbar`, `uk-card`) so the smoke test can detect them. `main.ts` imports `main.scss` and calls `initUikit()`. Set `css.preprocessorOptions.scss` `quietDeps: true` and `silenceDeprecations: ['import']`.
- [ ] **Step 3: Verify the build.** `pnpm exec nx build @hyper/press-uikit`, then assert `dist/themes/hyperpress-uikit` contains `style.css functions.php index.php views/ vendor/autoload.php .vite/manifest.json assets/*.css assets/*.js`, the manifest has key `src/main.ts` with a `css` list, the CSS has `.uk-button` and no `.uk-slider`, and a second build is a cache hit with the files intact (order check for `emptyOutDir`).
- [ ] **Step 4: Verify `nx affected`:** after touching `libs/uikit-tokens/src/_variables.scss`, `pnpm exec nx show projects --affected --base=HEAD` includes `@hyper/uikit-tokens` and `@hyper/press-uikit`, not `@hyper/press-foundation` or the plugins.
- [ ] **Step 5: Commit** `feat(uikit-theme): add the Timber theme skeleton and Vite build` over `themes/hyperpress-uikit pnpm-workspace.yaml pnpm-lock.yaml`.

### Task 7: Site up and smoke test

**Files:**
- Create: `docker/uikit/smoke.mjs`
- Modify: `themes/hyperpress-uikit/project.json` (targets `up`, `down`, `smoke`)

**Interfaces:**
- Produces: `nx up @hyper/press-uikit` (runs `build`, then `docker compose --profile local up -d` in `docker/uikit/`), `nx smoke @hyper/press-uikit` (uncached) running `node docker/uikit/smoke.mjs <base-url>`: the homepage returns 200, every URL in `.vite/manifest.json` (entry file and css) returns 200, the body contains `uk-` classes; exit non-zero with the failing URL otherwise.

- [ ] **Step 1: Write `smoke.mjs`** and run it against the stopped stack. Expected: FAIL with a connection error.
- [ ] **Step 2: Add the targets,** then `pnpm exec nx up @hyper/press-uikit`.
- [ ] **Step 3: Owner step.** The owner runs the WordPress install and theme activation with `docker compose --profile local run --rm wpcli core install ...` then `theme activate hyperpress-uikit` (command block with placeholders for URL, title, admin user, password, email, written into the execution notes). Check that the theme and plugin mounts exist in the container.
- [ ] **Step 4: Run** `pnpm exec nx smoke @hyper/press-uikit`. Expected: exit 0. With `dist/themes/hyperpress-uikit/vendor` deleted, the homepage still returns 200 and the admin shows the notice.
- [ ] **Step 5: Commit** `feat(uikit-theme): add up, down and smoke targets` over the two files.

### Task 8: Dev server and HMR

**Files:**
- Create: `themes/hyperpress-uikit/bin/serve.mjs`
- Modify: `themes/hyperpress-uikit/vite.config.ts` (`server`: fixed `port` 5173, `strictPort: true`, `host: true`, `cors` allowing the site origin, `origin` set to the browser URL), `project.json` (`serve` target), `docker/uikit/.env.local.example` if the origin needs a key

**Interfaces:**
- Produces: `nx serve @hyper/press-uikit`: runs `build` once, writes `dist/themes/hyperpress-uikit/.vite-dev` (`{"browser":"http://localhost:5173","internal":"http://host.docker.internal:5173"}`), starts Vite, and removes the marker on SIGINT/SIGTERM and on exit. Sass and TS changes hot-reload; PHP and Twig changes (watched in `src`-side copies) re-run `copy-theme` and trigger a full reload.

- [ ] **Step 1: CORS and networking spike.** With the site up and `serve` running, record: the browser origin of the site; `curl -H "Origin: <site origin>" -I http://localhost:5173/@vite/client` returns 200 with an `access-control-allow-origin` header; `docker compose exec wordpress curl -sI http://host.docker.internal:5173/@vite/client` returns 200. Write the result into the execution notes, and adjust `server.cors`/`host` until both pass.
- [ ] **Step 2: Failing check:** with the marker present and no server running, `Assets::isDev()` is false (covered by Task 5) and the homepage serves production assets; verify this live by creating the marker by hand and loading the page.
- [ ] **Step 3: Implement** `serve.mjs` and the target; start `nx serve`, then fetch the homepage and assert the HTML contains `/@vite/client` and `src/main.ts` script tags with the browser URL and no hashed `assets/` file.
- [ ] **Step 4: HMR proof (owner, browser).** Edit `libs/uikit-tokens/src/_variables.scss` colour and see the page restyle without reload; edit `views/index.twig` and see a full reload. Ctrl+C the server: the marker is removed and the page falls back to production assets.
- [ ] **Step 5: Commit** `feat(uikit-theme): add the Vite dev server with HMR` over the files above.

### Task 9: Gate and close-out

**Files:**
- Modify: root `AGENTS.md` (uikit commands, `docker/uikit`, tooling service, the dist mount rule), the spec (stages 2-4 done, PHP 8.5 tooling image instead of 8.3, results of the CORS spike), this plan (boxes, banner, execution notes)

- [ ] **Step 1: Gate.** `pnpm exec nx run-many -t build test typecheck` passes for all projects; `nx up` then `nx smoke` passes; the Foundation theme build and plugin `composer lint`/`test:unit` still pass; `pnpm install --frozen-lockfile` exits 0; `git status --short` is clean (no real `.env*`, no `dist/`, no root-owned stray folders per the `find ... -user root` check in `AGENTS.md`).
- [ ] **Step 2: Docs** per the Files list, then add `> **STATUS: ✅ COMPLETED** (<date>). <what passed>` and an "Outcome / execution notes" section.
- [ ] **Step 3: Commit** `docs: describe the uikit theme and mark stages 2-4 completed` over the three docs.
