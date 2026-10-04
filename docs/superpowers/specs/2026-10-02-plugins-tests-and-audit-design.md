# hyperPress plugins: tests and audit (Spec 2 of 2)

Date: 2026-10-02
Scope: the 12 original plugins in `plugins/` plus `hyperpress-shortcodes` and `hyperpress-media` (created by the theme extraction). Theme tests and audit are specified in the theme spec.
Companion: Spec 1 (convention and shared code). Spec 1's sequencing puts this
spec's test infrastructure first, so the refactor is guarded by tests.

## Goal

1. Give `plugins/` a test and lint toolchain that runs in Docker, since the host
   has only PHP 8.5 and docker.
2. Encode Spec 1's convention as automated contract tests.
3. Audit the plugins for security and correctness defects, report them ranked,
   and fix each one test-first.

## 1. Toolchain

Files at the `plugins/` root:

| File | Purpose |
|---|---|
| `composer.json` | Dev dependencies: `phpunit/phpunit`, `wp-phpunit/wp-phpunit`, `yoast/phpunit-polyfills`, `wp-coding-standards/wpcs`, `phpcompatibility/phpcompatibility-wp`, `dealerdirect/phpcodesniffer-composer-installer`. Also the shared PSR-4 map for tests. |
| `phpcs.xml.dist` | WordPress ruleset plus PHPCompatibilityWP. Excludes the `class-*.php` file-naming sniff (Spec 1 uses PSR-4 names) and the vendored `TimeCircles.js` assets. |
| `phpunit.xml.dist` | Two suites: `unit` (no WordPress) and `integration` (WordPress test library). |
| `docker-compose.yml` | Services `php` (PHP CLI plus composer) and `db` (MariaDB for the integration suite). |
| `tests/bootstrap-unit.php`, `tests/bootstrap-integration.php` | Suite bootstraps. The integration bootstrap loads `hyperpress-utils` first, then the plugin under test. |

Entry points, all run through compose so nothing is needed on the host:

- `docker compose run --rm php composer install`
- `docker compose run --rm php composer lint` (phpcs plus `php -l` on every file)
- `docker compose run --rm php composer test:unit`
- `docker compose run --rm php composer test:integration` (brings up `db`)

The image runs PHP 8.5, matching production. The plugin headers keep
`Requires PHP: 8.3` as the floor. `phpcs` is non-gating until Spec 1's
implementation finishes (existing code predates the new ruleset); `php -l`
gates from the start.

`meta-box-aio` is not installed in the test environment. Meta box definitions
are registered through the `rwmb_meta_boxes` filter, so tests call that filter
directly and inspect the returned arrays. Code that calls `rwmb_meta()`
is tested with a small stub function.

CI is not in scope (no CI exists today). The compose entry points are
CI-ready when wanted.

## 2. Test suites

### Unit suite (no WordPress)

Covers the pure logic in `hyperpress-utils`:
- Label generation from singular and plural names.
- Capability-set generation for a CPT key.
- Definition defaults and overrides in `PostTypeRegistrar` and `TaxonomyRegistrar`
  (array in, array out, before `register_post_type` is called).
- Breadcrumb and banner argument assembly, including the `hyperpress_banner_args` filter.
- Key validation: a registered key longer than 20 characters or containing
  uppercase or spaces is rejected.

### Integration suite (WordPress test library)

Contract tests, one file per concern, run against every plugin so a new plugin
is covered without new test code:

| Contract | Assertion |
|---|---|
| Key prefix | Every CPT and taxonomy registered by a hyperPress plugin starts with `hyper_` and is 20 characters or fewer. |
| Public URLs | Each public CPT's rewrite slug is singular and its archive slug plural, matching the table in Spec 1. |
| Capabilities | Each CPT uses its own capability set with `map_meta_cap`. After activation, Administrator and Editor hold every capability, and Subscriber holds none. |
| Season and program | `hyper_season` and `hyper_program` attach to the CPTs named in their theme mods. The default is `[]`: with no theme mod set they attach to nothing, so the built-in `post` type never uses them. |
| Taxonomy attachment | `hyper_district` and `hyper_week` attach to `hyper_competition`. `hyper_tier` attaches to `hyper_sponsor`. |
| Identifier convention | Every meta key, meta box ID, customizer setting and control ID, and shortcode name matches `^hyperpress_[a-z0-9_]+$`. Control IDs equal their setting IDs. |
| Hard-coded keys | No meta box definition or query names an old CPT or taxonomy key (`sponsor`, `robot`, `tier`, and the rest of Spec 1 section 2). |
| Dependencies | Each plugin's `Requires Plugins` header equals the table in Spec 1 section 1. |

Behavior tests for the shared classes, through real WordPress calls:
- `PostTypeRegistrar` registers a CPT whose arguments match its definition.
- `Capabilities::grant()` is idempotent, and the version-gated `init` check
  grants capabilities when the stored version is stale.
- `Breadcrumbs::build()` produces the specified trail from a context array (unit),
  and `Breadcrumbs` and `Banner` attach their hooks and assemble real requests
  (integration).
- Newsletter's season-aware banner title and per-post color still work through
  the `hyperpress_banner_args` filter. This is the regression test for the one
  banner that is not a pure copy.

### Header lint

plugins-01-test-toolchain unit test parses the header block of each `hyperpress-*/hyperpress-*.php` and
asserts the canonical field set from Spec 1 section 1: no `GitHub Plugin URI`,
a non-empty Description that names the plugin's real purpose, Author, License,
License URI, and the exact `Requires Plugins` list. It also asserts the main
file is named after the folder.

## 3. Audit

The audit produces a report first, then fixes. Nothing is fixed before its
finding is reviewed.

### Method

1. Read every PHP file in the 14 plugins with the checklist below. Each
   finding is recorded with file and line, what is wrong, a severity, and a
   reproduction (a failing test, or a short description when a test is not
   practical).
2. Every finding is verified directly in the source before it enters the report.
   The earlier exploration passes contradicted each other on some points, so
   no finding is accepted from a summary alone.
3. The report goes to `plugins/docs/superpowers/audit/2026-10-02-audit.md`,
   ranked by severity. The user reviews and approves which findings to fix.
4. Each approved finding gets a failing test first, then the fix, then a green
   run. One commit per finding, test and fix together.

### Checklist

| Area | What to check |
|---|---|
| Output escaping | Every echo of shortcode attributes, post meta, option and theme-mod values, widget `$args` and `$instance` is escaped for its context. |
| Shortcodes | Use of `extract()` (`hyperpress-countdown/lib/shortcodes/countdown.php:14`, and the show/hide shortcode), attribute sanitizing, nested content handling. |
| Input sanitizing | Meta box fields without a `sanitize_callback`. Known: `sponsor_description` (`hyperpress-sponsor/lib/meta-boxes.php:46-52`) and all eight competition fields. Customizer settings without `sanitize_callback`. |
| Capabilities and nonces | Any save handler, AJAX action or admin action without a capability check and nonce. Not yet examined. |
| Queries | Direct `$wpdb` use without `prepare()`, unbounded `posts_per_page => -1`, meta queries on unindexed keys. |
| Data exposure | CPTs with `public => true` that should not be, including Countdown. `show_in_rest` on content that should stay private. |
| Correctness | The `competition` meta box's robot query, newsletter's mismatched customizer IDs (fixed by Spec 1), dead code, unreachable branches. |
| Assets | Enqueue handles, versions, unused files, vendored library versions (TimeCircles 1.5.3). |
| Dependencies | `Requires Plugins` versus real usage (covered by the header lint). |

Known candidates from exploration, all still to be verified in the audit:
sponsor description sanitizing, countdown `extract()` and output escaping, the
show/hide shortcode's `extract()` calls, and widget `$args` echoes in
`hyperpress-socials/lib/widgets/display-socials.php` (likely fine, since
WordPress supplies those values). `hyperpress-utils` usage by other plugins
was not established and gets checked here.

### Out of scope

Features, performance work beyond unbounded queries, and anything in `theme/`.

## 4. Sequencing

1. Toolchain (section 1) with a trivially passing unit test, to prove the
   Docker loop works.
2. Header lint and contract tests (section 2), written against Spec 1's
   convention. They fail on the current code, which is the point.
3. Spec 1 implementation, driving those tests green.
4. Audit report (section 3), review, then test-first fixes.

The audit runs after the refactor, so findings refer to the final file layout
and no fix is written twice. Findings that are independent of the refactor and
severe can be pulled forward on request.

## 5. Success criteria

- `composer lint`, `composer test:unit` and `composer test:integration` pass in
  Docker from a clean checkout.
- Every contract in section 2 is an automated test, and each fails when its
  convention is violated. This is checked by temporarily breaking one case per contract.
- The audit report exists, every finding has been verified in source, and the
  user has approved the fix list.
- Every approved fix has a test that failed before the fix and passes after.

## 6. Risks and open assumptions

- **PHP 8.5 with the WordPress test library.** WordPress core and `wp-phpunit`
  may emit PHP 8.5 deprecations. The integration bootstrap must not fail on
  deprecations that originate in `vendor/`.
- **Integration suite cost.** The WordPress test library needs a database and
  a slower bootstrap. The unit suite stays fast so day-to-day runs do not pay it.
- **Meta Box stubbed.** Tests check the arrays passed to `rwmb_meta_boxes`, not
  the rendered admin UI. UI behavior is not covered.
- **No browser tests.** Customizer controls, shortcodes rendered on the front
  end and the countdown JavaScript are covered only at the PHP output level.
