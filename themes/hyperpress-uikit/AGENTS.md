# hyperPress uikit theme

Experimental Timber 2 + UIkit theme: UIkit is compiled from Sass source and the assets are built with Vite.
Requires PHP >=8.3 (composer platform pin 8.3.0) and WP 6.9 (PHPCS `minimum_wp_version`), like the rest of the repo. Text domain `hyperpress-uikit`.

Part of the hyperPress monorepo (`themes/hyperpress-uikit/`); the root `../../AGENTS.md` also applies (environment, Docker stacks, `dist/` rule, commit rules).

## Build chain (Nx, `project.json`)

- `copy-theme` (`bin/copy-theme.mjs`) copies `style.css`, `functions.php`, `index.php`, `inc/`, `views/`, `composer.json` and `composer.lock` into `dist/themes/hyperpress-uikit`. `vite-build` writes `.vite` and `assets` there (Sass options: `loadPaths`, `quietDeps`, silenced `import`/`global-builtin`/`color-functions`). `composer-install` runs `composer install --no-dev` in the Docker tooling container and writes `vendor`.
- `build` is a cached `nx:noop` (`outputs: []`) that depends on those three and on `^build`. The cached outputs are disjoint on purpose, so one target never restores over another's files.
- Never recreate the directory `dist/themes/hyperpress-uikit` (it is a bind mount); the root `../../AGENTS.md` has the repair command.
- Other targets: `up`/`down` start/stop the stack, `smoke` checks the running site, `setup` is the one-command install, `serve` is the dev loop, `package` builds the zip, `test` runs Vitest, `lint-css`/`lint-php` lint Sass/PHP (`lint-js` is inferred).
- `@nx/vite/plugin` also infers `vite-*` targets; use the explicit ones above.

## `inc/Assets.php`

- Public API: `isDev()`, `devServer()`, `entry( $key )`, `notice()`, `register()`, `enqueue()`, static `probeUrl()`. `functions.php` wires it with `( new Assets( … ) )->register()`.
- Production: `.vite/manifest.json` entry `src/main.ts`; `entry()` returns the script plus the CSS of the entry and its imported chunks.
- Dev mode: constant `VITE_DEV` is `true`, or the `.vite-dev` marker (JSON `{browser, internal}`, written by `bin/serve.mjs`) exists and `<internal>/@vite/client` answers (status 200-399, 0.5 s timeout). A stale marker falls back to the production assets.
- Handles start with `hp-uikit-`; a `script_loader_tag` filter adds `type="module"` to them.
- A missing manifest or `vendor/` shows an admin notice, never a fatal. `index.php` renders a minimal 200 page when Timber is missing (a 404 stays 404).

## PHP rules

- No PHP 8.4+ syntax (e.g. write `( new Assets( … ) )->register()` with parentheses).
- `functions.php` calls `add_theme_support( 'html5', array( 'script', 'style' ) )` so WordPress does not emit a second `type` attribute on module scripts.
- Timber 2 emits the head through `{{ function('wp_head') }}` in `views/base.twig`.
- `views/index.twig` prints `post.content`, not `post.preview` (which strips shortcodes).

## Tests and lint

- PHPUnit and PHPCS run in the tooling container from `docker/uikit`: use the tooling invocation in the root `../../AGENTS.md` (uikit site section) with `-w /themes/hyperpress-uikit` and `composer test:unit` or `composer lint`. `composer lint` is `php -l` plus PHPCS.
- `test` is Vitest for `bin/package.mjs` (`tests/package.test.ts`); `pnpm --filter @hyper/press-uikit typecheck` runs `tsc`.
- `lint-php` is PHPCS (`phpcs.xml.dist`): WordPress standard plus PHPCompatibilityWP `testVersion 8.3-`, text domain `hyperpress-uikit`, prefixes `HyperPress\Uikit` / `hp_uikit`.
- PHPCS relaxations: no mandatory docblock presence; PSR-4 file names and camelCase methods/variables; `EnqueuedResourceParameters.MissingVersion` off (Vite hashes assets); `AlternativeFunctions` and `UnusedFunctionParameter` skipped under `tests/`.
- `lint-css` is Stylelint on `src/**/*.scss` with `stylelint-config-standard-scss` (root `stylelint.config.mjs`), no relaxations. `lint-js` is Oxlint.

## Dev loop

- `nx serve @hyper/press-uikit` runs `bin/serve.mjs`: Vite on port 5173 (`strictPort`, `host: true`, `allowedHosts` includes `host.docker.internal`). The WordPress container reaches it as `http://host.docker.internal:5173`, the browser as `http://localhost:5173`.
- CORS origin comes from the host env var `UIKIT_WP_ORIGIN` (default `http://localhost:5080`); set it if you change the site port.
- It writes the `.vite-dev` marker once listening and removes it on exit or SIGINT/SIGTERM/SIGHUP. It watches `functions.php`, `index.php`, `style.css`, `inc/` and `views/`, re-runs `copy-theme` and sends a full reload. Sass and TS changes hot-reload.
- The dev server is reachable from the LAN while it runs: see the root `../../AGENTS.md`.

## Packaging

- `nx package` runs `bin/package.mjs` (exports `collectFiles`, `createZip`, `zipName`) on `dist/themes/hyperpress-uikit` and writes `dist/packages/hyperpress-uikit-<version>.zip` (version from the `style.css` header).
- The zip has one top-level folder and only manifest-referenced files under `assets/`; it excludes `.vite-dev`, the top-level `tests/`, `phpunit.xml.dist`, `phpcs.xml.dist` and `.git*`. Bytes are deterministic (fixed zip epoch, sorted entries). It fails if a manifest-referenced asset is missing.
- The target's cache inputs are the build tasks' output files (`dependentTasksOutputFiles`, transitive) because `dist/` is git-ignored; keep that when editing the target.

## Libs

- `libs/uikit-tokens`: Sass entry `src/index.scss` imports the variable overrides first, then `uikit/src/scss/variables` and `mixins`, then only the used components; `build` writes `dist/libs/uikit-tokens/index.css`.
- `libs/ui`: `initUikit()` registers the UIkit Icons plugin once (a second call is a no-op); tests use Vitest with happy-dom. The theme's `src/main.ts` calls it.
