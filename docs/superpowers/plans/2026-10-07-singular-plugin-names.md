# Singular plugin names Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Rename `hyperpress-socials` to `hyperpress-social` and `hyperpress-shortcodes` to `hyperpress-shortcode`, so every plugin folder name is singular.

**Architecture:** A mechanical rename, done with `git mv` so history follows, one plugin per task. Identifiers that embed the plural name follow the `hyperpress_{plugin}_{field}` convention and are renamed with no compatibility shims, as in the 2026-10-02 convention spec ("nothing here is live").

**Tech Stack:** PHP 8.3, WordPress 6.9, PHPUnit, phpcs, Docker. Everything runs through `docker compose run --rm php ...` from `plugins/` or `theme/`.

**Spec:** Decisions made in conversation on 2026-10-07 (recorded below). Conventions: `docs/superpowers/specs/2026-10-02-plugins-convention-and-shared-code-design.md`.

## Decisions

- Folder, main file, plugin slug: `hyperpress-social`, `hyperpress-shortcode`. Header `Plugin Name`: `hyperPress Social`, `hyperPress Shortcode`.
- Namespaces: `HyperPress\Social`, `HyperPress\Shortcode`. The inner `Shortcodes\` sub-namespace/folder, feature names in test file names, and user-facing display labels stay as they are.
- Widget class `DisplaySocialsWidget` becomes `DisplaySocialWidget`.
- Identifiers that embed the plural plugin name are renamed, no shims: theme mods `hyperpress_socials_*` become `hyperpress_social_*`; the widget base id `hyperpress_socials` becomes `hyperpress_social` (saved option `widget_hyperpress_social`); the hooks `hyperpress_shortcodes_now` and `hyperpress_shortcodes_ordinals` become `hyperpress_shortcode_now` and `hyperpress_shortcode_ordinals`; any Customizer section/control ids of the form `*socials*` that are plugin identifiers follow the same rule.
- Unchanged: the five shortcode tags (`banner`, `raw`, `emphasis`, `date_distance`, `time_restrict`); theme-owned sidebar ids such as `socials` and `footer-socials`; historical plans and specs (they get a dated amendment note, not a rewrite).
- Consequence for the dev sites: WordPress deactivates a plugin whose folder moved, and saved values under the old theme-mod keys and widget option are not read any more. The Docker mounts are updated here; recreating the containers, reactivating the plugins and re-entering or copying the old values is a separate step for the site owner.

## Global Constraints

- Every plugin header keeps Version `2026.0.0`, Requires at least `6.9`, Requires PHP `8.3`, Text Domain `hyperpress`. `Requires Plugins` values are unchanged (`socials`: none; `shortcodes`: none; nothing depends on either).
- Main file is named after its folder (`hyperpress-social/hyperpress-social.php`).
- Use `git mv` for moves. Never a bare `git commit`: `git add <explicit paths>` (include deletions/renames) then `git commit -m "..." -- <explicit paths>`. Never stage `compose.yaml` or `.superpowers/`.
- Tooling only in Docker; a task is done when `composer test:integration`, `composer test:unit` and `composer lint` (exit 0) pass in each repo it changed. The plugin tests load every plugin from `tests/src/Support/Convention.php`, so the list there must match the folders at every commit.
- After each task, `grep -rn` (excluding `vendor`, `node_modules`, `.git`, `.superpowers`, `docs/superpowers`) for the old folder slug, old namespace and old identifiers must show no code hits.

## Review Focus

- **Stale references that fail silently:** `class_exists( '\HyperPress\Socials\...' )` checks in the theme return false after a rename, so the footer socials would quietly disappear instead of failing. Tests must prove the theme still renders the socials widget and links.
- **Widget registration:** the renamed widget base id must match wherever the theme/tests read `widget_hyperpress_social` and `the_widget()` class names.
- **Identifier rename completeness:** every one of the ~13 theme-mod keys (`github`, `twitter`, `contact`, `youtube`, `tiktok`, `thebluealliance`, `snapchat`, `instagram`, `firstinspires`, `facebook`, `discord`, `add_calendar`, ...) is renamed consistently in the Customizer, the links class and the tests.
- **Convention tests:** `Convention::PLUGINS`, `LOAD_ORDER`, `REQUIRES_PLUGINS`, header tests and `IdentifierConventionTest` pass with the new names.
- **Docker mounts:** `hyperalumni` and `hyperonline` parse with `docker compose --profile local config` and mount the renamed folders.

---

### Task 1: Rename hyperpress-socials to hyperpress-social (plugins repo)

**Files:**
- Move: `hyperpress-socials/` to `hyperpress-social/`, `hyperpress-social/hyperpress-socials.php` to `hyperpress-social/hyperpress-social.php`; `src/Widgets/DisplaySocialsWidget.php` to `src/Widgets/DisplaySocialWidget.php`
- Modify: everything inside the plugin (namespace `HyperPress\Social`, autoload prefix, boot class reference, header `Plugin Name`, identifiers per Decisions); `tests/src/Support/Convention.php` (`PLUGINS`, `LOAD_ORDER`, `REQUIRES_PLUGINS`); the plugin tests that reference the old namespace, widget class or identifiers (`tests/integration/Socials*Test.php`, `WidgetTitleEscapingTest.php`, `WidgetFormEscapingTest.php` and any others found by grep); `README.md` and `AGENTS.md` where the plugin is named.

**Interfaces:**
- Produces: `HyperPress\Social\Plugin::boot()`, `HyperPress\Social\Links`, `HyperPress\Social\Widgets\DisplaySocialWidget`, widget base id `hyperpress_social`, theme mods `hyperpress_social_<field>`. Task 2 (theme) consumes these exact names.

- [x] **Step 1:** `git mv` the folder, main file and widget class file; run the full integration suite and confirm it fails because of the old names (RED evidence).
- [x] **Step 2:** Replace namespace, autoload prefix, class names, header name and the identifiers listed in Decisions inside the plugin; update `Convention.php` and the plugin tests. Add or extend a test that asserts the widget base id is `hyperpress_social`, that every theme-mod key the Customizer registers starts with `hyperpress_social_`, and that none of the old `hyperpress_socials` strings remain in the plugin source.
- [x] **Step 3:** Update `README.md` and `AGENTS.md` mentions; add a dated amendment note (not a rewrite) to the 2026-10-02 convention spec plugin tables.
- [x] **Step 4:** Run `composer test:integration`, `composer test:unit`, `composer lint` in `plugins/`; expected all pass, lint exit 0. The theme suite is expected to fail on the socials tests until Task 2; note the failures.
- [x] **Step 5:** Commit in `plugins/`: `refactor(social): rename hyperpress-socials to hyperpress-social`, with every renamed and modified path listed explicitly.

### Task 2: Point the theme at hyperpress-social (theme repo)

**Files:**
- Modify: `theme/library/helpers.php` (lines ~60-67: `class_exists` and instantiation), `theme/tests/integration/FooterSocialsTest.php`, `FooterEscapingTest.php`, `NoPluginsTest.php`, and any other theme file found by grep for `HyperPress\Socials`, `hyperpress_socials`, `hyperpress-socials`, `widget_hyperpress_socials`.

**Interfaces:**
- Consumes: Task 1's class names, widget base id and theme-mod keys.

- [x] **Step 1:** Update the tests first to the new names and run `composer test:integration` in `theme/`; expected FAIL where the theme still uses old names (RED evidence).
- [x] **Step 2:** Update `helpers.php` and remaining references. Keep theme-owned sidebar ids (`socials`, `footer-socials`) unchanged.
- [x] **Step 3:** Run `composer test:unit`, `composer test:integration`, `composer lint` in `theme/` and the plugins integration suite; all pass.
- [x] **Step 4:** Commit in `theme/`: `refactor: use hyperpress-social`.

### Task 3: Rename hyperpress-shortcodes to hyperpress-shortcode

**Files:**
- Move: `plugins/hyperpress-shortcodes/` to `plugins/hyperpress-shortcode/`, main file to `hyperpress-shortcode.php`.
- Modify: everything inside the plugin (namespace `HyperPress\Shortcode`, inner `Shortcode\Shortcodes\*` classes, autoload prefix, header name, hooks per Decisions); `tests/src/Support/Convention.php`; `tests/integration/Shortcodes/*Test.php`; `README.md`, `AGENTS.md`; `theme/README.md` (two mentions) and any `theme/` code or docs naming the plugin (grep); the shortcode-tag stability expectations stay.

**Interfaces:**
- Produces: `HyperPress\Shortcode\Plugin::boot()`, `HyperPress\Shortcode\Shortcodes\{Banner,DateDistance,TimeRestrict,Raw,Emphasis}`, hooks `hyperpress_shortcode_now`, `hyperpress_shortcode_ordinals`. The five shortcode tags are unchanged.

- [x] **Step 1:** `git mv`; run the integration suite; confirm RED.
- [x] **Step 2:** Rename namespace, classes' references, header, hooks; update `Convention.php` and tests. Add a test asserting the five shortcode tags are still registered and the old hook names are gone.
- [x] **Step 3:** README/AGENTS/convention-spec amendment note in `plugins/`; `theme/README.md` and any theme references in `theme/` (own commit `docs: use hyperpress-shortcode`).
- [x] **Step 4:** Run suites and lint in both repos; all pass.
- [x] **Step 5:** Commit in `plugins/`: `refactor(shortcode): rename hyperpress-shortcodes to hyperpress-shortcode`.

### Task 4: Docker mounts and plan notes

> **STATUS: ✅ COMPLETED** (2026-10-07). Plugins repo commits `96b55b2`, `6aaa7c0`, `e63536d` (plus the plan-tick commits); theme repo commits `34099a8`, `e897be0`. Final state: plugins integration 541 tests / 1715 assertions (4 skipped, as before), unit 126 tests / 305 assertions, lint exit 0; both compose stacks `config` exit 0.

**Files:**
- Modify (untracked): `hyperonline/compose.main.yaml` (lines ~27-28), `hyperalumni/compose.main.yaml` (line ~21): source paths and target paths for the two renamed plugins, keeping the lists alphabetical.
- Modify: this plan (tick checkboxes, STATUS banner, Outcome notes incl. the dev-site consequence above).

- [x] **Step 1:** Edit both compose files; run `docker compose --profile local config >/dev/null; echo exit=$?` in each stack dir (do not start or read `.env*`); both exit 0.
- [x] **Step 2:** Final grep across `plugins/`, `theme/`, `hyperalumni/`, `hyperonline/` for the old slugs, namespaces and identifiers (excluding history docs); nothing stale.
- [x] **Step 3:** Update this plan and commit in `plugins/`: `docs(plans): record the singular plugin rename`.

## Outcome / execution notes

- **Identifiers renamed:** plugin folders/slugs `hyperpress-social`, `hyperpress-shortcode`; namespaces `HyperPress\Social`, `HyperPress\Shortcode`; widget class `DisplaySocialWidget`; widget base id `hyperpress_social` (option `widget_hyperpress_social`); theme mods `hyperpress_social_<field>`; hooks `hyperpress_shortcode_now` and `hyperpress_shortcode_ordinals`. Docker mounts in `hyperonline` and `hyperalumni` now use the new folder names (alphabetical order unchanged).
- **Consequence for dev sites:** WordPress deactivates a plugin whose folder moved, so both plugins are deactivated until reactivated; values saved under the old theme-mod keys and the old widget option are not read any more. Recreate the containers with the new mounts, reactivate the plugins and re-enter or copy the old values.
- **Shortcode tags unchanged (nine):** `hyper_banner`, `hyper_emphasis`, `hyper_date_distance`, `raw`, `time-restrict`, `time-restrict-repeat`, `time-restrict-repeat-1`, `time-restrict-repeat-2`, `time-restrict-repeat-3`.
- **Display labels kept:** the widget title "hyperPress Socials", the Customizer title 'Socials' and the `customize_socials` method name are user-facing/internal labels, not plugin identifiers, and were left as they are. Theme-owned sidebar ids `socials` and `footer-socials` are unchanged.
- **Theme bootstrap skip-list fix:** the theme's `tests/bootstrap-integration.php` lists plugin folders to skip when loading plugins; it was updated to the new folder names in theme commit `e897be0`, so the no-plugins theme tests keep skipping the right plugins.
- **Test fix:** `PluginIdentifiersTest` now freezes the clock outside the 2000-2100 window (year 2150, expects the else branch) and inside a past window (expects the shown branch); both fail when the filter name in `TimeRestrict.php` is broken (RED verified, then restored).
- **Final grep:** remaining old-slug hits are only historical dated docs under `docs/superpowers/{specs,plans,audit}` in both repos, the negative assertions in `SocialsTest.php` and `PluginIdentifiersTest.php`, and the git-ignored `.idea/workspace.xml`.
