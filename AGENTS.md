# hyperPress

pnpm + Nx monorepo for the hyperPress WordPress sites: the Foundation theme, the `hyperpress-*` plugins, an experimental Timber + UIkit theme with shared libs, and the local Docker stacks that run them. Design: `docs/superpowers/specs/2026-10-07-uikit-nx-wordpress-design.md`. Experiment write-up: `docs/superpowers/audit/2026-10-16-uikit-nx-oxc-verdict.md`.

## Layout

- `themes/hyperpress-foundation/`: the classic Foundation theme (gulp/webpack). Read its `AGENTS.md` before working there.
- `themes/hyperpress-uikit/`: the experimental Timber + UIkit theme (PHP `Assets` class, Twig views, Vite entry). Read its `AGENTS.md` before working there.
- `plugins/`: the `hyperpress-*` plugins plus shared PHP tooling (`composer.json`, phpcs, phpunit, `bin/`, `tests/`). Read its `AGENTS.md` before working there.
- `plugins/hyperpress-example/`: stub plugin kept as an example. It builds the shared Sass (`libs/uikit-tokens`) into its own `dist/uikit.css` and renders `[hp_uikit_card]` with UIkit classes, which shows a plugin consuming the lib with Nx tracking the dependency. It is mounted (inactive) on all three sites; run `nx build hyperpress-example` before activating it anywhere other than the uikit site.
- `libs/uikit-tokens/`: Sass, UIkit variable overrides and component imports. `libs/ui/`: TypeScript, UIkit init, tested with Vitest and happy-dom.
- `docker/`: the shared Compose file (with the `tooling` PHP/Composer service) and per-site wrappers for the local WordPress sites `hyperalumni`, `hyperonline` and `uikit`.
- `docs/superpowers/{specs,plans,audit}/`: all specs, plans and audits (`uikit-*` for the workspace, `theme-*`, `plugins-*`).
- `dist/` (git-ignored): Foundation keeps its build output in the theme (`themes/hyperpress-foundation/dist`, served from its source folder). A project whose source folder is not deployable, such as the uikit theme, builds an assembled artifact to the repo-level `dist/<type>/<name>` (e.g. `dist/themes/hyperpress-uikit`) that Compose mounts.

## Environment

- Node comes from nvm and is not on the default PATH. Load it first in each shell: `source ~/.nvm/nvm.sh && nvm use && export PATH="$HOME/.local/share/pnpm/bin:$PATH"` (root `.nvmrc`, Node 26.12.0).
- pnpm only (enforced by `preinstall`; no npm/yarn). The root `devEngines` pins pnpm 12.12.1. Run `pnpm install` once at the root.
- PHP runs in Docker only, never on the host. The floor is PHP `>=8.3` everywhere (`composer.json`, plugin headers, PHPCS `testVersion`); the suites pass on 8.3, 8.4 and 8.5. Production stays on 8.3 for now; see the verdict for the 8.4/8.5 recommendation.
- `.env*` files are git-ignored. Do not read or print them. Commit only the `*.example` templates.

## Conventions

- Nx project config (targets, cache, tags) lives in each project's `project.json`; `package.json` holds scripts and dependencies only.
- Shared dependency versions (Nx and `@nx/*` included) are pinned in the `catalog:` in `pnpm-workspace.yaml`; use `"catalog:"` in package.json and bump them there.
- Cached targets declare specific `outputs`. A cached `nx:noop` aggregator needs `outputs: []`, otherwise Nx defaults to `dist/<project>` and replaces the theme directory.

## Commands

Run Nx with `pnpm exec nx ...`.

- Nx: `nx show projects`, `nx build @hyper/press-foundation`, `nx run plugins-tooling:lint|test:unit|test:integration`. Nx caches the builds, `test`, `test:node`, `lint-js`, `lint-css` and `package` targets; the targets that run Docker PHP (`plugins-tooling` targets, `lint-php`) are never cached.
- Foundation theme scripts: `pnpm --filter @hyper/press-foundation <script>`, or `pnpm <script>` from `themes/hyperpress-foundation/`.
- uikit theme: `nx build @hyper/press-uikit` assembles `dist/themes/hyperpress-uikit` (internals in the theme's `AGENTS.md`). `nx up|down|smoke|serve @hyper/press-uikit`: `up` builds then starts the stack, `smoke` checks the running site, `serve` is the Vite dev loop with HMR.
- Format: `pnpm fmt` writes and `pnpm fmt:check` verifies (Oxfmt over the whole repo, config `.oxfmtrc.json`; PHP is formatted by PHPCS only). `fmt:check` is outside Nx, so it is not part of `nx run-many`.
- Lint targets (distinct names, never collide): `lint-js` (Oxlint through `@nx/oxlint`, inferred on every project with JS/TS; config `.oxlintrc.json`, warnings only for Foundation legacy code), `lint-css` (Stylelint on `libs/uikit-tokens` and the uikit theme's Sass; Foundation's Sass is not covered), `lint-php` (PHPCS for the uikit theme, WordPress standard plus PHPCompatibilityWP `testVersion 8.3-`, runs in the tooling container) and `lint` (the `plugins-tooling` composer PHPCS for the plugins). Run them all with `nx run-many -t lint-js lint-css lint-php lint`.
- Package: `nx package @hyper/press-uikit` builds and writes `dist/packages/hyperpress-uikit-<version>.zip` (single top-level folder, only manifest-referenced assets, no `.vite-dev`).
- PHP tooling for the Foundation theme and the plugins: from `themes/hyperpress-foundation/` or `plugins/`, `docker compose run --rm php composer lint|test:unit|test:integration`. `vendor/` is git-ignored, so run `composer install` the same way first. The two tooling compose files use the project names `hyperpress-plugins` and `hyperpress-theme`, so their ephemeral database containers and the composer cache volume are shared by anything that uses those names; do not run two copies (for example an old standalone clone) at the same time. Their mounts use the monorepo layout: `plugins/` mounts `themes/hyperpress-foundation` as `/theme`, and the theme mounts `plugins/` as `/plugins`.

## Local WordPress stacks

- Start a site: `cd docker/<hyperalumni|hyperonline> && docker compose --profile local up -d`; stop it with `docker compose --profile local stop`. Ports come from that site's `.env.local`: hyperalumni WordPress 3080 and phpMyAdmin 3090, hyperonline 4080 and 4090.
- `docker/compose.main.yaml` is shared. Each site directory holds only its wrapper `compose.yaml` and its `.env.local`; the other env files (`.env`, `.env.db`, `.env.mariadb`, `.env.phpmyadmin`, `.env.wordpress`, `.env.versions`) are shared in `docker/`. Real env files are git-ignored; commit only the `*.example` templates, and copy them to real files (filling in the values) on a new checkout.
- WP-CLI: `cd docker/<hyperalumni|hyperonline> && docker compose --profile local run --rm wpcli <command>`, e.g. `wpcli --info` or `wpcli search-replace ... --dry-run`. The `wpcli` service reuses the `wordpress` mounts and needs the stack's `db`/`wordpress` containers (the `local` profile); it runs as uid 33:33 and sits in the `tools` profile, so `up` does not start it.
- The project names keep the existing volumes (`hyperalumni_*`, `hyperonline_*`, `hyperuikit_*`). `docker compose down -v` deletes a site's database and WordPress files: fine for local development, but do it on purpose.
- The theme and the plugins are bind-mounted from `themes/hyperpress-foundation/` and `plugins/` (the uikit site mounts `dist/themes/hyperpress-uikit`). Design: the spec's "Docker stack" section.
- Docker Desktop creates a missing bind-mount source as an empty root-owned folder instead of failing, and Compose's `create_host_path: false` does not change that here. After changing a mount path or `THEME_SRC`, look for strays with `find . -maxdepth 4 -type d -empty -user root -not -path '*/node_modules/*'` and remove them with `rmdir`. The theme's integration tests also leave an empty root-owned `themes/hyperpress-foundation/.superpowers`; that one is harmless.

### uikit site

- `cd docker/uikit && docker compose --profile local up -d` (or `nx up @hyper/press-uikit`). WordPress http://localhost:5080, phpMyAdmin 5090 (ports and project name `hyperuikit` come from the git-ignored local env file; copy the committed example on a new checkout). The owner installs WordPress and activates the theme once with wpcli: `docker compose --profile local run --rm wpcli wp <command>` (the `wp` prefix is required except for flags such as `--info`).
- One-command setup: `nx setup @hyper/press-uikit` builds, starts the stack, installs WordPress only if needed, activates the theme and `hyperpress-example`, creates the `hello-uikit-card` demo post if absent, then checks the demo post's permalink and smoke-checks the site; it is idempotent. It needs the shell variables `UIKIT_ADMIN_USER`, `UIKIT_ADMIN_PASSWORD` and `UIKIT_ADMIN_EMAIL` (a missing one aborts before anything starts; the password is never printed or written) and optionally `UIKIT_BASE_URL` (default `http://localhost:$WORDPRESS_EXTERNAL_PORT` when that variable is set, else `http://localhost:5080`; a URL whose port differs from `WORDPRESS_EXTERNAL_PORT` aborts). Fresh clone: `pnpm install`, copy the local env example, set the three variables, run `nx setup @hyper/press-uikit`.
- To test the stack without touching your site, run a scratch project by overriding `COMPOSE_PROJECT_NAME` and the ports in that command's environment (e.g. `COMPOSE_PROJECT_NAME=hyperuikit-fresh WORDPRESS_EXTERNAL_PORT=5180 PHPMYADMIN_EXTERNAL_PORT=5190 UIKIT_BASE_URL=http://localhost:5180`), and remove only it afterwards with `docker compose -p hyperuikit-fresh --profile local down -v`.
- PHP/Composer tooling: from `docker/uikit`, `docker compose run --rm --user "$(id -u):$(id -g)" -e COMPOSER_HOME=/tmp/composer tooling <cmd>`; add `-w /themes/hyperpress-uikit` for the theme's composer scripts (e.g. `composer test:unit`). Always pass `--user` (the service runs as root) and not `--profile tools`.
- Dev loop: use `nx serve @hyper/press-uikit`, not the inferred `vite-*` targets; Vite listens on `0.0.0.0`, so the dev server is reachable from the LAN while it runs: trusted networks only (see the theme's `AGENTS.md`).
- Bind-mount gotcha: `dist/themes/hyperpress-uikit` is mounted into the container. Never `rm -rf` the directory itself; anything that recreates it leaves a stale mount, repaired with `docker compose --profile local up -d --force-recreate wordpress`.

## Git

- One repo: commit from the root with explicit paths. Run `git add <paths>` then `git commit -m "..." -- <paths>`. Never a bare `git commit` and never `git add -A`.
- Follow the commit style in `git log` (`type(scope): lowercase summary`). Commit and push only when asked.
- `master` is published: do not rewrite its history. The Foundation theme keeps its original commit hashes (the FoundationPress lineage).

## Implementation plans

Plans are in `docs/superpowers/plans/`, specs in `docs/superpowers/specs/` (see the plans README).
Commit a plan on its own before coding, tick its checkboxes in the same commit as each task's code,
and update any plan that mentions a file you change. Add a COMPLETED banner when a plan finishes.
Open follow-ups are listed in each plan's "Known follow-ups".
