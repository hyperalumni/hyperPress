> **STATUS: ✅ COMPLETED** (2026-10-11). Both sites start from docker/ with their existing volumes; configs identical to the old stacks; theme and 14 plugins mounted; HTTP 200; owner confirmed both sites and admin login; old directories unmodified; nothing pushed.

# Migrate the hyperalumni and hyperonline Docker stacks Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the two identical stacks in `hyperPress/hyperalumni/` and `hyperPress/hyperonline/` with one shared compose file plus thin per-site wrappers in `uikit/docker/`, keeping the existing project and volume names, so existing local data is reused when it is still there (the owner accepts recreating it).

**Architecture:** `docker/compose.main.yaml` is the old file with host paths rewritten and the theme mount parameterised. Each site is a wrapper (`docker/<site>/compose.yaml`) that sets the project `name:` and `include`s the shared file with `project_directory: .`. Six env files are shared in `docker/`; only `.env.local` is per site. Old directories stay untouched.

**Tech Stack:** Docker Compose 5.5.1 (`include`, `--no-env-resolution`), Node 26.11.1 (scratch compare script), git.

**Spec:** `docs/superpowers/specs/2026-10-07-uikit-nx-wordpress-design.md` (sections "Docker stack" and "Stage 0b"). Read it before starting.

## Global Constraints

- Work in `/home/cmcg/projects/hyperPress/uikit` (new files under `docker/`; edits to `.gitignore`, root `AGENTS.md`, the spec and this plan) and the scratch directory `/tmp/opencode/stage0b`. Never modify, move or delete `../hyperalumni`, `../hyperonline`, `../theme`, `../plugins` or `../AGENTS.md`.
- Never read or print env file values. The shell tool rejects any command containing the literal text `.env`: use globs (`'docker/*.example'`), `[.]env` inside regexes, and `docker compose config --no-env-resolution` (prints env file paths, never values). Real env files are copied by the owner in the owner's terminal; the agent checks key names and emptiness with EnvSitter through the `execute` tool, inside this project only.
- Unchanged from the old stacks: project names (`hyperalumni`, `hyperonline`), named volumes (`db`, `wp`), service names, container-side paths (`/var/www/html/wp-content/themes/hyperPress`, `/var/www/html/wp-content/plugins/hyperpress-<name>`), profile `local`, `restart: unless-stopped`, `pull_policy: weekly`, the healthcheck. Only host paths change.
- These are local development stacks and their data is disposable (the owner confirmed). Do not delete volumes unless needed: if a site will not start against its existing volumes, `docker compose --profile local down -v` for that site only is allowed after a ledger note. Never run `docker system prune`. After each site's checks, leave its containers stopped (`docker compose --profile local stop`), as they were at the start.
- Use `source ~/.nvm/nvm.sh && nvm use 26.11.1 >/dev/null` before Node. Commit signing: every commit is `git -c user.signingkey=4A9F22E376B1163C commit ...` with explicit paths (`git add <paths>` then `git commit -m "..." -- <paths>`); never `git add -A`; never change git config; no push.
- Tick a task's checkboxes in the commit that finishes it. Tasks 1 and 2 finish before the first code commit, so their boxes are ticked in the Task 3 commit; Tasks 4 and 5 make no commit, so theirs are ticked in the Task 6 commit.
- Progress ledger: `/tmp/opencode/stage0b/progress.md` (outside the repo). Scratch variables: `/tmp/opencode/stage0b/vars.sh`.

## Review Focus

1. **Volumes not reattached:** a wrong project or volume name would silently start an empty WordPress. Task 3's comparison covers `name` and volume names; Tasks 4 and 5 diff `docker volume ls` before and after.
2. **Wrong host paths for the theme or plugins:** the container would silently fall back to a default theme or lose plugins. Task 3's comparison normalises paths and must match the old config; Tasks 4 and 5 check files inside the running container.
3. **Secrets leaking:** real env files tracked, or values copied into templates. Task 2 checks the secret-bearing templates are empty; Task 3 asserts only `*.example`, `*.yaml` and `*.ini` are tracked under `docker/`.
4. **Third-party plugins in the `wp` volume:** plugins such as `meta-box-aio` are not mounted from the repo, so a recreated volume loses them and dependent hyperpress plugins will not activate. Tasks 4 and 5 record in the ledger whether the volumes were reused or recreated.
5. **Port collisions between the sites:** both can be up at once. Tasks 4 and 5 record the published ports and Task 5 asserts they differ from the other site's.

---

### Task 1: Commit spec and plan, preflight, save old configs

**Files:**
- Commit: `docs/superpowers/specs/2026-10-07-uikit-nx-wordpress-design.md`, `docs/superpowers/plans/2026-10-11-docker-stacks-migration.md`
- Create (scratch): `/tmp/opencode/stage0b/{vars.sh,old-sha256.txt,old-cfg-hyperalumni.json,old-cfg-hyperonline.json,progress.md}`

**Interfaces:**
- Produces: `old-cfg-<site>.json` (old config without env values); `old-sha256.txt`.

- [x] **Step 1: Commit the spec, then the plan, each on its own**

`git add <spec> && git -c user.signingkey=4A9F22E376B1163C commit -m "docs(specs): migrate docker stacks into the monorepo" -- <spec>`, then the same for the plan with `-m "docs(plans): docker stacks migration"`.
Expected: two commits; `git status --short` is empty.

- [x] **Step 2: Preflight**

Run `docker compose version`, then for each site `cd /home/cmcg/projects/hyperPress/<site> && docker compose --profile local config --services`, then `docker ps --filter name=hyperalumni --filter name=hyperonline -q | wc -l`.
Expected: Compose 2.20 or newer; services `db`, `phpmyadmin`, `wordpress` for both; `0` running containers (if not 0, stop and ask the user). Write `sha256sum` of `<site>/compose.yaml`, `<site>/compose.main.yaml`, `<site>/php/uploads.ini` for both sites (absolute paths) to `old-sha256.txt`.

- [x] **Step 3: Save the old configs without env values**

For each site: `docker compose --profile local config --no-env-resolution --format json > /tmp/opencode/stage0b/old-cfg-<site>.json`.
Expected: exit 0; `grep -c -E 'PASSWORD|environment' <file>` prints `0` (no values were resolved).

---

### Task 2: Env files (owner-run copy) and templates

**Files:**
- Create (agent): `docker/php/uploads.ini`, directories `docker/hyperalumni/`, `docker/hyperonline/`
- Create (owner): `docker/.env`, `docker/.env.db`, `docker/.env.mariadb`, `docker/.env.phpmyadmin`, `docker/.env.wordpress`, `docker/.env.versions`, `docker/hyperalumni/.env.local`, `docker/hyperonline/.env.local`, and a `.example` twin of each of the eight

**Interfaces:**
- Produces: the eight real env files (git-ignored after Task 3) and eight `.example` templates.

- [x] **Step 1: Create directories and copy `uploads.ini`**

`mkdir -p docker/php docker/hyperalumni docker/hyperonline && cp ../hyperalumni/php/uploads.ini docker/php/uploads.ini && cmp docker/php/uploads.ini ../hyperonline/php/uploads.ini`
Expected: no output from `cmp` (identical).

- [x] **Step 2: Owner runs the copy block (STOP and wait)**

Print exactly this block for the user to run in their own terminal, then stop until they confirm it finished:

```
cd /home/cmcg/projects/hyperPress
for f in .env .env.db .env.mariadb .env.phpmyadmin .env.wordpress .env.versions; do cmp hyperalumni/$f hyperonline/$f && cp -p hyperalumni/$f uikit/docker/$f; done
cp -p hyperalumni/.env.local uikit/docker/hyperalumni/.env.local
cp -p hyperonline/.env.local uikit/docker/hyperonline/.env.local
cd uikit/docker
for f in .env.db .env.mariadb .env.wordpress; do sed -E 's/^([A-Za-z_][A-Za-z0-9_]*)=.*/\1=/' $f > $f.example; done
for f in .env .env.versions; do cp $f $f.example; done
: > .env.phpmyadmin.example
cp hyperalumni/.env.local hyperalumni/.env.local.example
cp hyperonline/.env.local hyperonline/.env.local.example
```

Expected: the `cmp` loop prints nothing (the six shared files are byte-identical across the sites). If `cmp` reports a difference, stop: the shared-file design does not hold and the spec needs a per-site copy for that file.

- [x] **Step 3: Verify key names and empty templates with EnvSitter**

With the `execute` tool, call `tools.envsitter_keys({ filePath })` for each real file and compare the names:
`docker/.env`: `NODE_OPTIONS`; `docker/.env.db`: `HYPER_DB_NAME`, `HYPER_DB_PASSWORD`, `HYPER_DB_ROOT_PASSWORD`, `HYPER_DB_USER`; `docker/.env.mariadb`: `MARIADB_DATABASE`, `MARIADB_PASSWORD`, `MARIADB_ROOT_PASSWORD`, `MARIADB_USER`; `docker/.env.phpmyadmin`: none; `docker/.env.wordpress`: `WORDPRESS_DB_HOST`, `WORDPRESS_DB_NAME`, `WORDPRESS_DB_PASSWORD`, `WORDPRESS_DB_USER`; `docker/.env.versions`: `MARIADB_VERSION`, `PHPMYADMIN_VERSION`, `WORDPRESS_VERSION`; each `docker/<site>/.env.local`: `COMPOSE_BAKE`, `COMPOSE_PROFILES`, `PHPMYADMIN_EXTERNAL_PORT`, `PHPMYADMIN_INTERNAL_PORT`, `WORDPRESS_EXTERNAL_PORT`, `WORDPRESS_INTERNAL_PORT`.
Then for `docker/.env.db.example`, `docker/.env.mariadb.example` and `docker/.env.wordpress.example` call `tools.envsitter_match({ filePath, op: "is_empty", allKeys: true })`.
Expected: every name list matches exactly, and the three secret templates report all keys empty. Never print values.

---

### Task 3: Shared compose file, site wrappers, comparison, commit

**Files:**
- Create: `docker/compose.main.yaml`, `docker/hyperalumni/compose.yaml`, `docker/hyperonline/compose.yaml`
- Modify: `.gitignore` (replace `!.env.example` with `!.env*.example`)
- Create (scratch): `/tmp/opencode/stage0b/compare.js`

**Interfaces:**
- Consumes: `old-cfg-<site>.json`, the Task 2 files.
- Produces: `node compare.js <site>` exits `0` when the new config equals the old one after path normalisation, otherwise prints the differing JSON paths and exits `1`.

- [x] **Step 1: Write the comparison and watch it fail**

Create `compare.js <site>`: load `old-cfg-<site>.json`; run `docker compose --profile local config --no-env-resolution --format json` in `/home/cmcg/projects/hyperPress/uikit/docker/<site>` and parse it; replace host path prefixes in every string value, longest prefix first, so both sides use the same tokens. Old: `/home/cmcg/projects/hyperPress/theme` -> `<theme>`, `/home/cmcg/projects/hyperPress/plugins` -> `<plugins>`, `/home/cmcg/projects/hyperPress/<site>/php` -> `<php>`, `/home/cmcg/projects/hyperPress/<site>` -> `<envdir>`. New: `/home/cmcg/projects/hyperPress/uikit/themes/hyperpress-foundation` -> `<theme>`, `/home/cmcg/projects/hyperPress/uikit/plugins` -> `<plugins>`, `/home/cmcg/projects/hyperPress/uikit/docker/php` -> `<php>`, `/home/cmcg/projects/hyperPress/uikit/docker` -> `<envdir>`. Deep-compare ignoring key order.
Run `node compare.js hyperalumni`. Expected now: FAIL (no wrapper exists yet).

- [x] **Step 2: Create `docker/compose.main.yaml`**

Copy `/home/cmcg/projects/hyperPress/hyperalumni/compose.main.yaml` and make exactly these changes, nothing else: (1) every `env_file` entry gets a `../` prefix (`.env.db` -> `../.env.db`, and likewise `.env.wordpress`, `.env.mariadb`, `.env.phpmyadmin`); (2) `./php/uploads.ini` -> `../php/uploads.ini`; (3) the line `../theme/:/var/www/html/wp-content/themes/hyperPress` becomes `${THEME_SRC:-../../themes/hyperpress-foundation}:/var/www/html/wp-content/themes/${THEME_SLUG:-hyperPress}`; (4) every `../plugins/hyperpress-<name>/` source becomes `../../plugins/hyperpress-<name>/` (the 14 mounts stay separate).

- [x] **Step 3: Create the two wrappers**

`docker/hyperalumni/compose.yaml`:

```yaml
name: hyperalumni
include:
  - path: ../compose.main.yaml
    project_directory: .
    env_file:
      - ../.env
      - .env.local
      - ../.env.versions
```

`docker/hyperonline/compose.yaml` is identical except `name: hyperonline`.

- [x] **Step 4: Update `.gitignore`**

Replace the line `!.env.example` with `!.env*.example` (edit tool). Expected: the root file still lists `node_modules/`, `dist/`, `.idea/`, `.env*`.

- [x] **Step 5: Run the comparison for both sites**

`node /tmp/opencode/stage0b/compare.js hyperalumni` and `... hyperonline`.
Expected: both exit `0` with no differences. Any difference means Step 2 or 3 deviates from the old config: fix the file, not the script.

- [x] **Step 6: Check ignores and templates**

Run `git status --short -uall docker | wc -l` and `git status --short --ignored docker | grep -c '^!!'`.
Expected: `12` untracked entries (compose.main.yaml, php/uploads.ini, two wrapper compose.yaml, eight `.example` templates) and `8` ignored entries (the real env files).

- [x] **Step 7: Commit**

Tick Tasks 1 and 2 and this task's boxes in the plan. `git add .gitignore docker/compose.main.yaml docker/php/uploads.ini docker/hyperalumni/compose.yaml docker/hyperonline/compose.yaml 'docker/*.example' 'docker/hyperalumni/*.example' 'docker/hyperonline/*.example' <plan>`, then commit with the same paths: `-m "feat(docker): shared compose and per-site wrappers for hyperalumni and hyperonline"`.
Expected: one commit; `git ls-files docker | wc -l` is `12`; `git ls-files docker | grep -c -v -E '(example|yaml|ini)$'` is `0`; `git status --short` is empty.

---

### Task 4: Cut over `hyperalumni`

**Files:** none changed in the repo; the site's containers are recreated from `docker/hyperalumni/`.

**Interfaces:**
- Consumes: the saved old configs and `old-sha256.txt` from Task 1; the Task 3 files.
- Produces: `hyperalumni` verified running from the monorepo, then stopped; the published ports recorded in the ledger.

- [x] **Step 1: Record services, ports and volumes**

In `docker/hyperalumni`: `docker compose --profile local config --services`, `docker compose --profile local config --no-env-resolution | grep published`, and `docker volume ls -q | sort > /tmp/opencode/stage0b/vols-before.txt`.
Expected: services `db`, `phpmyadmin`, `wordpress`; published ports `3080` (WordPress) and `3090` (phpMyAdmin), matching the old stack.

- [x] **Step 2: Bring the site up**

`docker compose --profile local up -d --wait` in `docker/hyperalumni`.
Expected: `wordpress`, `db` and `phpmyadmin` are recreated and running; `db` is healthy. If anything fails, roll back: `docker compose --profile local down` (never `-v`) here, then `up -d` from `/home/cmcg/projects/hyperPress/hyperalumni`; no volume is removed.

- [x] **Step 3: Volume reuse**

Run `diff /tmp/opencode/stage0b/vols-before.txt <(docker volume ls -q | sort)`.
Expected: empty (the site reused its volumes). If new `hyperalumni_*` volumes appear, the site started fresh: record it in the ledger (acceptable; WordPress shows the installer and third-party plugins such as `meta-box-aio` must be reinstalled).

- [x] **Step 4: Mounts inside the container**

`docker compose --profile local exec -T wordpress sh -c 'test -f /var/www/html/wp-content/themes/hyperPress/functions.php || echo MISSING theme; for p in award competition countdown label media newsletter program robot season shortcode social sponsor student utils; do test -f /var/www/html/wp-content/plugins/hyperpress-$p/hyperpress-$p.php || echo MISSING $p; done; echo checked'`
Expected: only `checked` is printed.

- [x] **Step 5: HTTP**

`curl -sL -o /dev/null -w '%{http_code}\n' http://localhost:3080/` and the same for `http://localhost:3090/`.
Expected: `200` for both. A redirect that ends elsewhere is a finding: report where it went.

- [x] **Step 6: Owner confirms login (STOP and wait)**

Ask the user to open `http://localhost:3080/` and `http://localhost:3080/wp-admin` and confirm: for a reused volume, the site renders with the `hyperPress` theme and the admin login works; for a fresh volume, the WordPress installer appears. After they reply, run `docker compose --profile local stop`.
Expected: the user confirms; `docker ps -q --filter name=hyperalumni | wc -l` is `0`.

---

### Task 5: Cut over `hyperonline`

**Files:** none changed in the repo.

**Interfaces:**
- Consumes: as Task 4 with `hyperonline` in place of `hyperalumni` (directory `docker/hyperonline`, volumes `hyperonline_db` / `hyperonline_wp`).
- Produces: `hyperonline` verified, then stopped.

- [x] **Step 1: Record services, ports and volumes, and check the ports differ**

As Task 4 Step 1 for `docker/hyperonline`. Expected: the same three services; its published WordPress and phpMyAdmin ports are both different from `3080` and `3090`.

- [x] **Step 2: Bring the site up**

As Task 4 Step 2 for `docker/hyperonline` (rollback uses `/home/cmcg/projects/hyperPress/hyperonline`).

- [x] **Step 3: Volume reuse**

As Task 4 Step 3 for `hyperonline` (volumes `hyperonline_*`).

- [x] **Step 4: Mounts inside the container**

As Task 4 Step 4. Expected: only `checked`.

- [x] **Step 5: HTTP**

As Task 4 Step 5 using this site's published ports. Expected: `200` for both.

- [x] **Step 6: Owner confirms login (STOP and wait), then stop the site**

As Task 4 Step 6 for this site's URL. Expected: the user confirms; no `hyperonline` container is running afterwards.

---

### Task 6: Docs, final gate, wrap-up

**Files:**
- Modify: `AGENTS.md` (root)
- Modify: this plan (COMPLETED banner, outcome notes, checkboxes)

**Interfaces:**
- Consumes: `old-sha256.txt`.

- [x] **Step 1: Write the failing check**

`grep -c 'docker/hyperalumni' AGENTS.md`
Expected now: `0`.

- [x] **Step 2: Update the root `AGENTS.md`**

Add a short "Local WordPress stacks" section: start a site with `cd docker/<hyperalumni|hyperonline> && docker compose --profile local up -d`; shared env files live in `docker/` and only `.env.local` is per site; `*.example` templates are committed and the real files are git-ignored; the project names keep the existing volumes; `docker compose down -v` deletes a site's database and WordPress files (fine for local development, but do it on purpose); design in the spec's "Docker stack". In the layout list, change the "Planned, not built yet" line so `docker/` is no longer listed as planned.
Re-run Step 1's command. Expected now: `1` or more.

- [x] **Step 3: Final gate**

Run `sha256sum -c /tmp/opencode/stage0b/old-sha256.txt`, `git ls-files docker | grep -c -v -E '(example|yaml|ini)$'`, `docker ps -q --filter name=hyperalumni --filter name=hyperonline | wc -l`, `git -C ../theme status --short`, `git -C ../plugins status --short`.
Expected: all six checksum lines `OK` (old stacks unmodified); `0`; `0`; empty; empty.

- [x] **Step 4: Record the outcome and commit**

Add a `> **STATUS: ✅ COMPLETED** (<date>). <what passed>` banner at the top of this plan and an "Outcome / execution notes" section (ports, whether each site reused or recreated its volumes, deviations, and the suggested `../AGENTS.md` edits: uikit is now a git repo, the stacks live in `uikit/docker/`, theme and plugins paths changed). Tick Tasks 4-6. `git add AGENTS.md <plan>` and commit `-m "docs: describe docker stacks and mark migration plan completed" -- AGENTS.md <plan>`. Then `rm -rf /tmp/opencode/stage0b`.
Expected: `git status --short` is empty. Do not push. Do not edit `../AGENTS.md`; list the suggested edits in the final message.

---

## Outcome / execution notes

- **Result:** `docker/` now holds one shared `compose.main.yaml`, `php/uploads.ini`, two thin wrappers (`docker/hyperalumni/`, `docker/hyperonline/`), six shared env files, and a per-site `.env.local`, with eight committed `.example` templates. For both sites the new config is identical to the old stack's config after host paths are normalised (project names, ports, volumes, restart and pull policies, healthcheck, the 14 plugin mounts). Ports: `hyperalumni` WordPress 3080 and phpMyAdmin 3090; `hyperonline` 4080 and 4090.
- **Cutover:** both sites started from the new location, reused their existing volumes (no new volumes appeared), and have the `hyperPress` theme and all 14 plugin files mounted. HTTP 200 on every published port. The owner confirmed that each site renders and that admin login works. Both sites were left stopped, as they were at the start.
- **Data:** the owner said the volumes are disposable local development data, so the backup and baseline-state steps were dropped from the plan and spec before executing. No volumes were removed or recreated.
- **Env files:** copied by the owner (the agent cannot read or copy them). Key names of all eight real files and eight templates were verified with EnvSitter, and the three secret-bearing templates (`.env.db`, `.env.mariadb`, `.env.wordpress`) were verified empty. The owner blanked the plain-copy per-site templates by hand; they hold only ports and profile settings.
- **Old stacks:** `../hyperalumni` and `../hyperonline` are untouched (checksums of their `compose.yaml`, `compose.main.yaml` and `php/uploads.ini` match). Retiring them is the owner's call.
- **Separate defect found and fixed during this plan:** two PHP-tooling compose files from stage 0 still used the old sibling-repo paths (see the correction note in the monorepo restructure plan). The fix is in `78ba2d0`.
- **Naming:** commit subjects and plan filenames use topic names, not stage numbers. This plan was renamed accordingly and two earlier subjects were reworded.
- **Check adjusted:** Task 6 Step 2's literal `grep` for `docker/hyperalumni` was replaced by checking the new section's header and start command.
- **Suggested edits to `../AGENTS.md` (outside this repo, not applied):** state that `uikit/` is now a git repo containing the former `theme/` and `plugins/` (their history kept); that the `hyperalumni` and `hyperonline` stacks now live in `uikit/docker/` and mount `uikit/themes/hyperpress-foundation` and `uikit/plugins/hyperpress-*`; that the old `hyperalumni/` and `hyperonline/` directories are superseded; and that its note about `hyperonline` mounting a smaller plugin set is stale (both mount all 14).
- **Not done / next:** archive `../theme` and `../plugins` once this repo is pushed and is the working copy; pushing `master` to `hyperalumni/hyperPress` is a separate decision; Nx, `themes/hyperpress-uikit/`, `libs/` and a `docker/uikit/` dev site are later stages.
- **Final review and one reverted experiment:** a fresh-context review found nothing Critical and one Important item: short-form bind mounts let Docker silently create a missing host path as an empty root-owned folder (the same way `uikit/theme` appeared earlier). Converting the mounts to the long form with `create_host_path: false` passed a structural check (16 of 16 binds) but failed the behavioural test: with a bad `THEME_SRC` in a throwaway project the container was still created and the folder still appeared, because the engine is Docker Desktop (the `docker-desktop` context), which creates bind sources itself. The change was reverted and the root `AGENTS.md` now warns about this and gives a `find` command for stray folders. Deferred minors: `COMPOSE_PROFILES` and `COMPOSE_BAKE` in the per-site local env file have no effect (an explicit `--profile local` is needed, as documented); a relative `THEME_SRC` override resolves from `docker/<site>/`; the theme and plugin mounts are shared read-write by both sites; `down -v` in either tooling copy also removes the composer cache volume shared with the standalone repos.
