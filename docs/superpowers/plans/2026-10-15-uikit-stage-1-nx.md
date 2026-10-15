# UIkit Stage 1: Add Nx Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add Nx 23.x to the pnpm workspace, prove it runs on Node 26, and register the existing Foundation theme and plugins as Nx projects, with no change to how either builds.

**Architecture:** `nx` is a root devDependency with a minimal `nx.json`. The Foundation theme (`@hyper/press`) is already a pnpm package, so Nx infers it from its `package.json`; a small `nx` block there adds cache outputs. The plugins have no `package.json`, so each gets a minimal `project.json`, and a `plugins-tooling` project wraps the Docker composer commands as uncached targets.

**Tech Stack:** Nx 23.3.0 (latest at planning time), Node 26.12.0, pnpm 12.12.1, Docker Compose (PHP tooling only).

**Spec:** `docs/superpowers/specs/2026-10-07-uikit-nx-wordpress-design.md` (Stage 1). Stages 0 and 0b are done (`2026-10-11-stage-0-monorepo-restructure.md`, `2026-10-11-docker-stacks-migration.md`). This is plan A of three: B covers stages 2-4 (uikit Docker site, Timber theme, Vite, UIkit tokens, HMR), C covers stages 5-7 (plugin consuming the shared lib, lint targets, write-up).

## Global Constraints

- Nx 23.x, exact-pinned; every `@nx/*` package, when added later, on the same 23.x version.
- Node 26.12.0 (bumped from 26.11.1 in Task 1; 26.12.0 is what is installed) and pnpm 12.12.1, pnpm only (`preinstall: npx only-allow pnpm`).
- Shell setup per command: `source ~/.nvm/nvm.sh && nvm use && export PATH="$HOME/.local/share/pnpm/bin:$PATH"` (run `nvm use` without a pipe, or it runs in a subshell).
- PHP tooling runs in Docker only, never composer on the host.
- Commit from the root with explicit pathspecs: `git add <paths>` then `git commit -m "..." -- <paths>`. Never `git add -A`. Do not read or print `.env*` files.
- No behavior change to the Foundation theme or the plugins. Oxfmt stays outside Nx.
- Completed plans keep their original wording (historical records), so Task 1 does not edit them.
- Work is on branch `feature/uikit-stage-1-nx`. Nothing is pushed.

## Review Focus

- Nx daemon or native binary fails on Node 26: fall back to `NX_DAEMON=false`, record the cause, and keep the rest of the plan working.
- Nx discovers duplicate projects in `.worktrees/` (git-ignored full repo copies with their own `package.json` and `project.json`): must not appear in `nx show projects`.
- Cache restores a wrong or partial `dist` (gulp-rev hashed names, `dist` is git-ignored): the restored file list must equal the built one.
- `minimumReleaseAgeStrict` blocks `nx@23.3.0` as too new: expected response is a documented `minimumReleaseAgeExclude` entry, not disabling the setting.
- Docker-backed targets run with Docker stopped or `vendor/` missing: must fail with a clear message and never be cached.

---

### Task 1: Bump the Node pin and correct the spec

**Files:**
- Modify: `.nvmrc`, `themes/hyperpress-foundation/.nvmrc` (`26.11.1` -> `26.12.0`)
- Modify: `package.json`, `themes/hyperpress-foundation/package.json` (`devEngines.runtime.version`, `engines.node`)
- Modify: `AGENTS.md` (Node version in Environment; add the pnpm PATH line)
- Modify: `docs/superpowers/specs/2026-10-07-uikit-nx-wordpress-design.md`

**Interfaces:**
- Produces: Node 26.12.0 as the single pinned runtime. Later tasks assume it.

- [x] **Step 1: Record the pre-change baseline.** Under the shell setup, with the old pin still in place and using Node 26.12.0 via `nvm use 26.12.0`, run `pnpm install` then `pnpm --filter @hyper/press build`. Save `find themes/hyperpress-foundation/dist -type f | sort` to `/tmp/opencode/stage1/dist-baseline.txt` (create the directory). Expected: both commands exit 0 and the file list is non-empty.
- [x] **Step 2: Bump the pin** in the four files above and in `AGENTS.md` (Node 26.12.0; add `export PATH="$HOME/.local/share/pnpm/bin:$PATH"` to the Environment shell line). Do not edit completed plans.
- [x] **Step 3: Correct the spec:** pnpm 12.12.1 (was 12.11.2), Node 26.12.0, 16 plugins (was 14, in the Plugins row and the Docker mounts text), stage 0 and 0b marked done in the Staged rollout, Nx pinned to 23.3.0 in the Decisions table.
- [x] **Step 4: Verify.** Run `nvm use` (reads `.nvmrc`), `node -v` -> `v26.12.0`; `pnpm install` exits 0 with no engine error; `pnpm --filter @hyper/press build` exits 0 and `find ... | sort` equals the baseline file.
- [x] **Step 5: Commit** `chore: pin Node 26.12.0 and correct the uikit spec` over exactly the files above.

### Task 2: Install Nx and add `nx.json`

**Files:**
- Modify: `package.json` (devDependency `"nx": "23.3.0"`, exact), `pnpm-lock.yaml`, `.gitignore` (add `.nx/`)
- Create: `nx.json`
- Modify (only if install is blocked by release age): `pnpm-workspace.yaml` (add the `nx` packages the error names to `minimumReleaseAgeExclude`)

**Interfaces:**
- Produces: the `nx` binary at the root (`pnpm exec nx`) and `nx.json` with `targetDefaults` for `build` and `test:node` (`cache: true`, `inputs: ["default", "^default"]`) plus `namedInputs.default = ["{projectRoot}/**/*", "!{projectRoot}/dist/**/*", "!{projectRoot}/vendor/**/*", "!{projectRoot}/node_modules/**/*"]`.

- [ ] **Step 1: Install.** `pnpm add -D -w --save-exact nx@23.3.0`. If pnpm rejects it as younger than the release-age window, add the named packages to `minimumReleaseAgeExclude`, note the reason in a comment-free commit message, and retry. Expected: exit 0.
- [ ] **Step 2: Create `nx.json`** as specified in Interfaces, with `"$schema": "./node_modules/nx/schemas/nx-schema.json"` and `useDaemonProcess` left default.
- [ ] **Step 3: Verify the toolchain on Node 26.** `pnpm exec nx --version` prints `23.3.0`; `pnpm exec nx show projects` prints `@hyper/press` and nothing from `.worktrees/`. If the daemon or native binding errors, rerun with `NX_DAEMON=false`; if that works, add `"useDaemonProcess": false` to `nx.json` and record the error text in this plan's execution notes. If neither works, stop and report: the Node 26 risk in the spec has materialised.
- [ ] **Step 4: Verify the lockfile** has no unrelated changes: `git diff --stat pnpm-lock.yaml` shows only additions for `nx` and its dependencies.
- [ ] **Step 5: Commit** `build: add Nx 23.3.0` over `package.json pnpm-lock.yaml pnpm-workspace.yaml nx.json .gitignore` (drop `pnpm-workspace.yaml` if unchanged).

### Task 3: Register the Foundation theme

**Files:**
- Modify: `themes/hyperpress-foundation/package.json` (add an `nx` block)

**Interfaces:**
- Consumes: `nx.json` target defaults from Task 2.
- Produces: project `@hyper/press` with cached targets `build` (outputs `{projectRoot}/dist`) and `test:node`. Other scripts (`dev`, `start`, `phpcs`, `phpcbf`, `package`, `audit`) stay uncached.

- [ ] **Step 1: Add the `nx` block** to the theme's `package.json`: `targets.build.outputs = ["{projectRoot}/dist"]`, `targets.build.cache = true`, `targets["test:node"].cache = true`; set `cache: false` explicitly on `phpcs`, `phpcbf`, `package`, `dev` and `start`.
- [ ] **Step 2: Verify the build matches the baseline.** `rm -rf themes/hyperpress-foundation/dist`, then `pnpm exec nx build @hyper/press`. Expected: success, and the sorted `dist` file list equals `/tmp/opencode/stage1/dist-baseline.txt`.
- [ ] **Step 3: Verify the test target.** `pnpm exec nx run @hyper/press:test:node` exits 0 and runs the same tests as `pnpm --filter @hyper/press test:node`.
- [ ] **Step 4: Verify config.** `pnpm exec nx show project @hyper/press --json` lists `build` with `cache: true` and the `dist` output, and `phpcs` with `cache: false`.
- [ ] **Step 5: Commit** `build(theme): register the Foundation theme as an Nx project` over the theme `package.json`.

### Task 4: Register the plugins and their tooling

**Files:**
- Create: `plugins/hyperpress-<name>/project.json` for each of the 16 plugins (`ls -d plugins/hyperpress-*`)
- Create: `plugins/project.json` (project `plugins-tooling`)

**Interfaces:**
- Produces: 16 projects named `hyperpress-<name>` (`projectType: "library"`, tags `["type:plugin"]`, no targets) and `plugins-tooling` with uncached targets `lint`, `test:unit`, `test:integration`, each an `nx:run-commands` with `cwd: "plugins"` running `docker compose run --rm php composer <script>`. Plan B and C add per-plugin targets and dependency edges to these projects.

- [ ] **Step 1: Read `plugins/composer.json` scripts and `plugins/AGENTS.md`** and confirm the script names `lint`, `test:unit`, `test:integration` exist; use the real names if they differ. Pin them in the target definitions.
- [ ] **Step 2: Create the 17 `project.json` files** (minimal; `$schema` `../../node_modules/nx/schemas/project-schema.json` for plugins, `../node_modules/...` for `plugins/project.json`).
- [ ] **Step 3: Verify nesting.** `pnpm exec nx show projects` lists `@hyper/press`, `plugins-tooling` and exactly 16 `hyperpress-*` names. If Nx rejects `plugins-tooling` because its root contains the plugin roots, move the tooling project to `plugins/bin/project.json` (`cwd` unchanged) and rerun; record which layout worked.
- [ ] **Step 4: Verify the tooling target** (Docker running, `vendor/` installed via `docker compose run --rm php composer install` from `plugins/`): `pnpm exec nx run plugins-tooling:lint` exits with the same status and output as running `composer lint` directly, and a second run is not served from cache (no `[local cache]` line).
- [ ] **Step 5: Verify the failure mode.** With Docker stopped (or `docker compose` pointed at a missing file via a temporary `--file` argument), the target exits non-zero with Docker's own error and is not cached. Restore the environment afterwards.
- [ ] **Step 6: Commit** `build(plugins): register plugins and composer tooling as Nx projects` over `plugins/project.json plugins/hyperpress-*/project.json`.

### Task 5: Prove graph and cache, then close out

**Files:**
- Modify: `AGENTS.md` (Nx commands: `pnpm exec nx show projects`, `nx build @hyper/press`, `nx run plugins-tooling:lint`; that Nx caches the theme build only)
- Modify: `docs/superpowers/specs/2026-10-07-uikit-nx-wordpress-design.md` (Staged rollout: stage 1 done, with the Node 26 result)
- Modify: this plan (tick boxes, COMPLETED banner, execution notes)

- [ ] **Step 1: Cache hit.** Run `pnpm exec nx build @hyper/press` twice. Expected: the second run prints `existing outputs match the cache` or `[local cache]` and finishes faster.
- [ ] **Step 2: Cache restore is faithful.** `rm -rf themes/hyperpress-foundation/dist`, run `pnpm exec nx build @hyper/press` again (cache hit), and confirm the sorted `dist` list equals the baseline file.
- [ ] **Step 3: Graph.** `pnpm exec nx graph --file=/tmp/opencode/stage1/graph.json` writes a graph whose nodes are exactly `@hyper/press`, `plugins-tooling` and the 16 plugins; no `.worktrees` paths appear.
- [ ] **Step 4: Isolation.** `git status --short` shows no stray files (`.nx/` is ignored); `pnpm install --frozen-lockfile` exits 0.
- [ ] **Step 5: Gate from the spec.** `pnpm --filter @hyper/press build` and the plugins' `composer lint` / `composer test:unit` (via Docker) still pass directly, without Nx.
- [ ] **Step 6: Docs and banner.** Make the three edits above, add `> **STATUS: ✅ COMPLETED** (<date>). <what passed>` and an "Outcome / execution notes" section (Nx version, daemon result, release-age exclusions, plugin layout chosen).
- [ ] **Step 7: Commit** `docs: describe Nx commands and mark stage 1 completed` over `AGENTS.md`, the spec and this plan.
