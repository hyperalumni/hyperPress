> **STATUS: ✅ COMPLETED** (2026-10-11). Stage 0 gate passed: history, layout, root pnpm install and build (deps and dist identical to baseline), PHP lint/tests in Docker, originals untouched, nothing pushed.

# Stage 0: Monorepo Restructure Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Turn `uikit/` into a pnpm-workspace monorepo that contains the full hyperPress (theme) history, the theme moved to `themes/hyperpress-foundation/`, the plugins repo merged under `plugins/`, all docs at the root, and root workspace files, with no behavior change.

**Architecture:** `uikit/` becomes a clone of the theme repo's `master` (original hashes kept). One rename commit moves the theme, the `filter-repo`-rewritten plugins history is rebased on top (linear history, no merge commit), and further commits add root workspace files and fix path references. Originals (`../theme`, `../plugins`) are never touched; nothing is pushed.

**Tech Stack:** git 2.53, git-filter-repo, Node 26.11.1 (nvm), pnpm 12.11.2, Docker Compose (PHP tooling only).

**Spec:** `docs/superpowers/specs/2026-10-07-uikit-nx-wordpress-design.md` (section "Stage 0"). Read it before starting.

## Global Constraints

- Work only inside `/home/cmcg/projects/hyperPress/uikit` and the scratch directory `/tmp/opencode/stage0`. Never modify `../theme`, `../plugins`, `../hyperalumni`, `../hyperonline`.
- Branch `master`. `origin` = `git@github.com:hyperalumni/hyperPress.git`. **Never push**, never set upstream tracking.
- Theme commit hashes stay unchanged. Plugins history is rewritten with exactly: `git filter-repo --to-subdirectory-filter plugins` then `git filter-repo --force --path-rename plugins/docs/:docs/`. The rewritten plugins history is then rebased onto `master` with `git rebase --onto master --root` (never merged), so the added commits contain no merge commit. Only `master` of each repo is imported.
- Node 26.11.1 and pnpm 12.11.2. Run Node tools with: `source ~/.nvm/nvm.sh && nvm use 26.11.1 >/dev/null && <command>`.
- pnpm only (never npm/yarn). PHP/Composer only in Docker (`docker compose -p <name> ...`), never on the host.
- Never read or print `.env*` files. Never `git add -A` / `git add .`. Commit with explicit paths: `git add <paths>` then `git commit -m "..." -- <paths>`. Exception: commits made from `git mv` renames, where the staged index is verified first and committed whole.
- Commit signing: `~/.gitconfig` signs commits with `B3A68A8C1678FCEC`, which is not in the keyring. By the user's decision, every `git commit`/`git rebase`/`git merge` in this plan is run as `git -c user.signingkey=4A9F22E376B1163C <command>` (signed, config untouched). Never edit git config.
- Docs live at root `docs/superpowers/{specs,plans,audit}/`. The theme's `docs/` stays in place at the repo root (it is not moved into `themes/hyperpress-foundation/`, but it is still part of the repo, with its history); the plugins repo's `docs/` is merged into the same root `docs/`.
- Root `pnpm-workspace.yaml` `packages` = `themes/*`, `plugins/*`. Theme's own `pnpm-workspace.yaml` is removed; its settings move to the root file unchanged.
- Tick a task's checkboxes in the commit that finishes that task. Tasks 1 and 2 finish before the plan is committed, so their boxes are ticked in the Task 3 commit; history-dependent checks that need the new commits to exist are collected in Task 7.
- Scratch variables live in `/tmp/opencode/stage0/vars.sh`; later tasks run `source /tmp/opencode/stage0/vars.sh`.

## Review Focus

1. **Dependency drift:** regenerating the lockfile at the root could resolve newer versions than the theme's lock. Task 5 compares resolved `name@version` against the baseline.
2. **Workspace glob picks up unexpected projects:** a `package.json` inside `plugins/*` would be installed as a workspace project. Task 5 asserts none are tracked and that pnpm sees exactly one project.
3. **Docker clashes with the existing stacks:** the plugins compose project is named `plugins` by default, the same as the original stack. Task 7 uses `-p stage0-*` names and tears them down with `down -v`.
4. **Build assumes its own cwd/root:** moving the theme could break relative paths or `config.yml` lookup. Task 5 builds via `pnpm --filter` from the root and diffs the output file list against a pristine baseline build.
5. **Stray artifacts committed or history lost:** `.idea/`, `node_modules/`, `dist/` must never be tracked; the move must keep `git log --follow` working. Each commit step checks `git status`, and Task 7 checks tracked paths and history counts.

---

### Task 1: Preflight and baseline

**Files:**
- Create (scratch only): `/tmp/opencode/stage0/{vars.sh,baseline-dist.txt,baseline-deps.txt,theme-baseline/,plugins-baseline/}`

**Interfaces:**
- Produces: `vars.sh` exporting `THEME_SHA`, `THEME_COUNT`, `THEME_DOCS`, `PLUGINS_SHA`, `PLUGINS_COUNT`, `PLUGINS_DOCS`; `baseline-dist.txt` (sorted `find dist -type f` of a pristine theme build); `baseline-deps.txt` (sorted `name@version` list of the theme's direct dependencies).

- [x] **Step 1: Check the toolchain**

Run: `source ~/.nvm/nvm.sh && nvm use 26.11.1 >/dev/null && node -v && pnpm -v && git filter-repo --version && docker compose version`
Expected: `v26.11.1`, `12.11.2`, a filter-repo version, a Compose version. **If `pnpm` is missing, stop and ask the user** how pnpm is provisioned (Node 26 does not bundle corepack); do not install it globally on your own.

- [x] **Step 2: Confirm the originals are clean and on master, then record the facts**

Run: `git -C ../theme status --short; git -C ../plugins status --short; git -C ../theme branch --show-current; git -C ../plugins branch --show-current`
Expected: no status lines; `master` twice. Then write `/tmp/opencode/stage0/vars.sh` with `export` lines for: `THEME_SHA=$(git -C ../theme rev-parse master)`, `THEME_COUNT=$(git -C ../theme rev-list --count master)`, `THEME_DOCS=$(git -C ../theme ls-files docs | wc -l)`, and the three `PLUGINS_*` equivalents (values written as literals). Expected today: `THEME_COUNT=1627`, `PLUGINS_COUNT=184`, `PLUGINS_DOCS=12`.

- [x] **Step 3: Build a pristine baseline of the theme**

Run: `git clone --no-local ../theme /tmp/opencode/stage0/theme-baseline`, then in it: `source ~/.nvm/nvm.sh && nvm use 26.11.1 >/dev/null && pnpm install && pnpm build`.
Expected: both succeed (if the baseline build fails, stop: the restructure must start from a green build). Save `find dist -type f | sort > /tmp/opencode/stage0/baseline-dist.txt`. Save direct dependencies with: `pnpm ls --depth 0 --json | node -e "const d=JSON.parse(require('fs').readFileSync(0,'utf8'));const o=[];for(const p of d)for(const k of ['dependencies','devDependencies'])for(const [n,v] of Object.entries(p[k]||{}))o.push(n+'@'+v.version);console.log(o.sort().join('\n'))" > /tmp/opencode/stage0/baseline-deps.txt`.

- [x] **Step 4: Clone a pristine plugins copy for later comparisons**

Run: `git clone --no-local ../plugins /tmp/opencode/stage0/plugins-baseline`
Expected: clone succeeds. (Used in Task 7 only if a Docker command fails, to tell whether the failure predates the restructure.)

---

### Task 2: Import the theme history and commit the spec and plan

**Files:**
- Modify (git state): `uikit/.git` (fetch, checkout, remote)
- Commit: `docs/superpowers/specs/2026-10-07-uikit-nx-wordpress-design.md`, `docs/superpowers/plans/2026-10-11-stage-0-monorepo-restructure.md`

**Interfaces:**
- Consumes: `THEME_SHA`, `THEME_COUNT` from `vars.sh`.
- Produces: `master` = theme history + two docs commits; `origin` configured, not pushed.

- [x] **Step 1: Check the starting state**

Run: `git rev-parse --verify HEAD; git status --short`
Expected: `rev-parse` fails (no commits); status shows only `?? .idea/` and `?? docs/`.

- [x] **Step 2: Import the theme's master**

Run: `git fetch ../theme master && git checkout -B master FETCH_HEAD`
Expected: success, and `git rev-parse HEAD` equals `$THEME_SHA`. If git refuses because an untracked file would be overwritten, stop and report which file.

- [x] **Step 3: Configure origin without pushing**

Run: `git remote add origin git@github.com:hyperalumni/hyperPress.git && git remote -v`
Expected: fetch and push URLs listed; `git branch -vv` shows no upstream.

- [x] **Step 4: Check only the two docs are untracked**

Run: `git status --short`
Expected: exactly `?? docs/superpowers/plans/2026-10-11-stage-0-monorepo-restructure.md` and `?? docs/superpowers/specs/2026-10-07-uikit-nx-wordpress-design.md` (`.idea/` is ignored by the theme's `.gitignore`).

- [x] **Step 5: Commit the spec, then the plan, each on its own**

Tick Task 1's boxes in the plan first. Then run `git add <spec> && git commit -m "docs(specs): uikit nx wordpress workspace design" -- <spec>` and `git add <plan> && git commit -m "docs(plans): stage 0 monorepo restructure" -- <plan>`.
Expected: two commits; `git rev-parse HEAD~2` equals `$THEME_SHA`; `git rev-list --count HEAD` equals `$THEME_COUNT + 2`.

---

### Task 3: Move the theme into `themes/hyperpress-foundation/`

**Files:**
- Rename: every tracked top-level entry except `docs` -> `themes/hyperpress-foundation/<entry>` (includes `.github`, `.gitignore`, `.nvmrc`, `.editorconfig`, `AGENTS.md`, `src`, `library`, `tests`, `pnpm-lock.yaml`, `pnpm-workspace.yaml`, etc.)
- Modify: the plan (checkboxes)

**Interfaces:**
- Consumes: Task 2 state.
- Produces: root contains only `docs/` and `themes/` as tracked entries.

- [x] **Step 1: Move**

Run: `mkdir -p themes/hyperpress-foundation && git ls-files -z | cut -z -d/ -f1 | sort -zu | grep -zv '^docs$' | xargs -0 -I{} git mv {} themes/hyperpress-foundation/{}`
Expected: no errors.

- [x] **Step 2: Verify the index holds only renames**

Run: `git diff --cached -M --name-status | cut -c1 | sort | uniq -c` and `git status --short | grep -v '^R '`
Expected: only `R` entries counted (no `A`/`D`/`M`); the second command prints only `?? .idea/` (the theme's `.gitignore` that hid it has moved; the root `.gitignore` arrives in Task 5).

- [x] **Step 3: Verify the layout**

Run: `git ls-files | grep -v '^docs/' | grep -vc '^themes/hyperpress-foundation/'`
Expected: `0`. Also `git ls-files | cut -d/ -f1 | sort -u` prints exactly `docs` and `themes`.

- [x] **Step 4: Commit**

Tick Tasks 2 and 3 boxes in the plan, `git add <plan>`, then `git commit -m "refactor: move theme into themes/hyperpress-foundation"` (whole index, verified in Step 2).
Expected: one commit; `git status --short` shows only `?? .idea/`.

---

### Task 4: Rebase the plugins history onto `master` under `plugins/`

**Files:**
- Create (scratch): `/tmp/opencode/stage0/plugins-rewritten/`
- Add via rebase: `plugins/**` (all plugin files and tooling) and `docs/superpowers/{audit,plans,specs}/*` from the plugins repo, as `PLUGINS_COUNT` replayed commits
- Modify: the plan (checkboxes), in one docs commit after the rebase

**Interfaces:**
- Consumes: `PLUGINS_COUNT`, `PLUGINS_DOCS`, `THEME_DOCS`.
- Produces: `plugins/` (no `plugins/docs`), plugin docs at root `docs/`, and `master` = the Task 3 commit followed by `PLUGINS_COUNT` linear plugin commits (no merge commit, each signed per Global Constraints), then one docs commit.

- [x] **Step 1: Rewrite a fresh clone**

Run in `/tmp/opencode/stage0`: `git clone --no-local /home/cmcg/projects/hyperPress/plugins plugins-rewritten`, then inside it: `git filter-repo --to-subdirectory-filter plugins` followed by `git filter-repo --force --path-rename plugins/docs/:docs/`.
Expected: both report parsed commits and a completed rewrite.

- [x] **Step 2: Verify the rewritten clone**

Run inside it: `git ls-files | grep -c '^docs/'`; `git ls-files | grep -c '^plugins/docs'`; `git ls-files | grep -vc -e '^plugins/' -e '^docs/'`; `git rev-list --count HEAD`
Expected: `$PLUGINS_DOCS` (12); `0`; `0`; `$PLUGINS_COUNT` (184).

- [x] **Step 3: Rebase onto `master` and fast-forward**

In `uikit/` (clean working tree; park any uncommitted work with `git stash push --include-untracked` first and restore it with `git stash pop --index`): `git fetch /tmp/opencode/stage0/plugins-rewritten master:refs/heads/plugins-rewritten`, then `git -c user.signingkey=4A9F22E376B1163C rebase --onto master --root plugins-rewritten`, then `git checkout master && git merge --ff-only plugins-rewritten && git branch -d plugins-rewritten`.
Expected: "Successfully rebased"; fast-forward; `git rev-list --count HEAD` equals `$THEME_COUNT + 3 + $PLUGINS_COUNT`; `git rev-list --merges --count HEAD~$PLUGINS_COUNT..HEAD` is `0`.

- [x] **Step 4: Verify the tree**

Run: `test ! -e plugins/docs && echo ok`; `git ls-files plugins | grep -o '^plugins/hyperpress-[a-z]*' | sort -u | wc -l`; `git ls-files docs | wc -l`; `git ls-files | cut -d/ -f1 | sort -u`
Expected: `ok`; `14`; `$THEME_DOCS + $PLUGINS_DOCS + 2`; exactly `docs`, `plugins`, `themes`.

- [x] **Step 5: Record the import in the plan**

Tick Task 4's boxes in the plan, `git add <plan>`, `git -c user.signingkey=4A9F22E376B1163C commit -m "docs(plans): record plugins import" -- <plan>`.
Expected: one commit; `git status --short` shows no changes to tracked files from this task.

---

### Task 5: Root workspace files

**Files:**
- Create: `package.json`, `.nvmrc`, `.gitignore`
- Move: `themes/hyperpress-foundation/pnpm-workspace.yaml` -> `pnpm-workspace.yaml` (then add `packages`); `themes/hyperpress-foundation/pnpm-lock.yaml` -> `pnpm-lock.yaml` (then regenerated by install)
- Modify: the plan (checkboxes)

**Interfaces:**
- Consumes: `baseline-dist.txt`, `baseline-deps.txt`.
- Produces: a root workspace in which `pnpm --filter @hyper/press build` works.

- [x] **Step 1: Check no plugin-level JS projects exist**

Run: `git ls-files 'plugins/*package.json'`
Expected: no output. If any file is listed, stop and report: it would become a workspace project.

- [x] **Step 2: Create root `package.json`**

Contents (values copied from the theme's `package.json`): `name` `@hyper/workspace`, `private` true, `scripts.preinstall` `npx only-allow pnpm`, `devEngines.packageManager` `{ name: pnpm, version: 12.11.2, onFail: download }`, `devEngines.runtime` `{ name: node, version: 26.11.1, onFail: error }`, `engines.node` `26.11.1`. No other scripts.

- [x] **Step 3: Move and extend the pnpm workspace file and lockfile**

Run: `git mv themes/hyperpress-foundation/pnpm-workspace.yaml pnpm-workspace.yaml` and `git mv themes/hyperpress-foundation/pnpm-lock.yaml pnpm-lock.yaml`. Add to the top of `pnpm-workspace.yaml` a `packages:` key listing `themes/*` and `plugins/*`; keep every existing key unchanged.

- [x] **Step 4: Create root `.nvmrc` and `.gitignore`**

`.nvmrc`: `26.11.1`. `.gitignore` lines: `node_modules/`, `dist/`, `.idea/`, `.env*`, `!.env.example`. (Do not copy the theme's 310-line file.)

- [x] **Step 5: Install and check the workspace**

Run: `source ~/.nvm/nvm.sh && nvm use 26.11.1 >/dev/null && pnpm install`, then `pnpm -r ls --depth -1`
Expected: install succeeds; the project list contains `@hyper/press` and no `hyperpress-*`/`plugins` entries.

- [x] **Step 6: Check for dependency drift**

Run: `pnpm --filter @hyper/press ls --depth 0 --json | node -e "<same one-liner as Task 1 Step 3>" | diff - /tmp/opencode/stage0/baseline-deps.txt`
Expected: no diff. If versions differ, stop and report the diff; do not commit.

- [x] **Step 7: Build from the root and compare output**

Run: `pnpm --filter @hyper/press build`, then `(cd themes/hyperpress-foundation && find dist -type f | sort) | diff - /tmp/opencode/stage0/baseline-dist.txt`
Expected: build succeeds; no diff.

- [x] **Step 8: Check nothing unwanted is tracked or pending, then commit**

Run: `git status --short`
Expected: only `package.json`, `.nvmrc`, `.gitignore`, `pnpm-workspace.yaml`, `pnpm-lock.yaml` (the moves appear as renames/deletes of the old theme paths); no `node_modules`/`dist`. Tick Task 5's boxes, then `git add package.json .nvmrc .gitignore pnpm-workspace.yaml pnpm-lock.yaml <plan>` and `git commit -m "build: add root pnpm workspace" -- package.json .nvmrc .gitignore pnpm-workspace.yaml pnpm-lock.yaml themes/hyperpress-foundation/pnpm-workspace.yaml themes/hyperpress-foundation/pnpm-lock.yaml <plan>`.

---

### Task 6: Root `AGENTS.md` and path fixes

**Files:**
- Create: `AGENTS.md`
- Modify: `themes/hyperpress-foundation/AGENTS.md`, `plugins/AGENTS.md`, `plugins/README.md`, and `docs/superpowers/plans/README.md` only if it is theme-specific
- Modify: the plan (checkboxes)

**Interfaces:**
- Consumes: Task 5 state.
- Produces: every `docs/superpowers`, `../theme`, `../plugins` reference in the three moved files resolves from its new location.

- [x] **Step 1: Write the failing path check**

Run: `git grep -nE 'docs/superpowers|\.\./(theme|plugins)' -- themes/hyperpress-foundation/AGENTS.md plugins/AGENTS.md plugins/README.md`
Expected now: lists the existing references (plugins `AGENTS.md` lines 6 and 13, `README.md` lines 35 and 50, theme `AGENTS.md` lines 26 and 42, plus any `../plugins` mention). These must each be rewritten in Step 2.

- [x] **Step 2: Fix the references**

In `plugins/AGENTS.md` and `plugins/README.md`, prefix each `docs/superpowers` with `../` (resolves to the root `docs/`). In `themes/hyperpress-foundation/AGENTS.md`, prefix each with `../../`, change `../plugins` to `../../plugins`, and change the `pnpm-workspace.yaml allowBuilds` note to refer to the root `pnpm-workspace.yaml`. Add one line near the top of both nested `AGENTS.md` files: it lives in the hyperPress monorepo and the root `AGENTS.md` applies. Make no other edits to them.
Re-run Step 1's command. Expected: every remaining match is `../docs/superpowers`, `../../docs/superpowers` or `../../plugins`.

- [x] **Step 3: Write root `AGENTS.md`**

Short and cross-cutting, with these sections: what the repo is and its layout (the tree from the spec); environment setup (`source ~/.nvm/nvm.sh && nvm use`, pnpm only, PHP tooling in Docker, never read `.env*`); git (single repo, commit from the root with explicit paths: `git add <paths>` then `git commit -m "..." -- <paths>`, never a bare `git commit`, never `git add -A`, no push without being asked, follow the style in `git log`); docs (root `docs/superpowers/`); "read `themes/hyperpress-foundation/AGENTS.md` or `plugins/AGENTS.md` before working in those areas"; "supersedes `../AGENTS.md`, which still describes `theme/` and `plugins/` as separate repos"; and an "Implementation plans" section (plans in `docs/superpowers/plans/`, specs in `docs/superpowers/specs/`, commit a plan on its own before coding, tick checkboxes with each task's commit, COMPLETED banner at the end).

- [x] **Step 4: Check the plans README**

Read `docs/superpowers/plans/README.md`. If it names the theme specifically, edit it to describe the whole workspace; otherwise leave it unchanged.

- [x] **Step 5: Commit**

Tick Task 6's boxes, then `git add AGENTS.md themes/hyperpress-foundation/AGENTS.md plugins/AGENTS.md plugins/README.md <plan>` (plus `docs/superpowers/plans/README.md` if edited) and `git commit -m "docs: add root AGENTS.md and fix moved doc paths" -- <same paths>`.
Expected: `git status --short` is empty.

---

### Task 7: Stage 0 gate and wrap-up

**Files:**
- Modify: this plan (COMPLETED banner, outcome notes, checkboxes)

**Interfaces:**
- Consumes: all `vars.sh` values; Docker.

- [x] **Step 1: History and layout checks**

Run:
- `git log --follow --format=%h -- themes/hyperpress-foundation/functions.php | wc -l` -> expected `(git -C ../theme log --follow --format=%h -- functions.php | wc -l) + 1`; and the last line of `git log --follow --format=%h -- themes/hyperpress-foundation/functions.php` equals the last line of `git -C ../theme log --format=%h -- functions.php`.
- `git log --oneline -- plugins/hyperpress-media | wc -l` -> `8`; `git log --oneline -- docs/superpowers/plans/2026-10-06-label-plugin-extraction.md | wc -l` -> `21`.
- `git rev-list --count HEAD` -> `$THEME_COUNT + $PLUGINS_COUNT + 7` (2 docs, move, spec update, plugins record, root files, AGENTS); `git rev-list --merges --count $THEME_SHA..HEAD` -> `0` (no merge commit added).
- `git ls-files | cut -d/ -f1 | sort -u` -> exactly `.gitignore .nvmrc AGENTS.md docs package.json plugins pnpm-lock.yaml pnpm-workspace.yaml themes`; `test ! -e plugins/docs`; `git ls-files | grep -E '(^|/)(node_modules|dist)/'` -> no output.

- [x] **Step 2: PHP tooling still works from the new locations (Docker, isolated project names)**

Run in `plugins/`: `docker compose -p stage0-plugins run --rm php composer lint`, then `composer test:unit`, then `composer test:integration` (same prefix), then `docker compose -p stage0-plugins down -v`. Run in `themes/hyperpress-foundation/`: `docker compose -p stage0-theme run --rm php composer lint` and `... composer test:unit`, then `docker compose -p stage0-theme down -v`.
Expected: each exits 0. If one fails, run the same command in the matching clone under `/tmp/opencode/stage0` (`plugins-baseline`, `theme-baseline`) with a `stage0-base` project name: a failure there too means it predates the restructure (report it, do not fix it here).

- [x] **Step 3: Originals untouched and nothing pushed**

Run: `git -C ../theme status --short; git -C ../plugins status --short; [ "$(git -C ../theme rev-parse master)" = "$THEME_SHA" ] && [ "$(git -C ../plugins rev-parse master)" = "$PLUGINS_SHA" ] && echo same; git remote -v; git branch -vv`
Expected: no status lines; `same`; only `origin`; no upstream shown.

- [x] **Step 4: Record the outcome and finish**

Add a `> **STATUS: ✅ COMPLETED** (2026-10-11). <what passed>` banner at the top of this plan and an "Outcome / execution notes" section listing the actual counts and any deviations. Tick Tasks 6 and 7. `git add <plan>` and `git commit -m "docs(plans): mark monorepo restructure completed" -- <plan>`. Then `rm -rf /tmp/opencode/stage0`.
Expected: `git status --short` is empty; do not push.

---

## Outcome / execution notes

- **Result:** `master` holds the full hyperPress theme history (1627 commits, original hashes, tip `7e9a30a` is an ancestor of `HEAD`) followed by the stage 0 commits: spec, plan, theme move (264 R100 renames), 184 signed plugin commits, spec update, plan record, root workspace files, root `AGENTS.md` and path fixes, a Docker project-name fix, and this banner. At the stage gate `git rev-list --count HEAD` was 1818 (`THEME_COUNT + PLUGINS_COUNT + 7`) with 0 merge commits added. Nothing was pushed; `origin` is configured and `../theme`, `../plugins` and the Docker stacks are untouched.
- **Plugins imported by rebase, not merge** (the user's request during Task 5): Task 4 and the spec were rewritten for it. The first merge-commit variant was discarded after confirming the rebased tree was identical (only the plan file differed); the plugins history had 0 merge commits, so nothing was flattened.
- **Commit signing:** `~/.gitconfig` names key `B3A68A8C1678FCEC`, which is not in the keyring. By the user's decision every commit and the 184 replayed commits were signed with `4A9F22E376B1163C` via `git -c user.signingkey=...`; git config was not changed.
- **pnpm:** it comes from the user's `.zshrc` (`PNPM_HOME`), so the plan's "pnpm missing" stop did not apply; the toolchain resolved to Node 26.11.1 and pnpm 12.11.2.
- **Plan check corrected:** Task 7 compared plain `git log` with `git log --follow`; plain log includes a merge commit (`027ed5f`) that `--follow` omits in both repos. The check now compares `--follow` with `--follow` (39 in the original, 40 here: 39 plus the move commit).
- **Docker:** `composer install` has to be run first because `vendor/` is git-ignored. All PHP suites passed from the new locations: plugins lint, 126 unit, 596 integration (4 skipped); theme lint, 38 unit.
- **Final review (fresh context, most capable tier):** no Critical findings. One item judged Important by effect was fixed: the monorepo's `plugins/` and the old `plugins` repo share the default Compose project name, so the docs now say to pass `-p hyperpress-plugins`.
- **Deferred follow-ups for the owner:** `.github/` is inert inside the theme folder; the theme keeps its own `.nvmrc` (same value as the root); the theme's `AGENTS.md` has older stale notes ("Node >=24", "only mozjpeg is enabled" while every `allowBuilds` entry is `false`); `../AGENTS.md` outside this repo is stale; the root `.gitignore` has no `vendor/` entry (nested ignores cover it); `uikit/` and `../theme` will diverge, so decide which is the source of truth before new commits land in either; pushing and repointing the Docker stacks remain separate decisions.
- **Corrections after completion (found when stray `theme/` and `themes/plugins/` directories appeared):** two tooling compose files kept the old two-repo sibling paths. `plugins/compose.yaml` mounted `../theme` and `themes/hyperpress-foundation/compose.yaml` mounted `../plugins`, so Docker created empty root-owned directories as mount sources during the final Docker checks, and the theme's integration suite had not been run. Both mounts now use the monorepo layout, the two directories were removed, and the theme integration suite (230 tests, 10 skipped by design) and the plugins suites pass. The earlier Docker project-name advice was also wrong: both compose files set explicit `name:` values (`hyperpress-plugins`, `hyperpress-theme`) identical to the standalone repos, so `-p hyperpress-plugins` isolated nothing. That advice was removed; the notes now say the project names and composer cache volume are shared with the standalone repos.
