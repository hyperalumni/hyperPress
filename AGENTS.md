# hyperPress monorepo

pnpm workspace holding the hyperPress WordPress theme(s) and plugins in one git repo (`master`). It was formed from the former `theme` and `plugins` repos, with their history kept. Design: `docs/superpowers/specs/2026-10-07-uikit-nx-wordpress-design.md`.

This file supersedes `../AGENTS.md` (the `hyperPress/` folder file outside this repo), which still describes `theme/` and `plugins/` as separate repos and the `hyperalumni/` and `hyperonline/` Docker stacks that mount them.

## Layout

- `themes/hyperpress-foundation/`: the classic Foundation theme (gulp/webpack). Read its `AGENTS.md` before working there.
- `plugins/`: the `hyperpress-*` plugins plus shared PHP tooling (`composer.json`, phpcs, phpunit, `bin/`, `tests/`). Read its `AGENTS.md` before working there.
- `docs/superpowers/{specs,plans,audit}/`: all specs, plans and audits for the workspace (`uikit-*`), theme (`theme-*`) and plugins (`plugins-*`).
- `docker/`: shared Compose file (with the `tooling` PHP/Composer service) and per-site wrappers for the local WordPress sites `hyperalumni`, `hyperonline` and `uikit`.
- `dist/` rule (both git-ignored): Foundation keeps its build output in-theme (`themes/hyperpress-foundation/dist`, served from its source folder); projects with a non-deployable source folder, such as the uikit theme, build an assembled artifact to the repo-level `dist/<type>/<name>` (e.g. `dist/themes/hyperpress-uikit`) that compose mounts.
- `themes/hyperpress-uikit/`: the experimental Timber + UIkit theme (PHP `Assets` class, Twig views, Vite entry).
- `plugins/hyperpress-example/`: stub plugin that builds the shared Sass (`libs/uikit-tokens`) into its own `dist/uikit.css` and renders `[hp_uikit_card]` with UIkit classes; it proves a plugin can consume the lib with Nx tracking the dependency.
- `hyperpress-example` is mounted (inactive) on all three sites; run `nx build hyperpress-example` before activating it anywhere other than the uikit site.
- `libs/uikit-tokens/` (Sass: UIkit variable overrides and component imports) and `libs/ui/` (TypeScript: UIkit init, tested with Vitest and happy-dom).
- Nx is set up (root `nx.json`; stages 0-7 of the spec are done, including the `@nx/vite/plugin` inferred `vite-*` targets, the lint and format gates, packaging and `setup`). The experiment write-up is `docs/superpowers/audit/2026-10-16-uikit-nx-oxc-verdict.md`. Open follow-ups: merging the stacked stage branches, the production PHP version, and the plans' deferred minors (for example the Sass `@import` to `@use` move before Dart Sass 3).

## Environment

- Node comes from nvm and is not on the default PATH. Load it first in each shell: `source ~/.nvm/nvm.sh && nvm use && export PATH="$HOME/.local/share/pnpm/bin:$PATH"` (root `.nvmrc`, Node 26.12.0).
- pnpm only (enforced by `preinstall`; no npm/yarn). The root `devEngines` pins pnpm 12.12.1. Run `pnpm install` once at the root.
- Shared dependency versions (Nx and `@nx/*`) are pinned in the `catalog:` in `pnpm-workspace.yaml`; use `"catalog:"` in package.json, and bump them there.
- Nx project config (targets, cache, tags) lives in each project's `project.json`; `package.json` holds scripts and dependencies only.
- Nx commands (run with `pnpm exec`): `nx show projects`, `nx build @hyper/press-foundation`, `nx run plugins-tooling:lint|test:unit|test:integration`. Nx caches the builds, `test`, `test:node`, `lint-js`, `lint-css` and `package` targets; the targets that run Docker PHP (`plugins-tooling` targets, `lint-php`) are never cached.
- uikit theme: `pnpm exec nx build @hyper/press-uikit` assembles `dist/themes/hyperpress-uikit` (PHP, Twig, Vite output, Composer `vendor/`). `nx up|down|smoke|serve @hyper/press-uikit`: `up` builds then starts the stack, `smoke` checks the running site, `serve` is the Vite dev loop with HMR (it writes a `.vite-dev` marker while running).
- Format: `pnpm fmt` writes and `pnpm fmt:check` verifies (Oxfmt over the whole repo, config `.oxfmtrc.json`; PHP is formatted by PHPCS only, not Oxfmt). `fmt:check` is outside Nx, so it is not part of `nx run-many`.
- Lint targets (distinct names, never collide): `lint-js` (Oxlint through `@nx/oxlint`, inferred on every project with JS/TS; config `.oxlintrc.json`, warnings only for Foundation legacy code), `lint-css` (Stylelint on `libs/uikit-tokens` and the uikit theme's Sass; Foundation's Sass is not covered), `lint-php` (PHPCS for the uikit theme, WordPress standard plus PHPCompatibilityWP `testVersion 8.3-`, runs in the tooling container), and `lint` (the `plugins-tooling` composer PHPCS for the plugins). Run them all with `pnpm exec nx run-many -t lint-js lint-css lint-php lint`.
- Package: `pnpm exec nx package @hyper/press-uikit` builds and writes `dist/packages/hyperpress-uikit-<version>.zip` (single top-level folder, only manifest-referenced assets, no `.vite-dev`).
- Theme scripts: `pnpm --filter @hyper/press-foundation <script>`, or run `pnpm <script>` from `themes/hyperpress-foundation/`.
- PHP tooling runs in Docker only, never composer on the host: from the theme or plugins directory, `docker compose run --rm php composer lint|test:unit|test:integration`. `vendor/` is git-ignored, so run `composer install` the same way first. The two tooling compose files set their own project names (`hyperpress-plugins`, `hyperpress-theme`), the same as the standalone repos, so their ephemeral containers and the composer cache volume are shared with those repos; do not run both copies at the same time. Their mounts use the monorepo layout: `plugins/` mounts `themes/hyperpress-foundation` as `/theme`, and the theme mounts `plugins/` as `/plugins`.
- `.env*` files are git-ignored. Do not read or print them.

## Local WordPress stacks

- Start a site: `cd docker/<hyperalumni|hyperonline> && docker compose --profile local up -d`; stop it with `docker compose --profile local stop`. Ports come from that site's `.env.local`: hyperalumni WordPress 3080 and phpMyAdmin 3090, hyperonline 4080 and 4090.
- `docker/compose.main.yaml` is shared. Each site directory holds only its wrapper `compose.yaml` and its `.env.local`; the other env files (`.env`, `.env.db`, `.env.mariadb`, `.env.phpmyadmin`, `.env.wordpress`, `.env.versions`) are shared in `docker/`. Real env files are git-ignored; commit only the `*.example` templates, and copy them to real files (filling in the values) on a new checkout.
- WP-CLI: `cd docker/<hyperalumni|hyperonline> && docker compose --profile local run --rm wpcli <command>`, e.g. `wpcli --info` or `wpcli search-replace ... --dry-run`. The `wpcli` service reuses the `wordpress` mounts and needs the stack's `db`/`wordpress` containers (the `local` profile); it runs as uid 33:33 and sits in the `tools` profile, so `up` does not start it.
- The project names keep the existing volumes (`hyperalumni_*`, `hyperonline_*`). `docker compose down -v` deletes a site's database and WordPress files: fine for local development, but do it on purpose.
- The theme and the 16 plugins are bind-mounted from `themes/hyperpress-foundation/` and `plugins/`. Design: the spec's "Docker stack" section.
- Docker Desktop creates a missing bind-mount source as an empty root-owned folder instead of failing, and Compose's `create_host_path: false` does not change that here. After changing a mount path or `THEME_SRC`, look for strays with `find . -maxdepth 4 -type d -empty -user root -not -path '*/node_modules/*'` and remove them with `rmdir`. The theme's integration tests also leave an empty root-owned `themes/hyperpress-foundation/.superpowers`; that one is harmless.

### uikit site

- `cd docker/uikit && docker compose --profile local up -d` (or `nx up @hyper/press-uikit`). WordPress http://localhost:5080, phpMyAdmin 5090 (ports and project name `hyperuikit` come from the git-ignored local env file; copy the committed example on a new checkout). The owner installs WordPress and activates the theme once with wpcli: `docker compose --profile local run --rm wpcli wp <command>` (the `wp` prefix is required except for flags such as `--info`).
- One-command setup: `pnpm exec nx setup @hyper/press-uikit` builds, starts the stack, installs WordPress only if needed, activates the theme and `hyperpress-example`, creates the `hello-uikit-card` demo post if absent, then checks the demo post's permalink and smoke-checks the site; it is idempotent. It needs the shell variables `UIKIT_ADMIN_USER`, `UIKIT_ADMIN_PASSWORD` and `UIKIT_ADMIN_EMAIL` (a missing one aborts before anything starts; the password is never printed or written) and optionally `UIKIT_BASE_URL` (default `http://localhost:$WORDPRESS_EXTERNAL_PORT` when that variable is set, else `http://localhost:5080`; a URL whose port differs from `WORDPRESS_EXTERNAL_PORT` aborts). Fresh clone: `pnpm install`, copy the local env example, set the three variables, run `pnpm exec nx setup @hyper/press-uikit`.
- To test the stack without touching your site, run a scratch project by overriding `COMPOSE_PROJECT_NAME` and the ports in that command's environment (e.g. `COMPOSE_PROJECT_NAME=hyperuikit-fresh WORDPRESS_EXTERNAL_PORT=5180 PHPMYADMIN_EXTERNAL_PORT=5190 UIKIT_BASE_URL=http://localhost:5180`), and remove only it afterwards with `docker compose -p hyperuikit-fresh --profile local down -v`.
- PHP/Composer tooling (never on the host): from `docker/uikit`, `docker compose run --rm --user "$(id -u):$(id -g)" -e COMPOSER_HOME=/tmp/composer tooling <cmd>`; add `-w /themes/hyperpress-uikit` for the theme's composer scripts (e.g. `composer test:unit`). Always pass `--user` (the service runs as root) and not `--profile tools`.
- Dev loop: use `nx serve @hyper/press-uikit`, not the inferred `vite-serve`/`vite-dev`/`vite-preview` targets (they do not write the `.vite-dev` marker, so WordPress keeps serving the production assets). Vite binds `0.0.0.0` (`host: true`) so the WordPress container can reach it via `host.docker.internal`; the dev server is therefore reachable from the LAN while it runs, so use it only on trusted networks.
- `UIKIT_WP_ORIGIN` is a host shell environment variable read by the theme's Vite config (CORS origin of the site, default `http://localhost:5080`); set it if you change the site port.
- Bind-mount gotcha: `dist/themes/hyperpress-uikit` is mounted into the container. Never `rm -rf` the directory itself; anything that recreates it leaves a stale mount, repaired with `docker compose --profile local up -d --force-recreate wordpress`.

## Git

- One repo: commit from the root with explicit paths. Run `git add <paths>` then `git commit -m "..." -- <paths>`. Never a bare `git commit` and never `git add -A`.
- Follow the commit style in `git log`. Commit and push only when asked.
- The theme's history keeps its original hashes (the FoundationPress lineage). Do not rewrite it.

## Implementation plans

Plans are in `docs/superpowers/plans/`, specs in `docs/superpowers/specs/` (see the plans README).
Commit a plan on its own before coding, tick its checkboxes in the same commit as each task's code,
and update any plan that mentions a file you change. Add a COMPLETED banner when a plan finishes.
