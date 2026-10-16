# UIkit Stages 5-7: Shared-Lib Plugin, Lint and Format, Packaging, Verdict Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Prove a plugin can consume the shared UIkit Sass lib with Nx tracking the dependency, add Oxlint, Oxfmt, Stylelint and PHPCS as gates, package the uikit theme, and write the experiment verdict on Nx and Oxc for WordPress work.

**Architecture:** A new stub plugin `plugins/hyperpress-example` builds `libs/uikit-tokens` CSS into its own `dist/` and renders `[hp_uikit_card]` with UIkit classes. Oxfmt runs over the whole repo from root scripts; Oxlint (`@nx/oxlint`), Stylelint and PHPCS are Nx targets with distinct names so they never collide with existing `lint` targets. A `package` target zips the assembled theme, and a `setup` target takes a fresh clone to a rendered site.

**Tech Stack:** Nx 23.3.0, Oxlint, Oxfmt, `@nx/oxlint`, Stylelint, PHPCS (WPCS + PHPCompatibilityWP), PHPUnit 9.6, Node 26.12.0, pnpm 12.12.1, Docker Compose.

**Spec:** `docs/superpowers/specs/2026-10-07-uikit-nx-wordpress-design.md` (stages 5, 6, 7; "Stub plugin", "Testing and verification", "Success criteria"). Plans A (`2026-10-15-uikit-stage-1-nx.md`) and B (`2026-10-15-uikit-stages-2-4-theme-vite-hmr.md`) are done; this branch is cut from B's, so B (and A) must be merged first or travel with it.

## Global Constraints

- Project names: root `@hyper/press`, `@hyper/press-foundation`, `@hyper/press-uikit` (`themes/hyperpress-uikit`), `@hyper/uikit-tokens`, `@hyper/ui`; plugins are named `hyperpress-<name>` in their `project.json`.
- Nx project config (targets, cache, tags) lives in `project.json`; `package.json` holds scripts and dependencies only. Shared dependency versions are pinned in the `catalog:` in `pnpm-workspace.yaml` and referenced as `"catalog:"`. The catalog already holds `oxfmt` 0.73.0, `oxlint` 1.88.0, `oxlint-tsgolint` 7.0.2004 and `@nx/oxlint` 23.3.0.
- Node 26.12.0, pnpm only; shell setup per command: `source ~/.nvm/nvm.sh && nvm use && export PATH="$HOME/.local/share/pnpm/bin:$PATH"` (no pipe after `nvm use`). Registry lookups use `pnpm view`.
- PHP and Composer run in Docker only. Compose forms from `docker/uikit`: `docker compose --profile local ...` for the site; one-off tooling `docker compose run --rm --user "$(id -u):$(id -g)" -e COMPOSER_HOME=/tmp/composer [-w /themes/hyperpress-uikit] tooling <cmd>` (never `--profile tools`).
- Real `.env*` files are never read or printed; shell commands containing the string `.env` are rejected by the sandbox. Credentials appear only as placeholders in committed files.
- `dist/` rule: Foundation and plugins keep build output inside their own folder (`<project>/dist`); non-deployable sources assemble into the repo-level `dist/<type>/<name>`. Never recreate the directory `dist/themes/hyperpress-uikit` (stale bind mount; repair `docker compose --profile local up -d --force-recreate wordpress`).
- Cached targets must declare disjoint, specific `outputs`; a cached `nx:noop` aggregator needs `outputs: []`.
- Oxfmt runs over the whole monorepo from root scripts (`"fmt": "oxfmt"`, `"fmt:check": "oxfmt --check"`), not as per-project targets. PHP is formatted by PHPCS only.
- Commit from the root with explicit pathspecs written out; never `git add -A`. Nothing is pushed. Work is on branch `feature/uikit-stages-5-7`.
- Out of scope: Block editor / `theme.json`, content migration, CI, Vite+, Twig linting, hoisting the plugins' PHP tooling, pushing history.

## Pins (re-verified in Task 1 before use)

Reported at planning time, unverified: oxlint 1.88.0, oxfmt 0.73.0, `@nx/oxlint` 23.3.0 (peer `oxlint ^1.43.0`; infers a target per project that has an Oxlint config and lintable files; option `targetName`, default `lint`), stylelint 17.16.0, stylelint-config-standard-scss 17.0.0, postcss-scss 4.0.9. Oxfmt facts (oxc.rs docs): supports JS/TS/JSON/YAML/CSS/SCSS/Markdown; config `.oxfmtrc.json`; `ignorePatterns` uses `.gitignore` syntax; honours `.gitignore`, `.editorconfig` and `.prettierignore`; always skips lock files; `sortPackageJson` defaults to on.

## Review Focus

- A repo-wide Oxfmt reformat changing behavior: Foundation build output hashes, `package.json` key order read by Foundation tests (`sortPackageJson`), Markdown tables in plans, `.prettierrc` differences.
- Inferred Oxlint target colliding with `plugins-tooling:lint` (composer): target names must stay distinct.
- Stylelint false positives on UIkit's own Sass conventions (`@import`, global functions) blocking the build.
- Shortcode attribute injection: every attribute escaped; CSS enqueued only on pages that use the shortcode.
- Adding a 17th plugin mount to the shared compose file changing `hyperalumni` and `hyperonline`.
- The zip containing dev files, stale hashed assets or the `.vite-dev` marker; `setup` leaking the admin password into logs or files.

---

### Task 1: Verify pins, measure Oxfmt and Oxlint scope

**Files:**
- Modify: this plan (verified Pins table, measured scope, ignore list)

**Interfaces:**
- Produces: the exact versions, the `.oxfmtrc.json` option values and `ignorePatterns` list, and the Oxlint rule set that Tasks 2-5 use.

- [ ] **Step 1: Re-check pins** with `pnpm view <pkg> version` and `pnpm view <pkg> time --json` (24 h release-age window): oxlint, oxfmt, `@nx/oxlint`, `oxlint-tsgolint`, stylelint, stylelint-config-standard-scss, postcss-scss, a zip library for Task 9 (`fflate`), and from `plugins/composer.json` the WPCS and PHPCompatibilityWP versions to mirror. Replace the Pins paragraph with a table.
- [ ] **Step 2: Measure Oxfmt** in a throwaway install under `/tmp/opencode` (not in the repo): run `oxfmt --check` over a copy of the repo, record the number of files it would change per top-level directory, which of them are vendored, minified or generated, and which Foundation files differ from `themes/hyperpress-foundation/.prettierrc` settings. Check the effect of `sortPackageJson` on the Foundation `package.json` and whether `themes/hyperpress-foundation/tests` read `package.json` key order.
- [ ] **Step 3: Measure Oxlint** the same way over `libs`, `themes`, `plugins`, `docker`: findings per project at default categories.
- [ ] **Step 4: Decide and record** the `ignorePatterns` (vendored, minified, generated files only), `sortPackageJson` (expected `false`), formatting options mirroring `.prettierrc`, and the Oxlint rule/override set that keeps legacy code from failing the gate.
- [ ] **Step 5: Commit** `docs(plans): verify stage 5-7 pins and lint scope` over this plan.

### Task 2: Oxfmt and the one-time reformat

**Files:**
- Create: `.oxfmtrc.json`
- Modify: root `package.json` (scripts `fmt`, `fmt:check`; devDependency `oxfmt: "catalog:"`), `pnpm-lock.yaml`
- Modify (second commit, formatting only): every file `oxfmt` rewrites

**Interfaces:**
- Produces: `pnpm fmt` and `pnpm fmt:check` (exit 0 when clean); the config decisions from Task 1.

- [ ] **Step 1: Write the failing check:** after installing, `pnpm fmt:check` exits non-zero on the unformatted repo and lists files.
- [ ] **Step 2: Add config and scripts,** commit `build: add Oxfmt` (config, package.json, lockfile only).
- [ ] **Step 3: Run `pnpm fmt`,** review the diff by category (no semantic edits to PHP, none to lock files), commit `style: format the repo with Oxfmt` containing only formatting changes.
- [ ] **Step 4: Verify nothing changed in behavior:** `pnpm fmt:check` exits 0; `pnpm exec nx build @hyper/press-foundation` gives the same `dist` file list and file contents as before the reformat (compare hashes; if hashes differ, diff the minified outputs and report); `nx run @hyper/press-foundation:test:node`, `nx run plugins-tooling:lint` and `test:unit`, the uikit theme PHPUnit, and `nx run-many -t build test typecheck` all pass; `git diff --stat` shows no PHP file changed.
- [ ] **Step 5: Record** the number of files reformatted in the plan notes.

### Task 3: Oxlint through `@nx/oxlint`

**Files:**
- Create: `.oxlintrc.json`
- Modify: `nx.json` (plugin entry `@nx/oxlint` with `targetName: "lint-js"`), root `package.json` (devDependencies `oxlint`, `@nx/oxlint`, `oxlint-tsgolint` all `"catalog:"`), `pnpm-lock.yaml`, and source files for findings

**Interfaces:**
- Produces: an inferred, cached `lint-js` target on every project that has lintable JS/TS files (`@hyper/ui`, `@hyper/uikit-tokens`, `@hyper/press-uikit`, `@hyper/press-foundation`, the example plugin if it has JS). No project's existing targets change.

- [ ] **Step 1: Write the failing check:** `pnpm exec nx run @hyper/ui:lint-js` fails (no such target); a deliberately bad fixture file under `/tmp` linted with the new config exits non-zero.
- [ ] **Step 2: Add** the config (rule set from Task 1), the plugin entry and dependencies. Capture `nx show project <name> --json` for every project before and after: only `lint-js` targets may appear.
- [ ] **Step 3: Run** `pnpm exec nx run-many -t lint-js`; fix real findings in new code (libs, uikit theme `src`, `bin`, `vite.config.ts`, `docker/uikit/*.mjs` if in a project), and use overrides in `.oxlintrc.json` (with a one-line reason) for legacy Foundation and plugin JS.
- [ ] **Step 4: Verify** the second run is a cache hit, a changed source file invalidates only its project, and `nx run plugins-tooling:lint` (composer) still works unchanged.
- [ ] **Step 5: Commit** `build: lint JavaScript and TypeScript with Oxlint`.

### Task 4: Stylelint for the Sass

**Files:**
- Create: `stylelint.config.mjs`
- Modify: root `package.json` (devDependencies `stylelint`, `stylelint-config-standard-scss`, `postcss-scss`, via the catalog), `libs/uikit-tokens/project.json` and `themes/hyperpress-uikit/project.json` (target `lint-css`), `pnpm-workspace.yaml`, `pnpm-lock.yaml`

**Interfaces:**
- Produces: cached `lint-css` targets running `stylelint "<projectRoot>/**/*.scss"` on `@hyper/uikit-tokens` and `@hyper/press-uikit` only (Foundation's legacy Sass is out of scope).

- [ ] **Step 1: Write the failing check:** a scratch `.scss` file with a known violation (`/tmp`, linted with `--config stylelint.config.mjs`) exits non-zero; `nx run @hyper/uikit-tokens:lint-css` fails (no target).
- [ ] **Step 2: Add** the config extending `stylelint-config-standard-scss` with the minimum rule relaxations for UIkit conventions (`@import` of `uikit/src/scss/...`, variable naming); each relaxation gets a one-line reason in the config.
- [ ] **Step 3: Run** `pnpm exec nx run-many -t lint-css`; fix findings in our Sass; verify the cache hit on a second run.
- [ ] **Step 4: Commit** `build: lint Sass with Stylelint`.

### Task 5: PHPCS for the uikit theme

**Files:**
- Create: `themes/hyperpress-uikit/phpcs.xml.dist`
- Modify: `themes/hyperpress-uikit/composer.json` (dev dependencies `wp-coding-standards/wpcs`, `phpcompatibility/phpcompatibility-wp`, `dealerdirect/phpcodesniffer-composer-installer` at the versions `plugins/composer.json` uses; scripts `lint:syntax`, `lint:cs`, `lint`; `allow-plugins`), `composer.lock`, `themes/hyperpress-uikit/project.json` (target `lint-php`), and PHP files for findings

**Interfaces:**
- Produces: `composer lint` for the theme (syntax + WordPress standard + PHPCompatibilityWP `testVersion 8.3-`, text domain `hyperpress-uikit`, prefix namespace `HyperPress\Uikit`, excluding `vendor`, `dist`, `node_modules`, `tests` as the plugins' ruleset does) and an uncached Nx target `lint-php` running it through the tooling container.

- [ ] **Step 1: Write the failing check:** `composer lint` in the theme fails (no script); a scratch PHP file using an 8.4-only feature fails the compatibility sniff.
- [ ] **Step 2: Add** the ruleset and scripts, regenerate the lock with the platform pin `php 8.3.0` kept; add the Nx target.
- [ ] **Step 3: Run** `pnpm exec nx run @hyper/press-uikit:lint-php`; fix findings in `inc/`, `functions.php`, `index.php`, `views` PHP, `tests/`; do not weaken sniffs without a one-line reason.
- [ ] **Step 4: Verify** `composer test:unit` still passes and `vendor/` is not copied into `dist` with dev packages (`composer install --no-dev` result unchanged).
- [ ] **Step 5: Commit** `build(uikit-theme): lint PHP with PHPCS`.

### Task 6: The stub plugin `hyperpress-example` (TDD)

**Files:**
- Create: `plugins/hyperpress-example/{hyperpress-example.php,src/Plugin.php,src/Shortcode.php,package.json,project.json,bin/copy-css.mjs,.gitignore}` and its tests under the shared `plugins/tests/` layout (follow `plugins/AGENTS.md`)
- Modify: `docker/compose.main.yaml` (a 17th plugin bind mount for `hyperpress-example`, same pattern as the other 16), `pnpm-workspace.yaml` only if needed

**Interfaces:**
- Produces: namespace `HyperPress\Example`; `Shortcode::register(): void` (adds `[hp_uikit_card]`), `Shortcode::render(array|string $atts, ?string $content = null): string` returning `<div class="uk-card uk-card-default uk-card-body hp-uikit-card"><h3 class="uk-card-title">…</h3>…</div>` with `title` attribute (default empty → no heading) and enclosed content, every dynamic value escaped; `Plugin::boot(): void` hooking it up and registering the stylesheet handle `hp-example-uikit` (file `dist/uikit.css`) which is enqueued only when the shortcode renders. Nx project `hyperpress-example` (package name `@hyper/press-example`, dependency `"@hyper/uikit-tokens": "workspace:*"`) with cached target `build` (dependsOn `@hyper/uikit-tokens:build`; runs `bin/copy-css.mjs` copying `dist/libs/uikit-tokens/index.css` to `plugins/hyperpress-example/dist/uikit.css`; outputs `{projectRoot}/dist`).

- [ ] **Step 1: Write failing tests:** shortcode registered under `hp_uikit_card`; `render` returns the `uk-card uk-card-default uk-card-body` markup with the title in `uk-card-title` and the content inside; a title of `<script>alert(1)</script>` is escaped; no title means no heading; the stylesheet handle is enqueued after `render` and not before. Run them through `docker compose run ... tooling`/the plugins tooling per `plugins/AGENTS.md`. Expected: FAIL.
- [ ] **Step 2: Implement** the plugin files to satisfy the tests; header and text domain `hyperpress` as the other plugins.
- [ ] **Step 3: Implement** the Nx project and `bin/copy-css.mjs`; verify `pnpm exec nx build hyperpress-example` writes `plugins/hyperpress-example/dist/uikit.css` containing `.uk-card`, and a second build is a cache hit.
- [ ] **Step 4: Add the compose mount** and verify with `docker compose --profile local config` for `hyperalumni`, `hyperonline` and `uikit` that the rendered configs differ from before only by that mount (mount source exists, so no root-owned stray folder).
- [ ] **Step 5: Run** `nx run plugins-tooling:lint` and `test:unit` (all green, plugin passes PHPCS) and commit `feat(example): add the stub plugin that consumes the Sass lib`.

### Task 7: Prove the dependency tracking and the cache

**Files:**
- Modify: this plan (measured results)

- [ ] **Step 1: Graph.** `pnpm exec nx graph --file=/tmp/opencode/stage5/graph.json`: the `hyperpress-example` project has an edge to `@hyper/uikit-tokens`, as does `@hyper/press-uikit`.
- [ ] **Step 2: Affected.** Edit a comment in `libs/uikit-tokens/src/_variables.scss` (uncommitted): `pnpm exec nx show projects --affected --base=HEAD` includes `@hyper/uikit-tokens`, `@hyper/press-uikit` and `hyperpress-example` and excludes `@hyper/ui`, `@hyper/press-foundation` and the other 15 plugins; then revert.
- [ ] **Step 3: Cache.** `nx run-many -t build` twice: the second is a full cache hit; change one token value and run again: only the tokens, the theme and the plugin rebuild. Record cold, cached and partial timings in seconds for the write-up.
- [ ] **Step 4: Commit** the recorded numbers in this plan: `docs(plans): record affected and cache results`.

### Task 8: Shortcode on the running site

**Files:**
- Modify: `docker/uikit/smoke.mjs` (env `SMOKE_EXPECT`: comma-separated substrings the homepage body must contain, each an `ok`/`FAIL` line)

- [ ] **Step 1: Failing check:** with the plugin not activated and no post using the shortcode, `SMOKE_EXPECT=hp-uikit-card node docker/uikit/smoke.mjs` fails naming `hp-uikit-card`.
- [ ] **Step 2: Activate and seed** with wpcli from `docker/uikit` (`docker compose --profile local run --rm wpcli plugin activate hyperpress-example`, then `post create` a published post whose content is `[hp_uikit_card title="Hello"]World[/hp_uikit_card]`).
- [ ] **Step 3: Verify:** `SMOKE_EXPECT=hp-uikit-card pnpm exec nx smoke @hyper/press-uikit` passes, the page references the plugin's stylesheet, and the stylesheet URL returns 200; the plain smoke (no `SMOKE_EXPECT`) still passes.
- [ ] **Step 4: Commit** `feat(uikit-theme): let the smoke test expect shortcode output`.

### Task 9: Packaging

**Files:**
- Create: `themes/hyperpress-uikit/bin/package.mjs`, its test
- Modify: `themes/hyperpress-uikit/project.json` (target `package`), root catalog and `themes/hyperpress-uikit/package.json` (zip library chosen in Task 1)

**Interfaces:**
- Produces: `nx package @hyper/press-uikit` (dependsOn `build`, cached, outputs `{workspaceRoot}/dist/packages`) writing `dist/packages/hyperpress-uikit-<version>.zip` (version from `style.css`) with a single top-level folder `hyperpress-uikit/`, containing the assembled theme except `.vite-dev`, and only the hashed files in `assets/` that the manifest references.

- [ ] **Step 1: Write the failing test** (Node test runner or Vitest in the theme): given a fixture dist with an extra stale `assets/old-123.js` and a `.vite-dev`, the package function's file list contains `style.css`, `functions.php`, `vendor/autoload.php`, `.vite/manifest.json` and the referenced assets, and does not contain the stale asset, `.vite-dev`, `tests/`, or `vendor/phpunit`.
- [ ] **Step 2: Implement** `bin/package.mjs` and the target.
- [ ] **Step 3: Verify** on the real build: `unzip -l` shows the expected layout; extracting into a scratch WordPress-style `themes/` folder and running `php -l` on all PHP passes in the tooling container; with `nx serve` running (marker present) the zip still has no `.vite-dev`.
- [ ] **Step 4: Commit** `feat(uikit-theme): package the theme as a zip`.

### Task 10: One-command setup

**Files:**
- Create: `docker/uikit/setup.mjs`
- Modify: `themes/hyperpress-uikit/project.json` (target `setup`), root `AGENTS.md`

**Interfaces:**
- Produces: `nx setup @hyper/press-uikit` (uncached): `up`, wait until the database accepts connections and WordPress answers, `wp core install` only if `wp core is-installed` fails, activate the theme and the example plugin, create the demo shortcode post if absent. Admin credentials come only from the shell environment variables `UIKIT_ADMIN_USER`, `UIKIT_ADMIN_PASSWORD`, `UIKIT_ADMIN_EMAIL`; missing variables abort with a message naming them; the password is never printed, logged or written to a file. Idempotent on a second run.

- [ ] **Step 1: Write the failing check:** `nx setup` without the variables exits non-zero listing the missing names and starts nothing.
- [ ] **Step 2: Implement** `setup.mjs` and the target.
- [ ] **Step 3: Verify on a scratch project, not your site:** from `docker/uikit` with `-p hyperuikit-fresh` and `WORDPRESS_EXTERNAL_PORT=5180 PHPMYADMIN_EXTERNAL_PORT=5190` set in that command's environment, run the setup against fresh volumes using throwaway credentials; the homepage returns 200 with `SMOKE_EXPECT=hp-uikit-card`; a second run changes nothing; then remove only that scratch project (`docker compose -p hyperuikit-fresh --profile local down -v`). Do not touch the `hyperuikit` project or its volumes.
- [ ] **Step 4: Check** `git grep` and the process list for the throwaway password: absent.
- [ ] **Step 5: Commit** `feat(uikit-theme): add the one-command setup target`.

### Task 11: Gate, verdict and close-out

**Files:**
- Create: `docs/superpowers/audit/2026-10-16-uikit-nx-oxc-verdict.md`
- Modify: root `AGENTS.md` (format, lint, package and setup commands), the spec (stages 5-7 done, success criteria with evidence, `nx: true` in `allowBuilds` replacing the stale `nx: false` mentions), this plan (boxes, banner, notes)

- [ ] **Step 1: Gate.** `pnpm fmt:check`; `pnpm exec nx run-many -t lint-js lint-css lint-php lint build test typecheck`; `nx up` then `SMOKE_EXPECT=hp-uikit-card pnpm exec nx smoke @hyper/press-uikit`; Foundation build and `test:node`; plugin lint and unit tests; `nx package @hyper/press-uikit`; `pnpm install --frozen-lockfile`; `git status --short` clean; no root-owned files outside the known ones.
- [ ] **Step 2: Write the verdict** from the plans' execution notes, the Task 7 timings and the commit history: what each tool bought (Nx caching and affected, `@nx/vite` plugin, UIkit Sass from source, Vite, Timber, Oxlint, Oxfmt, Stylelint, PHPCS), measured numbers, friction found (executor deprecation, stale bind mount, `--profile` quirks, root-owned tooling), and a recommendation per tool (adopt, adopt with caveats, skip). Leave an `Owner verdict:` line under each recommendation for the owner to fill in.
- [ ] **Step 3: Docs and banner** per the Files list; add `> **STATUS: ✅ COMPLETED** (<date>). <what passed>` and an "Outcome / execution notes" section.
- [ ] **Step 4: Commit** `docs: describe stages 5-7 and add the experiment verdict`.
