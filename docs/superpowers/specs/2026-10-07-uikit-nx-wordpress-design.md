# UIkit + Nx + pnpm WordPress workspace: design

Status: stages 0-7 done; verdict in `docs/superpowers/audit/2026-10-16-uikit-nx-oxc-verdict.md` (created 2026-10-07, updated 2026-10-11: stage 0 uses the hyperPress repo as the base; all docs live at the repo root; 2026-10-15: pins bumped to Node 26.12.0 and pnpm 12.12.1, plugin count corrected to 16, stages 0 and 0b done, Nx pinned to 23.3.0; 2026-10-15: two `dist/` conventions recorded, Foundation keeps its in-theme `dist/`; 2026-10-15: stages 2-4 done, tooling image is PHP 8.5, Vite runs through the inferred `@nx/vite` plugin; 2026-10-16: stages 5-7 done, `allowBuilds` has `nx: true`)

## Purpose

A pnpm + Nx monorepo for the hyperPress theme(s) and plugins, used to test whether Nx, UIkit (Sass
source), Vite, Timber and the Oxc tools (Oxlint, Oxfmt) make a good modern WordPress workflow. The
new UIkit theme is an experiment, not a replacement for the Foundation theme.

The monorepo is the existing hyperPress theme repo (`hyperalumni/hyperPress`, a FoundationPress
fork), restructured by new commits: the theme moves into `themes/hyperpress-foundation/`, the plugins
repo is added under `plugins/` (rebased on top, no merge commit), and root workspace files are added. Nx and the new theme follow.

## Decisions

| Area                             | Decision                                                                                                                                                                                                                                                                                                                                                                                                        |
| -------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Repo                             | `uikit/` is a working clone of the hyperPress history (branch `master`, original commit hashes kept, so the FoundationPress lineage and the fork relationship of `hyperalumni/hyperPress` are intact). Restructured by new commits. Nothing is pushed in this design. `../theme`, `../plugins` and the Docker stacks that mount them are untouched                                                              |
| Workspace                        | pnpm workspace from stage 0; Nx 23.3.0 (exact pin; all `@nx/*` packages on the same 23.x version) from stage 1                                                                                                                                                                                                                                                                                                  |
| Runtime                          | Node 26.12.0 and pnpm 12.12.1, as already pinned by the theme (`.nvmrc`, `devEngines`); `only-allow pnpm` preinstall                                                                                                                                                                                                                                                                                            |
| Docs                             | One location: the repo-root `docs/superpowers/{specs,plans,audit}/`, holding workspace (`uikit-*`), theme (`theme-*`) and plugins (`plugins-*`) documents. Checked: no filename collisions between the three sets                                                                                                                                                                                               |
| Foundation theme                 | Existing theme moves unchanged, by one rename commit, to `themes/hyperpress-foundation/` (gulp/webpack build kept as is). Its `docs/` is not moved; it stays at the root                                                                                                                                                                                                                                        |
| Plugins                          | The `../plugins` repo is imported with its history (rebased on top of the move commit, so no merge commit): everything under `plugins/` as-is (16 `hyperpress-*` plugins plus its shared `composer.json`, `phpcs.xml.dist`, `phpunit.xml.dist`, `tests/`, `bin/`, `AGENTS.md`, Docker files), except its `docs/`, which goes to the root `docs/`. Hoisting shared tooling to the root is a later, separate step |
| New theme                        | Classic PHP theme `themes/hyperpress-uikit/`, Timber (Twig) templating, TypeScript, Sass                                                                                                                                                                                                                                                                                                                        |
| UIkit                            | Consumed from Sass source (`uikit/src/scss`, shipped in the npm package); variables overridden, only used components imported                                                                                                                                                                                                                                                                                   |
| Bundler                          | Vite via the inferred `@nx/vite/plugin` for the new theme (the `@nx/vite:build` executor is deprecated and broke on Nx 23.3); emits a manifest                                                                                                                                                                                                                                                                  |
| JS/TS lint                       | Oxlint via the first-party `@nx/oxlint` Nx plugin                                                                                                                                                                                                                                                                                                                                                               |
| Format (JS/TS/SCSS/JSON/YAML/MD) | Oxfmt, run over the whole monorepo by root `package.json` scripts (`"fmt": "oxfmt"`, `"fmt:check": "oxfmt --check"`), not as per-project Nx targets                                                                                                                                                                                                                                                             |
| SCSS lint                        | Stylelint (Oxlint does not cover CSS)                                                                                                                                                                                                                                                                                                                                                                           |
| PHP lint                         | PHPCS (WordPress standard), in Docker only                                                                                                                                                                                                                                                                                                                                                                      |
| Twig                             | No formatter or linter (none available)                                                                                                                                                                                                                                                                                                                                                                         |
| Dev environment                  | One shared Docker Compose file in `docker/` with thin per-site wrappers (`docker/hyperalumni/`, `docker/hyperonline/`, `docker/uikit/`); PHP/Composer run in a tooling container, never on the host                                                                                                                                                                                                             |
| Stub plugin                      | `plugins/hyperpress-example/` (kept in stage 5; whether it stays is an open question in the verdict)                                                                                                                                                                                                                                                                                                            |

## Workspace layout

```
uikit/
├── package.json, pnpm-workspace.yaml, .nvmrc, .gitignore   # stage 0
├── nx.json                                                  # stage 1
├── AGENTS.md
├── docs/superpowers/{specs,plans,audit}/                    # all docs: workspace, theme, plugins
├── themes/
│   ├── hyperpress-foundation/   # existing Foundation theme, with its own AGENTS.md
│   └── hyperpress-uikit/        # new Timber + UIkit theme: functions.php, src/, views/
├── plugins/                     # ../plugins repo minus docs/: hyperpress-*/, composer.json, tests/, bin/
├── libs/uikit-tokens/           # Sass: UIkit variable overrides + component imports
├── libs/ui/                     # shared TypeScript: UIkit init, small helpers
├── docker/                      # compose: wordpress, db, tooling
└── dist/                        # generated, git-ignored, mounted into WordPress
```

## Stage 0: restructure into a monorepo

Goal: one repo containing both codebases with history, installable with a single root
`pnpm install`, with no behavior change. The spec and the stage 0 plan are committed first (two docs
commits on top of the imported theme history, so the plan precedes the code), then these commits on
`master`, in this order; commit 3 may be split into two (workspace files, then `AGENTS.md` and path
fixes).

Setup:

- `uikit/` currently holds a fresh `git init` (no commits), an untracked `.idea/` and the untracked
  workspace spec under `docs/`. The theme's tracked `docs/` stays at the root and has no filename in
  common with the spec, so the spec can stay in place; git refuses the checkout if it would
  overwrite an untracked file, which is the safety net.
- Fetch the theme's `master` from the local `../theme` clone (`git fetch ../theme master`), check it
  out as `master`, and set `origin` to `git@github.com:hyperalumni/hyperPress.git` without pushing.
  Only `master` is brought over. The `backup/*`, `cleanup/*` and `feature/*` branches stay in
  `../theme`.

Commits:

1. **Move the theme.** `git mv` every tracked top-level entry except `docs/` into
   `themes/hyperpress-foundation/`, including its `AGENTS.md`, `.nvmrc`, `.gitignore` and `.github/`
   (the last becomes inert there; a root workflow directory is a later decision). One pure-rename
   commit, so `git log --follow` keeps working across it. Original theme hashes are untouched.
2. **Rebase the plugins repo on top.** In a throwaway clone of `../plugins` under `/tmp/opencode`, rewrite
   history with `git filter-repo` so that every path gets a `plugins/` prefix except `docs/`, which
   stays at `docs/` (for example `--to-subdirectory-filter plugins` followed by
   `--path-rename plugins/docs/:docs/`; the plan fixes the exact invocation and checks the result).
   Fetch it into `uikit/` as a local branch, rebase it onto `master` (`git rebase --onto master --root`, each replayed commit signed by the committer), and fast-forward `master`, so history stays linear and no merge commit is added. The prefix means no
   root-file collisions (both repos have `AGENTS.md`, `.gitignore`, `composer.json`, `compose.yaml`,
   `Dockerfile`), and `git log docs/superpowers/plans/<file>` shows the plugin docs' history without
   `--follow`. Plugin commit hashes change; they have no upstream, so nothing is lost. `master` only;
   no tags exist.
3. **Root workspace files and path fixes.**
   - `package.json`: `private`, workspace name, `devEngines`/`engines` pins copied from the theme,
     `preinstall: npx only-allow pnpm`. No scripts yet.
   - `pnpm-workspace.yaml`: `packages: [themes/*, plugins/*]` (`libs/*` is added when it exists) plus
     the pnpm settings currently in the theme's file (`supportedArchitectures`, `allowBuilds`,
     `minimumReleaseAgeStrict`, and the rest), because pnpm reads these only at the workspace root.
   - Remove the theme's own `pnpm-workspace.yaml` and `pnpm-lock.yaml`; generate a root
     `pnpm-lock.yaml`.
   - Root `.nvmrc` (26.12.0) and `.gitignore` (`node_modules`, `dist`, `.idea`, `.env*`).
   - A root `AGENTS.md` for the workspace, short and cross-cutting: what the repo is and its layout;
     one-time environment setup (`source ~/.nvm/nvm.sh && nvm use`, pnpm only, PHP tooling in
     Docker); that this is now a single git repo (commit from the root with explicit pathspecs, never
     `git add -A`); where the docs live; and a pointer to read
     `themes/hyperpress-foundation/AGENTS.md` or `plugins/AGENTS.md` before working in those areas.
     Project-specific rules stay in the nested files. If the two nested files' commit rules differ,
     the root file states the single rule that now applies and the nested files are edited to match.
     It also states that it supersedes `../AGENTS.md` (the `hyperPress/` workspace file, outside this
     repo, which still describes `theme/` and `plugins/` as separate repos).
   - Update references to the moved docs: the four `docs/superpowers/...` references in
     `plugins/AGENTS.md` and `plugins/README.md`, and the docs references in
     `themes/hyperpress-foundation/AGENTS.md`, now point to the root `docs/`. Other edits to the
     moved `AGENTS.md` files are limited to paths or commands the restructure changes (for example commands
     now run via `pnpm --filter @hyper/press-foundation`).
   - The theme's existing `docs/superpowers/plans/README.md` stays at the root and now serves the
     whole workspace; it is edited only if it is theme-specific.
   - Completed plans keep their original wording, including plugin paths written relative to the old
     plugins repo root; they are historical records.

Not carried by git: the theme's git-ignored local files (`config.yml`, `.env`) and installed
`vendor/`, `node_modules/`. They are rebuilt, or copied with `cp` on request without reading them.

Fork and upstream:

- Because the theme's commit hashes are kept, the FoundationPress lineage is real git ancestry. An
  `upstream` remote can be added later and merged with `git merge -Xsubtree=themes/hyperpress-foundation`.
- `origin` is the same GitHub repo, so the "forked from" relationship is preserved if this history is
  ever pushed there. Pushing restructures the shared `master`, so it is a separate decision, out of
  scope here.

Verification (stage 0 gate):

- `pnpm install` succeeds at the root.
- `pnpm --filter @hyper/press-foundation build` succeeds and its output file list matches a build from the
  original `../theme`.
- `git log --follow -- themes/hyperpress-foundation/functions.php` shows pre-move history with the
  original hashes, and `git log -- plugins/hyperpress-media` shows plugin history.
- `plugins/docs` does not exist; `git log -- docs/superpowers/plans/2026-10-06-label-plugin-extraction.md`
  shows its history; root `docs/superpowers/` contains the `theme-*`, `plugins-*` and `uikit-*` files.
- The plugins repo's existing lint and test commands still pass when run from `plugins/`.
- `../theme`, `../plugins`, `hyperalumni/` and `hyperonline/` are unchanged (`git status` clean in
  both original repos).

## Build and asset loading (new theme, stage 2 onward)

- `nx build` writes the finished theme to `dist/themes/hyperpress-uikit/`: PHP, Twig, built
  `assets/`, `.vite/manifest.json`, and Composer `vendor/` (Timber) installed by the tooling container.
- The `wordpress` service bind-mounts the `dist/` theme and plugin folders into `wp-content/`.
- Two `dist/` conventions (decided 2026-10-15). A project that is itself the deployable theme, served
  from its own source folder, keeps its build output inside it: `themes/hyperpress-foundation/dist`
  stays unchanged (gulp/webpack, PHP reads `dist/assets` relative to the theme, compose mounts the
  source folder). A project whose source folder is not deployable builds an assembled artifact into the
  repo-level, git-ignored `dist/<type>/<name>` (e.g. `dist/themes/hyperpress-uikit`,
  `dist/libs/uikit-tokens`), and compose mounts that artifact. Moving Foundation to the repo-level
  `dist/` is deliberately not done: it would need an assembled artifact, a new copy/install pipeline,
  a changed `THEME_SRC` for the live stacks, reworked gulp watch/browser-sync, and changes to the
  `package` task and tests. Revisit only if the UIkit theme becomes the main theme.
- Dev: `nx serve` runs the Vite dev server on the host at a fixed port (5173, `strictPort`). A theme `Assets` class
  detects a dev marker (`VITE_DEV` constant or a `.vite-dev` file Nx writes while serving) and prints
  module script tags for the Vite client and entry. The marker is JSON with both URLs,
  `{"browser": "http://localhost:5173", "internal": "http://host.docker.internal:5173"}`: the browser
  and the WordPress container reach the host differently. `Assets` probes `<internal>/@vite/client`
  (the Vite root URL returns 404) before trusting the marker. Sass and TS hot-reload. PHP and Twig
  changes trigger a full reload.
- Production: `Assets` reads `manifest.json` and enqueues hashed assets through `wp_enqueue_*`.
- `nx up` builds first, then starts compose, because the mount is empty until a build completes.
- The assembling `build` target is a noop that depends on the copy, Vite and Composer targets; it needs
  `outputs: []`. Without it Nx defaults the outputs to `dist/{projectRoot}`, restores that folder from
  the cache, recreates `dist/themes/hyperpress-uikit` and leaves the WordPress bind mount stale
  (repair: `docker compose --profile local up -d --force-recreate wordpress` from `docker/uikit`).

## Stub plugin

`hyperpress-example` registers `[hp_uikit_card]`, rendering UIkit-class markup and importing compiled
CSS from `libs/uikit-tokens`. It exists to prove a plugin can consume a shared lib and that Nx
tracks the dependency, so a change to the lib rebuilds the theme and the plugin. Stage 5 kept it as
a stub rather than using an existing plugin; the owner decided on 2026-10-16 that it stays in the repo as an example.

## Docker stack

Replaces the stacks in `hyperPress/hyperalumni/` and `hyperPress/hyperonline/`. Those two are byte-identical in `compose.main.yaml`, `php/uploads.ini` and every `.env*` file except `.env.local` (ports and `COMPOSE_*` settings). They differ in the wrapper's `name:`.

Layout:

```
docker/
├── compose.main.yaml     # shared: wordpress, db, phpmyadmin (profile `local`); later a `tooling` service
├── php/uploads.ini
├── .env, .env.db, .env.mariadb, .env.phpmyadmin, .env.wordpress, .env.versions   # shared, git-ignored; .env*.example templates committed
├── hyperalumni/          # compose.yaml (name: hyperalumni, include ../compose.main.yaml) + .env.local (git-ignored; .env.local.example committed)
├── hyperonline/          # same, name: hyperonline
└── uikit/                # added in stage 2: dev site for the new theme
```

Rules:

- Project names (`name: hyperalumni`, `name: hyperonline`), the named volumes (`db`, `wp`), service names, container-side mount paths (`wp-content/themes/hyperPress`, `wp-content/plugins/hyperpress-*`), the `local` profile, `restart`, `pull_policy` and the healthcheck are unchanged. The existing databases and uploads are therefore reused when they still exist (recreating them is acceptable for local development). The theme slug `hyperPress` is stored in each site's database, so it is not renamed.
- Each wrapper `include`s `../compose.main.yaml` with `project_directory: .` and `env_file: [../.env, .env.local, ../.env.versions]`. A spike with Docker Compose 5.5.1 showed that this resolves bind-mount sources and service `env_file` paths relative to the wrapper directory (so `compose.main.yaml` uses `../php/...`, `../.env.db`, `../../plugins/...`) and keeps the volume names `<project>_db` and `<project>_wp`. Without `project_directory` that layout did not produce a usable config.
- Only host paths change: `../theme` becomes `../../themes/hyperpress-foundation`, and `../plugins/hyperpress-*` becomes `../../plugins/hyperpress-*`. The theme mount source and slug are parameters, `THEME_SRC` (default `../../themes/hyperpress-foundation`) and `THEME_SLUG` (default `hyperPress`), so the new theme is served by the same file from its own `.env.local` (spike-verified).
- The 16 per-plugin mounts stay (not one `plugins/` mount), because third-party plugins such as `meta-box-aio` live in the `wp` volume.
- Secrets: the agent's shell rejects commands containing `.env` and EnvSitter works only inside the project, so the owner copies the real `.env*` files with a command block from the plan. The agent then checks key names and that the templates are empty with EnvSitter, without reading values. Templates list names only for secret-bearing files (`.env.db`, `.env.mariadb`, `.env.wordpress`); the other templates are plain copies. The root `.gitignore` gains `!.env*.example`.
- The `tooling` service (PHP 8.5 + Composer, for `composer install` and PHPCS) and the `uikit` site exist since stage 2 (`docker/uikit`, project `hyperuikit`, WordPress on 5080, phpMyAdmin on 5090). The theme still declares `php >=8.3` and its lock pins platform php 8.3.0. The tooling service runs as root, so every run passes `--user "$(id -u):$(id -g)" -e COMPOSER_HOME=/tmp/composer` (otherwise `vendor/` and `dist/` files become root-owned). The shared `wordpress` service gets `extra_hosts: host.docker.internal:host-gateway`, needed on Linux so the container reaches the host's Vite server.

### Stage 0b: migrate the existing stacks

The data in these stacks is local development data and is disposable (confirmed by the owner), so there are no backups or state-equality checks. The project and volume names are kept, so a site that still has its volumes reuses them.

1. Preflight: Compose supports `include` with `project_directory`; both old stacks parse; no old container is running; record `sha256` of the old stacks' `compose.yaml`, `compose.main.yaml` and `php/uploads.ini` so the old directories can be proven unmodified at the end; save each old config with `docker compose config --no-env-resolution --format json` (prints env file paths, never values).
2. The owner copies the env files and creates the templates (command block in the plan); the agent verifies key names and empty templates.
3. Create the `docker/` files. Equivalence check: the new config for each site, normalized for host paths, must equal the saved old config.
4. Cut over `hyperalumni`, then `hyperonline`: `up -d` from `docker/<site>/` (containers are recreated because bind-mount sources changed). Checks: whether the volumes were reused or recreated is recorded (a fresh volume means the WordPress installer appears and third-party plugins such as `meta-box-aio` must be reinstalled; acceptable); the theme and plugin mounts are present in the container; the published ports answer over HTTP; the owner confirms the site loads. If a site will not start against its old volumes, `docker compose down -v` for that site only is allowed.
5. Docs: root `AGENTS.md` describes the new commands. The old directories stay untouched (retiring them is the owner's call), and the plan lists the edits to suggest for `../AGENTS.md`.

Verification: both sites start from the new location; the old directories are unmodified; `git status` is clean; no real `.env*` file is tracked.

## Error handling

- A missing manifest or missing `vendor/` (Timber not installed) shows an admin notice instead of a
  fatal error.
- A stale dev marker with no Vite server running falls back to production assets after a short
  reachability check.

## Testing and verification

- Vitest for `libs/ui`.
- PHPUnit in the tooling container for the `Assets` class (dev marker, manifest lookup,
  missing-manifest behavior).
- A smoke script: bring up the stack; homepage returns 200; manifest asset URLs resolve; the
  shortcode renders UIkit classes.
- Gate: `nx run-many -t lint build test`, `pnpm fmt:check`, plus the smoke script before the stack is
  called working. Stage 0 has its own gate (above).

## Staged rollout

0. Restructure the hyperPress repo into a monorepo: move the theme, rebase the plugins on top, add root files. (done)
   0b. Migrate the `hyperalumni` and `hyperonline` Docker stacks into `docker/` (see Docker stack). (done)
1. Add Nx 23.x; confirm the toolchain runs on Node 26; register existing projects. (done) On Node 26.12.0 the Nx daemon works, the `nx` postinstall is allowed via `allowBuilds` (`nx: true`, the owner's decision; the first run listed it among the ignored builds), Nx is pinned in the pnpm catalog, the theme's Nx config lives in `project.json`, and the plugin tooling project is `plugins/project.json`.
2. Docker stack and a bare `hyperpress-uikit` theme rendering through Timber. (done)
3. Vite build, manifest-based asset loading, UIkit from `libs/uikit-tokens`. (done)
4. Dev HMR against the Dockerized WordPress. (done; the automated websocket proof passed, and the owner confirmed Sass hot reload in the browser on 2026-10-16)
5. Stub (or real) plugin consuming the shared lib; prove `nx affected` and cache hits. (done) `plugins/hyperpress-example` builds the shared Sass into its own `dist/uikit.css` and renders `[hp_uikit_card]`; a token change rebuilt exactly the tokens, the plugin and the theme's Vite build, and a repeat build was a 7/7 cache hit (0.8 s against 6.3 s cold).
6. Oxlint (`@nx/oxlint`), Stylelint and PHPCS as Nx targets; Oxfmt as root `fmt`/`fmt:check` scripts. (done) The Nx targets are named `lint-js`, `lint-css` and `lint-php` because `lint` already means composer PHPCS on `plugins-tooling`; Oxfmt reformatted 82 files once, with `gulpfile.mjs` and vendored paths ignored.
7. Packaging and an experiment write-up. (done) `nx package @hyper/press-uikit` writes a deterministic zip of the assembled theme, `nx setup @hyper/press-uikit` takes a fresh clone to a rendered site, and the write-up is `docs/superpowers/audit/2026-10-16-uikit-nx-oxc-verdict.md`.

## Success criteria

- Stage 0: one repo, theme history and fork lineage intact, plugin history preserved, all docs in
  one place, one root `pnpm install`, Foundation theme builds as before. Met (stage 0 gate); the Foundation `dist` hashes were also unchanged after the Oxfmt reformat.
- Changing `libs/uikit-tokens` rebuilds only the new theme and the plugin (`nx affected`); a repeat
  build hits the Nx cache. Met: the affected set was the tokens, the theme and `hyperpress-example`, the partial rebuild was 3 of 7 tasks cached (2.7 s), and a repeat build was 7 of 7 (0.8 s).
- HMR works against the Dockerized WordPress. Met for Sass: the automated websocket proof passed and the owner confirmed Sass hot reload in the browser on 2026-10-16.
- A fresh clone goes from `pnpm install` to a rendered site in one command. Met with prerequisites: copy the local env example for `docker/uikit`, export `UIKIT_ADMIN_USER`, `UIKIT_ADMIN_PASSWORD` and `UIKIT_ADMIN_EMAIL`, then `nx setup @hyper/press-uikit`; verified on a scratch project and idempotent.
- A written verdict on Nx and Oxc for WordPress work. Met: `docs/superpowers/audit/2026-10-16-uikit-nx-oxc-verdict.md` (the owner fills in the verdict lines).

## Out of scope

Block editor / `theme.json`, content migration, CI, Vite+, Twig linting, hoisting the plugins repo's
shared tooling to the repo root, pushing the restructured history, retiring `hyperPressPlugins`,
and any change to `../theme` or `../plugins`. The old `hyperalumni/` and `hyperonline/` directories are not modified or deleted by stage 0b; retiring them is the owner's call.

## Risks and open items

- `uikit/` and `../theme` diverge as soon as either gets new commits. Until stage 0b repoints the Docker stacks they keep using `../theme` and `../plugins`; stage 0b makes this repo the working copy, after which `../theme` and `../plugins` should be archived (decided at cutover).
- Pushing the restructured `master` to `hyperalumni/hyperPress` changes the shared repo layout for
  everyone using it; it needs its own decision (branch vs `master`, and what happens to
  `hyperPressPlugins`).
- Vite runs on the host while WordPress runs in Docker; CORS and host-networking need an early test. Result of the stage 4 spike: Vite blocks requests with an unknown `Host` header, so `host.docker.internal` is in `server.allowedHosts`; CORS allows the WordPress origin (`UIKIT_WP_ORIGIN`, default `http://localhost:5080`); on Linux the container needs the `extra_hosts` entry above.
- Node 26 is in the Current phase. The theme already pins 26.12.0; compatibility of Nx, Vite, Sass
  and the Oxc tools with it is verified in stage 1, not assumed.
- Oxfmt runs outside Nx (root scripts over the whole repo), so formatting is not cached by Nx and is
  not part of `nx run-many`; it is a separate gate. Nx is pinned to the latest 23.x release, with
  `@nx/oxlint` and `@nx/vite` on the same version.
- The theme's own `AGENTS.md` says Node >=24 while its `package.json` pins 26.12.0; the pin is the
  source of truth.
- Exact Vite, Timber and UIkit versions are chosen when the plan is written, after checking current
  releases.
