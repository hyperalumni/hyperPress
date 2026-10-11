# UIkit + Nx + pnpm WordPress workspace: design

Status: draft for review (created 2026-10-07, updated 2026-10-11: stage 0 uses the hyperPress repo as the base; all docs live at the repo root)

## Purpose

A pnpm + Nx monorepo for the hyperPress theme(s) and plugins, used to test whether Nx, UIkit (Sass
source), Vite, Timber and the Oxc tools (Oxlint, Oxfmt) make a good modern WordPress workflow. The
new UIkit theme is an experiment, not a replacement for the Foundation theme.

The monorepo is the existing hyperPress theme repo (`hyperalumni/hyperPress`, a FoundationPress
fork), restructured by new commits: the theme moves into `themes/hyperpress-foundation/`, the plugins
repo is merged in under `plugins/`, and root workspace files are added. Nx and the new theme follow.

## Decisions

| Area | Decision |
|---|---|
| Repo | `uikit/` is a working clone of the hyperPress history (branch `master`, original commit hashes kept, so the FoundationPress lineage and the fork relationship of `hyperalumni/hyperPress` are intact). Restructured by new commits. Nothing is pushed in this design. `../theme`, `../plugins` and the Docker stacks that mount them are untouched |
| Workspace | pnpm workspace from stage 0; Nx 23.x (latest; all `@nx/*` packages on the same 23.x version) from stage 1 |
| Runtime | Node 26.11.1 and pnpm 12.11.2, as already pinned by the theme (`.nvmrc`, `devEngines`); `only-allow pnpm` preinstall |
| Docs | One location: the repo-root `docs/superpowers/{specs,plans,audit}/`, holding workspace (`uikit-*`), theme (`theme-*`) and plugins (`plugins-*`) documents. Checked: no filename collisions between the three sets |
| Foundation theme | Existing theme moves unchanged, by one rename commit, to `themes/hyperpress-foundation/` (gulp/webpack build kept as is). Its `docs/` is not moved; it stays at the root |
| Plugins | The `../plugins` repo is merged in with history: everything under `plugins/` as-is (14 `hyperpress-*` plugins plus its shared `composer.json`, `phpcs.xml.dist`, `phpunit.xml.dist`, `tests/`, `bin/`, `AGENTS.md`, Docker files), except its `docs/`, which goes to the root `docs/`. Hoisting shared tooling to the root is a later, separate step |
| New theme | Classic PHP theme `themes/hyperpress-uikit/`, Timber (Twig) templating, TypeScript, Sass |
| UIkit | Consumed from Sass source (`uikit/src/scss`, shipped in the npm package); variables overridden, only used components imported |
| Bundler | Vite via `@nx/vite` for the new theme; emits a manifest |
| JS/TS lint | Oxlint via the first-party `@nx/oxlint` Nx plugin |
| Format (JS/TS/SCSS/JSON/YAML/MD) | Oxfmt, run over the whole monorepo by root `package.json` scripts (`"fmt": "oxfmt"`, `"fmt:check": "oxfmt --check"`), not as per-project Nx targets |
| SCSS lint | Stylelint (Oxlint does not cover CSS) |
| PHP lint | PHPCS (WordPress standard), in Docker only |
| Twig | No formatter or linter (none available) |
| Dev environment | Self-contained Docker Compose stack in `docker/`; PHP/Composer run in a tooling container, never on the host |
| Stub plugin | `plugins/hyperpress-example/` was planned when no real plugins were in scope; revisit in stage 5 (a real plugin may serve instead) |

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
2. **Merge the plugins repo.** In a throwaway clone of `../plugins` under `/tmp/opencode`, rewrite
   history with `git filter-repo` so that every path gets a `plugins/` prefix except `docs/`, which
   stays at `docs/` (for example `--to-subdirectory-filter plugins` followed by
   `--path-rename plugins/docs/:docs/`; the plan fixes the exact invocation and checks the result).
   Fetch it into `uikit/` and merge with `--allow-unrelated-histories`. The prefix means no
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
   - Root `.nvmrc` (26.11.1) and `.gitignore` (`node_modules`, `dist`, `.idea`, `.env*`).
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
     moved `AGENTS.md` files are limited to paths or commands the merge changes (for example commands
     now run via `pnpm --filter @hyper/press`).
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
- `pnpm --filter @hyper/press build` succeeds and its output file list matches a build from the
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
- Dev: `nx serve` runs the Vite dev server on the host at a fixed port. A theme `Assets` class
  detects a dev marker (`VITE_DEV` constant or a `.vite-dev` file Nx writes while serving) and prints
  module script tags for the Vite client and entry. Sass and TS hot-reload. PHP and Twig changes
  trigger a full reload.
- Production: `Assets` reads `manifest.json` and enqueues hashed assets through `wp_enqueue_*`.
- `nx up` builds first, then starts compose, because the mount is empty until a build completes.

## Stub plugin

`hyperpress-example` registers `[hp_uikit_card]`, rendering UIkit-class markup and importing compiled
CSS from `libs/uikit-tokens`. It exists to prove a plugin can consume a shared lib and that Nx
tracks the dependency, so a change to the lib rebuilds the theme and the plugin. With the real
plugins now in the repo, stage 5 decides whether to keep it or use an existing plugin instead.

## Docker stack

Services on the `local` compose profile (matching `hyperalumni`/`hyperonline`):
`wordpress` (PHP 8.3), `db` (MariaDB), `tooling` (PHP 8.3 + Composer, used for `composer install`
and PHPCS). Credentials live in git-ignored `.env` files; `.env.example` is committed. The existing
`hyperalumni`/`hyperonline` stacks keep mounting `../theme` and `../plugins` until they are
deliberately repointed.

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

0. Restructure the hyperPress repo into a monorepo: move the theme, merge the plugins, add root files.
1. Add Nx 23.x; confirm the toolchain runs on Node 26; register existing projects.
2. Docker stack and a bare `hyperpress-uikit` theme rendering through Timber.
3. Vite build, manifest-based asset loading, UIkit from `libs/uikit-tokens`.
4. Dev HMR against the Dockerized WordPress.
5. Stub (or real) plugin consuming the shared lib; prove `nx affected` and cache hits.
6. Oxlint (`@nx/oxlint`), Stylelint and PHPCS as Nx targets; Oxfmt as root `fmt`/`fmt:check` scripts.
7. Packaging and an experiment write-up.

## Success criteria

- Stage 0: one repo, theme history and fork lineage intact, plugin history preserved, all docs in
  one place, one root `pnpm install`, Foundation theme builds as before.
- Changing `libs/uikit-tokens` rebuilds only the new theme and the plugin (`nx affected`); a repeat
  build hits the Nx cache.
- HMR works against the Dockerized WordPress.
- A fresh clone goes from `pnpm install` to a rendered site in one command.
- A written verdict on Nx and Oxc for WordPress work.

## Out of scope

Block editor / `theme.json`, content migration, CI, Vite+, Twig linting, hoisting the plugins repo's
shared tooling to the repo root, pushing the restructured history, retiring `hyperPressPlugins`,
repointing the existing Docker stacks, and any change to `../theme`, `../plugins`, `hyperalumni/` or
`hyperonline/`.

## Risks and open items

- `uikit/` and `../theme` diverge as soon as either gets new commits. The Docker stacks keep using
  `../theme` and `../plugins`, so how work is synced or cut over is decided after stage 0.
- Pushing the restructured `master` to `hyperalumni/hyperPress` changes the shared repo layout for
  everyone using it; it needs its own decision (branch vs `master`, and what happens to
  `hyperPressPlugins`).
- Vite runs on the host while WordPress runs in Docker; CORS and host-networking need an early test.
- Node 26 is in the Current phase. The theme already pins 26.11.1; compatibility of Nx, Vite, Sass
  and the Oxc tools with it is verified in stage 1, not assumed.
- Oxfmt runs outside Nx (root scripts over the whole repo), so formatting is not cached by Nx and is
  not part of `nx run-many`; it is a separate gate. Nx is pinned to the latest 23.x release, with
  `@nx/oxlint` and `@nx/vite` on the same version.
- The theme's own `AGENTS.md` says Node >=24 while its `package.json` pins 26.11.1; the pin is the
  source of truth.
- Exact Vite, Timber and UIkit versions are chosen when the plan is written, after checking current
  releases.
