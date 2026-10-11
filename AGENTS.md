# hyperPress monorepo

pnpm workspace holding the hyperPress WordPress theme(s) and plugins in one git repo (`master`). It was formed from the former `theme` and `plugins` repos, with their history kept. Design: `docs/superpowers/specs/2026-10-07-uikit-nx-wordpress-design.md`.

This file supersedes `../AGENTS.md` (the `hyperPress/` folder file outside this repo), which still describes `theme/` and `plugins/` as separate repos and the `hyperalumni/` and `hyperonline/` Docker stacks that mount them.

## Layout
- `themes/hyperpress-foundation/`: the classic Foundation theme (gulp/webpack). Read its `AGENTS.md` before working there.
- `plugins/`: the `hyperpress-*` plugins plus shared PHP tooling (`composer.json`, phpcs, phpunit, `bin/`, `tests/`). Read its `AGENTS.md` before working there.
- `docs/superpowers/{specs,plans,audit}/`: all specs, plans and audits for the workspace (`uikit-*`), theme (`theme-*`) and plugins (`plugins-*`).
- Planned, not built yet (see the spec): Nx, `themes/hyperpress-uikit/`, `libs/`, `docker/`.

## Environment
- Node comes from nvm and is not on the default PATH. Load it first in each shell: `source ~/.nvm/nvm.sh && nvm use` (root `.nvmrc`, Node 26.11.1).
- pnpm only (enforced by `preinstall`; no npm/yarn). The root `devEngines` pins pnpm 12.11.2. Run `pnpm install` once at the root.
- Theme scripts: `pnpm --filter @hyper/press <script>`, or run `pnpm <script>` from `themes/hyperpress-foundation/`.
- PHP tooling runs in Docker only, never composer on the host: from the theme or plugins directory, `docker compose run --rm php composer lint|test:unit|test:integration`.
- `.env*` files are git-ignored. Do not read or print them.

## Git
- One repo: commit from the root with explicit paths. Run `git add <paths>` then `git commit -m "..." -- <paths>`. Never a bare `git commit` and never `git add -A`.
- Follow the commit style in `git log`. Commit and push only when asked.
- The theme's history keeps its original hashes (the FoundationPress lineage). Do not rewrite it.

## Implementation plans
Plans are in `docs/superpowers/plans/`, specs in `docs/superpowers/specs/` (see the plans README).
Commit a plan on its own before coding, tick its checkboxes in the same commit as each task's code,
and update any plan that mentions a file you change. Add a COMPLETED banner when a plan finishes.
