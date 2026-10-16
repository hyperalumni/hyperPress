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

## Pins (verified in Task 1, 2026-10-16 19:47 UTC)

| Package                                          | Pin                                                           | Published (UTC) | Notes                                                                                                                                                                                                                                                                                                 |
| ------------------------------------------------ | ------------------------------------------------------------- | --------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `oxfmt`                                          | 0.73.0                                                        | 2026-10-13      | latest; older than the 24 h `minimumReleaseAge`; already in the catalog                                                                                                                                                                                                                               |
| `oxlint`                                         | 1.88.0                                                        | 2026-10-13      | latest; already in the catalog                                                                                                                                                                                                                                                                        |
| `oxlint-tsgolint`                                | 7.0.2004                                                      | 2026-10-10      | latest; already in the catalog. Runs with TypeScript 7.0.2 (see "Measured scope")                                                                                                                                                                                                                     |
| `@nx/oxlint`                                     | 23.3.0                                                        | 2026-10-07      | already in the catalog; `main`/`exports["."]` is `dist/index.js`, exporting only `createNodes`/`createNodesV2`/`createDependencies`, so the `nx.json` entry `"@nx/oxlint"` is correct (no `./plugin` subpath); executor `@nx/oxlint:lint`; option `targetName`, default `lint`; peer `oxlint ^1.43.0` |
| `stylelint`                                      | 17.16.0                                                       | 2026-10-01      | latest; add to the catalog in Task 4                                                                                                                                                                                                                                                                  |
| `stylelint-config-standard-scss`                 | 17.0.0                                                        | 2026-01-15      | latest; add in Task 4                                                                                                                                                                                                                                                                                 |
| `postcss-scss`                                   | 4.0.9                                                         | 2023-09-28      | latest (old but current); add in Task 4                                                                                                                                                                                                                                                               |
| `fflate`                                         | 0.8.3                                                         | 2026-05-16      | zip library for Task 9 (pure JS, no build script); add in Task 9                                                                                                                                                                                                                                      |
| `wp-coding-standards/wpcs`                       | `^3.1` (locked 3.4.1)                                         | n/a             | plugins and Foundation use the same constraint and lock; mirror `^3.1`                                                                                                                                                                                                                                |
| `phpcompatibility/phpcompatibility-wp`           | `^3.0@alpha` (locked 3.0.0-alpha2)                            | n/a             | with `phpcompatibility/php-compatibility` `^10.0@alpha` (10.0.0-alpha2) and `phpcompatibility/phpcompatibility-paragonie` `^2.0@alpha` (2.0.0-alpha2); mirror all three                                                                                                                               |
| `dealerdirect/phpcodesniffer-composer-installer` | `^1.0` in plugins, `^1.2.0` in Foundation (both locked 1.2.1) | n/a             | mirror `^1.2.0`; `config.allow-plugins` must allow it; PHPCS is `squizlabs/php_codesniffer` 3.13.6 (transitive)                                                                                                                                                                                       |

All JavaScript pins are at least three days past publication, so `minimumReleaseAgeStrict: true` does not block them. Oxfmt facts (oxc.rs docs, confirmed by the measurements below): supports JS/TS/JSON/YAML/CSS/SCSS/Markdown; config `.oxfmtrc.json`; `ignorePatterns` uses `.gitignore` syntax; honours `.gitignore` and `.prettierignore`; `sortPackageJson` defaults to on.

### Measured scope (probe in `/tmp/opencode/stage6-probe` on a `git archive HEAD` copy; nothing in the repo was touched)

**Oxfmt.** `themes/hyperpress-foundation` has no `.prettierrc` (none is tracked, and `package.json` has no `prettier` key), so there is no Prettier setting to mirror; its `.editorconfig` (2-space indent, LF, UTF-8, final newline, tabs for `*.php`/`*.html`) is nested and Oxfmt does not read it from the repo root. The code mostly uses single quotes, 2-space indent and no print-width wrapping beyond 100.

| Run                                           | Files that would change (of 144 checked)                                                                                                                                                                  |
| --------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Defaults, no config                           | 95 (`docs` 36, Foundation 33, `libs` 7, uikit theme 6, root 4, `plugins` 7, `docker` 2)                                                                                                                   |
| `singleQuote: true`, `sortPackageJson: false` | 93                                                                                                                                                                                                        |
| Final config below                            | 83 (`docs` 36 Markdown, Foundation 31 incl. 22 SCSS, uikit theme 6 incl. its `composer.json`, `libs` 3 SCSS, root 2, `docker` 2, `plugins` 3); the new `.oxfmtrc.json` itself is a 84th file in the probe |

By extension for the final run: md 41, scss 27, json 6, mjs 5, js 3, yaml 1, ts 1. Remaining changes are whitespace and line wrapping only (SCSS blank lines and one-line rules, `{a}` to `{ a }`, JSON short arrays collapsed, trailing commas, long calls wrapped); the uikit theme's `bin/*.mjs` and `vite.config.ts` use tabs, so they re-indent to spaces.

Decisions, each from a measurement:

- **Ignore `themes/hyperpress-foundation/gulpfile.mjs`.** `tests/unit/PackageConfigTest.php` asserts on its source text (`/gulp\.src\(PATHS.package\s*,.../`); Oxfmt rewrites it to a method chain (`gulp\n.src(PATHS.package, ...)`), which fails that regex. Foundation's other source-text tests (`_navigation.scss`, `_wp-admin.scss` selectors) survive the reformat.
- **Ignore `plugins/hyperpress-countdown/assets/**`** (vendored TimeCircles JS/CSS, 1,850 + 34 changed lines), **`plugins/tests/fixtures/**`** (captured legacy data, 1,470 changed lines, consumed by the parity tests), **`themes/hyperpress-foundation/CHANGELOG.md`** (upstream FoundationPress history) and **`**/composer.lock`** (Composer-written).
- **`singleQuote: false` for `themes/hyperpress-foundation/**/*.{yml,yaml}` and `**/compose.yaml`**: `tests/unit/BuildConfigTest.php` matches `config-default.yml` entries with `"..."` (double quotes); `singleQuote: true` would break it. `pnpm-workspace.yaml` (single-quoted, written by pnpm) is unchanged under `singleQuote: true`, and its comments and the `&nxVersion` anchor and `*nxVersion` aliases are preserved. `docker/compose.main.yaml` stays flagged only for `[ "local" ]` to `["local"]`.
- **`sortPackageJson: false`.** With the default, every workspace `package.json` except `libs/uikit-tokens` changes (Foundation 18 diff lines: moves `title`, `repository`, `private`, `compact`; the root 8, `libs/ui` 2, the uikit theme 2) and it would also fight `pnpm add`. The only test reading it (`PackageConfigTest::test_package_json_has_no_version_field`) uses `json_decode` and `assertArrayNotHasKey`, so key order is not tested; the gulp files and `docker/uikit` never read it. The reorder buys nothing.
- **`composer.json`**: the override `tabWidth: 4` works (both files reformat 2 to 4 spaces; `lint` arrays expand one entry per line, as Composer writes them) and a write followed by `--check` passes. Note both files are 2-space today, so this is a one-time reindent; `**/composer.lock` is ignored.
- **Markdown**: all 97 `### Task N:` headings, 751 `- [x]` and 66 `- [ ]` boxes (and the 6 indented ones) survive unchanged. Changes are blank lines inserted after headings and before lists (about 490) and tables re-padded to aligned columns (`|---|` to `| --- |`, long notes cells widen the lines). Whitespace only, but it touches 36 files in `docs/`.
- **Ignored paths**: decoy unformatted files in `dist/`, `vendor/`, `node_modules/`, `.nx/` and `.worktrees/` were all skipped (the `.gitignore` in the copy is honoured). Idempotence: `oxfmt` (write) then `oxfmt --check` exits 0.

Exact `.oxfmtrc.json` for Task 2:

```json
{
  "singleQuote": true,
  "sortPackageJson": false,
  "ignorePatterns": [
    "**/composer.lock",
    "plugins/hyperpress-countdown/assets/**",
    "plugins/tests/fixtures/**",
    "themes/hyperpress-foundation/CHANGELOG.md",
    "themes/hyperpress-foundation/gulpfile.mjs"
  ],
  "overrides": [
    { "files": ["**/composer.json"], "options": { "tabWidth": 4 } },
    {
      "files": [
        "themes/hyperpress-foundation/**/*.{yml,yaml}",
        "**/compose.yaml",
        "**/compose.*.yaml"
      ],
      "options": { "singleQuote": false }
    }
  ]
}
```

(`**/compose.*.yaml` did not match `docker/compose.main.yaml` in the probe; harmless, as that file takes `singleQuote: true` and only the `profiles` brackets change.) Task 2 must also run Foundation's unit tests after the reformat and may drop the Markdown padding by adding `docs/**` to `ignorePatterns` if the 36-file docs churn is judged not worth it.

**Oxlint 1.88.0** over `libs themes plugins docker` at defaults: 19 files linted, 8 warnings, 0 errors. `libs/ui`, `libs/uikit-tokens`, `themes/hyperpress-uikit` and `docker/uikit` are clean. Foundation: 4 warnings (`no-unused-vars` x2 in `gulpfile.mjs` and `foundation.js`, `no-useless-escape`, `unicorn/no-empty-file` for `app.js`); `plugins/hyperpress-countdown`: 4 warnings, all in the vendored `TimeCircles.js`. Adding `suspicious` at error level adds one hit, `unicorn/consistent-function-scoping` in Foundation, none in new code. Type-aware (`oxlint --type-aware`, tsgolint with TypeScript 7.0.2) runs, but it needs installed dependencies (`Cannot find type definition file for 'vite/client'` in the probe) and already flags a real `no-floating-promises` at `themes/hyperpress-uikit/bin/serve.mjs:67`. Decision: leave type-aware out of the gate in Task 4 and report that finding in Task 4/11 (enable later as an opt-in `lint-js:types`-style target).

Exact `.oxlintrc.json` for Task 4 (verified: `oxlint libs themes plugins docker` exits 0, and `--deny-warnings` on `libs themes/hyperpress-uikit docker` exits 0, so new code gets zero tolerance while Foundation legacy stays warnings):

```json
{
  "$schema": "./node_modules/oxlint/configuration_schema.json",
  "plugins": ["typescript", "unicorn", "oxc"],
  "categories": { "correctness": "error", "suspicious": "error" },
  "env": { "browser": true, "node": true, "es2022": true },
  "ignorePatterns": ["**/dist/**", "**/vendor/**", "plugins/hyperpress-countdown/assets/**"],
  "overrides": [
    {
      "files": ["themes/hyperpress-foundation/**"],
      "rules": {
        "no-unused-vars": "warn",
        "no-useless-escape": "warn",
        "unicorn/no-empty-file": "off",
        "unicorn/consistent-function-scoping": "off"
      }
    }
  ]
}
```

Reasons: the Foundation override keeps the unmodified FoundationPress-era code (unused imports, escapes, an intentionally empty `app.js`) from failing the gate; the countdown ignore is the vendored library.

**`@nx/oxlint` attachment.** The plugin creates a target per project that has a governing Oxlint config (the root `.oxlintrc.json` governs everything) and lintable files, named `targetName` (use `lint-js`). Expected projects: `@hyper/ui`, `@hyper/uikit-tokens`, `@hyper/press-uikit`, `@hyper/press-foundation`, the root, and `plugins-tooling` (its project root is `plugins/`, so it would lint the countdown plugin's JS; its composer `lint` target is a different name, so there is no collision). Individual `plugins/hyperpress-*` folders are not Nx projects, and `docker/uikit` is not a project (the root project covers it). Task 4 must confirm with `nx show project <name> --json` before and after, as already planned.

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

- [x] **Step 1: Re-check pins** with `pnpm view <pkg> version` and `pnpm view <pkg> time --json` (24 h release-age window): oxlint, oxfmt, `@nx/oxlint`, `oxlint-tsgolint`, stylelint, stylelint-config-standard-scss, postcss-scss, a zip library for Task 9 (`fflate`), and from `plugins/composer.json` the WPCS and PHPCompatibilityWP versions to mirror. Replace the Pins paragraph with a table.
- [x] **Step 2: Measure Oxfmt** in a throwaway install under `/tmp/opencode` (not in the repo): run `oxfmt --check` over a copy of the repo, record the number of files it would change per top-level directory, which of them are vendored, minified or generated, and which Foundation files differ from `themes/hyperpress-foundation/.prettierrc` settings. Check the effect of `sortPackageJson` on the Foundation `package.json` and whether `themes/hyperpress-foundation/tests` read `package.json` key order.
- [x] **Step 3: Measure Oxlint** the same way over `libs`, `themes`, `plugins`, `docker`: findings per project at default categories.
- [x] **Step 4: Decide and record** the `ignorePatterns` (vendored, minified, generated files only), `sortPackageJson` (expected `false`), formatting options mirroring `.prettierrc`, and the Oxlint rule/override set that keeps legacy code from failing the gate.
- [x] **Step 5: Commit** `docs(plans): verify stage 5-7 pins and lint scope` over this plan.

### Task 2: Oxfmt and the one-time reformat

**Files:**

- Create: `.oxfmtrc.json`
- Modify: root `package.json` (scripts `fmt`, `fmt:check`; devDependency `oxfmt: "catalog:"`), `pnpm-lock.yaml`
- Modify (second commit, formatting only): every file `oxfmt` rewrites

**Interfaces:**

- Produces: `pnpm fmt` and `pnpm fmt:check` (exit 0 when clean); the config decisions from Task 1.

- [x] **Step 1: Write the failing check:** after installing, `pnpm fmt:check` exits non-zero on the unformatted repo and lists files.
- [x] **Step 2: Add config and scripts,** commit `build: add Oxfmt` (config, package.json, lockfile only).
- [x] **Step 3: Run `pnpm fmt`,** review the diff by category (no semantic edits to PHP, none to lock files), commit `style: format the repo with Oxfmt` containing only formatting changes.
- [x] **Step 4: Verify nothing changed in behavior:** `pnpm fmt:check` exits 0; `pnpm exec nx build @hyper/press-foundation` gives the same `dist` file list and file contents as before the reformat (compare hashes; if hashes differ, diff the minified outputs and report); `nx run @hyper/press-foundation:test:node`, `nx run plugins-tooling:lint` and `test:unit`, the uikit theme PHPUnit, and `nx run-many -t build test typecheck` all pass; `git diff --stat` shows no PHP file changed.
- [x] **Step 5: Record** the number of files reformatted in the plan notes.

**Task 2 notes:**

- Commits: `a8b07cf` `build: add Oxfmt` (config, `package.json`, lockfile); `2ade45e` `style: format the repo with Oxfmt` (82 files, formatting only). RED: `pnpm fmt:check` with no config exited 1 listing 95 of 144 files; after the config 82 files were left, and `pnpm fmt:check` now exits 0 on 139 files with a second `pnpm fmt` leaving `git status` clean.
- Files reformatted: `docs` 36 (Markdown), `themes` 36 (Foundation 31: 25 under `src`, `gulp` 1, `tests` 1, `webpack.config.js`, `composer.json`, `AGENTS.md`, `README.md`; uikit theme 5), `plugins` 3 (`AGENTS.md`, `README.md`, `composer.json`), `libs` 3 (SCSS), `docker` 2 (`compose.main.yaml`, `uikit/smoke.mjs`), root 2 (`nx.json`, `AGENTS.md`). No `.php` and no lock file changed. By extension: md 41, scss 27, json 4, mjs 5, js 3, ts 1, yaml 1.
- Final `.oxfmtrc.json`: `singleQuote: true`, `sortPackageJson: false`; ignored `**/composer.lock`, the vendored countdown `assets/**`, `plugins/tests/fixtures/**`, Foundation `CHANGELOG.md`, and Foundation `gulpfile.mjs` (`PackageConfigTest` regex-matches `gulp.src(`). Override `singleQuote: false` for Foundation `*.yml|yaml`, `**/compose.yaml` and `**/compose.main.yaml` (`BuildConfigTest` requires double-quoted entries in `config-default.yml`; the Task 1 pattern `**/compose.*.yaml` matched nothing). No `composer.json` override (Composer keeps the 2-space indent; Oxfmt only expands the `lint` script arrays one entry per line, which is how Composer writes them), and `composer validate` passes for the uikit theme, Foundation and the plugins.
- `dist`, `vendor`, `node_modules`, `.nx`, `.worktrees` and `.superpowers` are skipped (decoy unformatted files in each were not reported). The 36 Markdown files keep every `### Task N:` heading and `- [ ]`/`- [x]` box (counts identical before and after in all 31 plan files).
- Baseline equality: the Foundation `dist` has the same 12-file list and identical sha256 for every file; `test:node` 8 pass, `nx run-many -t build test typecheck` 10 tasks green (Vitest 2 + 4), `plugins-tooling:lint` (140 files) and `test:unit` (176 tests, 481 assertions), the uikit PHPUnit (18 tests) and Foundation PHPUnit (41 tests) all equal the baselines.

### Task 3: Oxlint through `@nx/oxlint`

**Files:**

- Create: `.oxlintrc.json`
- Modify: `nx.json` (plugin entry `@nx/oxlint` with `targetName: "lint-js"`), root `package.json` (devDependencies `oxlint`, `@nx/oxlint`, `oxlint-tsgolint` all `"catalog:"`), `pnpm-lock.yaml`, and source files for findings

**Interfaces:**

- Produces: an inferred, cached `lint-js` target on every project that has lintable JS/TS files (`@hyper/ui`, `@hyper/uikit-tokens`, `@hyper/press-uikit`, `@hyper/press-foundation`, the example plugin if it has JS). No project's existing targets change.

- [x] **Step 1: Write the failing check:** `pnpm exec nx run @hyper/ui:lint-js` fails (no such target); a deliberately bad fixture file under `/tmp` linted with the new config exits non-zero.
- [x] **Step 2: Add** the config (rule set from Task 1), the plugin entry and dependencies. Capture `nx show project <name> --json` for every project before and after: only `lint-js` targets may appear.
- [x] **Step 3: Run** `pnpm exec nx run-many -t lint-js`; fix real findings in new code (libs, uikit theme `src`, `bin`, `vite.config.ts`, `docker/uikit/*.mjs` if in a project), and use overrides in `.oxlintrc.json` (with a one-line reason) for legacy Foundation and plugin JS.
- [x] **Step 4: Verify** the second run is a cache hit, a changed source file invalidates only its project, and `nx run plugins-tooling:lint` (composer) still works unchanged.
- [x] **Step 5: Commit** `build: lint JavaScript and TypeScript with Oxlint`.

Result (2026-10-16):

- `lint-js` was inferred on exactly four projects: `@hyper/ui`, `@hyper/uikit-tokens`, `@hyper/press-uikit`, `@hyper/press-foundation`. `plugins-tooling` and the individual plugins got none (their only JS is the ignored vendored countdown library), and the before/after `nx show project` diff over all 21 projects shows no changed existing target. `plugins-tooling:lint` (composer) is untouched and still passes.
- Config is `.oxlintrc.json` (no comments), so the reasons live here: the Foundation override keeps unmodified FoundationPress-era code (unused imports, escapes, an intentionally empty `app.js`) at warnings; the countdown ignore is the vendored library. New code is at `correctness` and `suspicious` error level.
- Type-aware (`oxlint --type-aware libs themes/hyperpress-uikit`) found one real issue, a floating promise in `themes/hyperpress-uikit/bin/serve.mjs`; fixed with a `.catch` at the call site plus `try/finally` so `running` is always reset. It is not enabled in the Nx target: the executor has no first-class option for it (unlisted options are forwarded as raw flags, so `--type-aware` works on the command line only) and Foundation has no tsconfig.
- Verified: second `run-many -t lint-js` is 4/4 cache hits; touching `libs/ui/src/index.ts` re-ran only `@hyper/ui` (3/4 hit); `fmt:check` and `install --frozen-lockfile` pass.

### Task 4: Stylelint for the Sass

**Files:**

- Create: `stylelint.config.mjs`
- Modify: root `package.json` (devDependencies `stylelint`, `stylelint-config-standard-scss`, `postcss-scss`, via the catalog), `libs/uikit-tokens/project.json` and `themes/hyperpress-uikit/project.json` (target `lint-css`), `pnpm-workspace.yaml`, `pnpm-lock.yaml`

**Interfaces:**

- Produces: cached `lint-css` targets running `stylelint "<projectRoot>/**/*.scss"` on `@hyper/uikit-tokens` and `@hyper/press-uikit` only (Foundation's legacy Sass is out of scope).

- [x] **Step 1: Write the failing check:** a scratch `.scss` file with a known violation (`/tmp`, linted with `--config stylelint.config.mjs`) exits non-zero; `nx run @hyper/uikit-tokens:lint-css` fails (no target).
- [x] **Step 2: Add** the config extending `stylelint-config-standard-scss` with the minimum rule relaxations for UIkit conventions (`@import` of `uikit/src/scss/...`, variable naming); each relaxation gets a one-line reason in the config.
- [x] **Step 3: Run** `pnpm exec nx run-many -t lint-css`; fix findings in our Sass; verify the cache hit on a second run.
- [x] **Step 4: Commit** `build: lint Sass with Stylelint`.

Result (2026-10-16):

- `stylelint.config.mjs` is just `extends: ['stylelint-config-standard-scss']` with no relaxed rules. Stylelint 17 ships no stylistic rules, so Oxfmt (formatter of record) cannot conflict, and the UIkit `@import 'uikit/src/scss/...'` paths and override-before-import order pass the standard rules unchanged. The reasons are in comments in the config.
- Four findings in our Sass were fixed instead of relaxing rules: removed a blank line between consecutive `@import`s (`at-rule-empty-line-before`), quoted `'Roboto'` in the font stack (`value-keyword-case`; same CSS), and dropped the `.scss` extension from the `@hyper/uikit-tokens/src/index` import in `main.scss` (`scss/load-partial-extension`; the uikit `vite-build` still succeeds).
- `lint-css` (cached, `nx:run-commands` from the workspace root, inputs: the project's `*.scss` plus `stylelint.config.mjs`, no outputs) exists on `@hyper/uikit-tokens` (3 files) and `@hyper/press-uikit` (1 file) only; a before/after `nx show project` shows no other target or project changed.
- RED check: a scratch file with `color:RED;;` and an empty block exits 2 (`value-keyword-case`, `block-no-empty`). Verified: second run 2/2 cache hits, touching one file re-ran only its own project, `fmt:check` and `install --frozen-lockfile` pass, `run-many -t lint-js build test typecheck` (14 tasks) passes.

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
