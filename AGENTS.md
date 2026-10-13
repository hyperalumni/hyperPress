# hyperPress monorepo

pnpm workspace holding the hyperPress WordPress theme(s) and plugins in one git repo (`master`). It was formed from the former `theme` and `plugins` repos, with their history kept. Design: `docs/superpowers/specs/2026-10-07-uikit-nx-wordpress-design.md`.

This file supersedes `../AGENTS.md` (the `hyperPress/` folder file outside this repo), which still describes `theme/` and `plugins/` as separate repos and the `hyperalumni/` and `hyperonline/` Docker stacks that mount them.

## Layout
- `themes/hyperpress-foundation/`: the classic Foundation theme (gulp/webpack). Read its `AGENTS.md` before working there.
- `plugins/`: the `hyperpress-*` plugins plus shared PHP tooling (`composer.json`, phpcs, phpunit, `bin/`, `tests/`). Read its `AGENTS.md` before working there.
- `docs/superpowers/{specs,plans,audit}/`: all specs, plans and audits for the workspace (`uikit-*`), theme (`theme-*`) and plugins (`plugins-*`).
- `docker/`: shared Compose file and per-site wrappers for the local WordPress sites `hyperalumni` and `hyperonline`.
- Planned, not built yet (see the spec): Nx, `themes/hyperpress-uikit/`, `libs/`.

## Environment
- Node comes from nvm and is not on the default PATH. Load it first in each shell: `source ~/.nvm/nvm.sh && nvm use` (root `.nvmrc`, Node 26.11.1).
- pnpm only (enforced by `preinstall`; no npm/yarn). The root `devEngines` pins pnpm 12.11.2. Run `pnpm install` once at the root.
- Theme scripts: `pnpm --filter @hyper/press <script>`, or run `pnpm <script>` from `themes/hyperpress-foundation/`.
- PHP tooling runs in Docker only, never composer on the host: from the theme or plugins directory, `docker compose run --rm php composer lint|test:unit|test:integration`. `vendor/` is git-ignored, so run `composer install` the same way first. The two tooling compose files set their own project names (`hyperpress-plugins`, `hyperpress-theme`), the same as the standalone repos, so their ephemeral containers and the composer cache volume are shared with those repos; do not run both copies at the same time. Their mounts use the monorepo layout: `plugins/` mounts `themes/hyperpress-foundation` as `/theme`, and the theme mounts `plugins/` as `/plugins`.
- `.env*` files are git-ignored. Do not read or print them.

## Local WordPress stacks
- Start a site: `cd docker/<hyperalumni|hyperonline> && docker compose --profile local up -d`; stop it with `docker compose --profile local stop`. Ports come from that site's `.env.local`: hyperalumni WordPress 3080 and phpMyAdmin 3090, hyperonline 4080 and 4090.
- `docker/compose.main.yaml` is shared. Each site directory holds only its wrapper `compose.yaml` and its `.env.local`; the other env files (`.env`, `.env.db`, `.env.mariadb`, `.env.phpmyadmin`, `.env.wordpress`, `.env.versions`) are shared in `docker/`. Real env files are git-ignored; commit only the `*.example` templates, and copy them to real files (filling in the values) on a new checkout.
- WP-CLI: `cd docker/<hyperalumni|hyperonline> && docker compose --profile local run --rm wpcli <command>`, e.g. `wpcli --info` or `wpcli search-replace ... --dry-run`. The `wpcli` service reuses the `wordpress` mounts and needs the stack's `db`/`wordpress` containers (the `local` profile); it runs as uid 33:33 and sits in the `tools` profile, so `up` does not start it.
- The project names keep the existing volumes (`hyperalumni_*`, `hyperonline_*`). `docker compose down -v` deletes a site's database and WordPress files: fine for local development, but do it on purpose.
- The theme and the 16 plugins are bind-mounted from `themes/hyperpress-foundation/` and `plugins/`. Design: the spec's "Docker stack" section.
- Docker Desktop creates a missing bind-mount source as an empty root-owned folder instead of failing, and Compose's `create_host_path: false` does not change that here. After changing a mount path or `THEME_SRC`, look for strays with `find . -maxdepth 4 -type d -empty -user root -not -path '*/node_modules/*'` and remove them with `rmdir`. The theme's integration tests also leave an empty root-owned `themes/hyperpress-foundation/.superpowers`; that one is harmless.

## Git
- One repo: commit from the root with explicit paths. Run `git add <paths>` then `git commit -m "..." -- <paths>`. Never a bare `git commit` and never `git add -A`.
- Follow the commit style in `git log`. Commit and push only when asked.
- The theme's history keeps its original hashes (the FoundationPress lineage). Do not rewrite it.

## Implementation plans
Plans are in `docs/superpowers/plans/`, specs in `docs/superpowers/specs/` (see the plans README).
Commit a plan on its own before coding, tick its checkboxes in the same commit as each task's code,
and update any plan that mentions a file you change. Add a COMPLETED banner when a plan finishes.
