# Plugins 05: Standards Baseline, Docs and Smoke Test

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans. Steps use checkbox (`- [ ]`) syntax.

**Goal:** Bring the migrated plugins to a clean coding-standards baseline, document the upgrade path, smoke-test in a real dev environment, and close out plugins-01 to plugins-05.

**Prerequisite:** plugins-01 to plugins-04 complete and green.
**Followed by:** plugins-06-audit.

**Conventions:** as before. Commit with an explicit pathspec.

---

## Task 1: Coding-standards baseline to zero

`composer lint:cs` was non-gating. It becomes gating here.

- [ ] **Step 1: Measure**

Run: `docker compose run --rm php vendor/bin/phpcs --report=summary`
Record the error and warning counts per plugin in the notes.

- [ ] **Step 2: Auto-fix one plugin at a time**

For each plugin `P` in `hyperpress-*`:

Run: `docker compose run --rm php vendor/bin/phpcbf hyperpress-P`
Then: `docker compose run --rm php composer test:unit && docker compose run --rm php composer test:integration`
Expected: still green. phpcbf only reformats, so a failing test means the fixer changed behaviour: revert that plugin (`git checkout -- hyperpress-P`) and fix it by hand instead.

- [ ] **Step 3: Fix what phpcbf cannot**

Run: `docker compose run --rm php vendor/bin/phpcs hyperpress-P`
Fix each remaining error by hand. Do NOT fix security-class findings (escaping, sanitizing, nonces, `extract`) here: those are plugins-06-audit. Suppress them with an inline `// phpcs:ignore <Sniff> -- plugins audit` comment naming the sniff, so the baseline reaches zero without changing behaviour, and the audit finds every suppression with `grep -rn 'plugins audit' hyperpress-*`.

- [ ] **Step 4: Commit per plugin**

```bash
git add hyperpress-P
git commit -m "style(P): bring to WordPress coding standards baseline" -- hyperpress-P
```

- [ ] **Step 5: Confirm the gate**

Run: `docker compose run --rm php composer lint`
Expected: exit 0.

If a sniff is impractical for the whole codebase (for example a naming rule that conflicts with a published public API), exclude it in `phpcs.xml.dist` with an XML comment giving the reason, and commit that change by itself.

---

## Task 2: Document the upgrade path and tooling

**Files:**
- Modify: `README.md`

- [ ] **Step 1: Read the current `README.md`**, then add (keeping its existing content and tone) three short sections:
  - **Development:** `docker compose run --rm php composer install`, `composer lint`, `composer test:unit`, `composer test:integration`; mention PHP 8.5 in the image and `Requires PHP: 8.3` as the floor.
  - **Conventions:** registered keys are `hyper_*` (max 20 characters); every other identifier is `hyperpress_{plugin}_{field}`; shared code lives in `hyperpress-utils`; load order and `Requires Plugins` (link to `docs/superpowers/specs/2026-10-02-plugins-convention-and-shared-code-design.md`).
  - **Extracted plugins:** `hyperpress-shortcodes` (time-restrict, date-distance, raw, emphasis, banner shortcodes) and `hyperpress-media` (SVG/AVIF uploads, NextGen integration) were moved out of the theme; sites whose content uses those shortcodes must activate `hyperpress-shortcodes`.
  - **Upgrading from the pre-`hyper_` keys:** deactivate and reactivate each plugin (main files were renamed, which changes the plugin basename); existing dev content and stored meta are orphaned by the key rename, so reset the local database; flush permalinks (Settings, Permalinks, Save). There is no migration tool by design.

- [ ] **Step 2: Commit**

```bash
git add README.md
git commit -m "docs: document tooling, conventions and the upgrade path" -- README.md
```

---

## Task 3: Smoke test in a real dev environment

Automated tests stub Meta Box and the theme, so verify the real stack once.

- [ ] **Step 1: Find how the dev environments reset and start**

Read the compose/README files in `../hyperalumni/` and `../hyperonline/` (read-only) and note the commands to start the stack, reset the database, and run WP-CLI. Add any reusable commands to the README section from Task 2.

- [ ] **Step 2: Reset and boot one environment** (ask the user before resetting any database)

Expected: WordPress loads with all 14 plugins. Use WP-CLI inside the environment (`wp plugin deactivate --all`, `wp plugin activate <each in LOAD_ORDER>`, `wp rewrite flush`).

- [ ] **Step 3: Check, and record the result of each**
  - `wp plugin list` shows all 12 active with no "requires" warnings.
  - `wp post-type list --fields=name,public` shows the six `hyper_*` types; `wp taxonomy list` shows the five `hyper_*` taxonomies.
  - For each public type, create one post and request its singular URL and archive URL (`curl -sI`): both 200 at the unchanged paths (`/award/...`, `/awards/`, and so on).
  - WP admin: each type's menu is visible to an Administrator and an Editor and NOT to a Subscriber account.
  - Customizer opens without PHP errors and shows the renamed controls (season/program dropdowns, socials, newsletter/sponsor subtitles, countdown colours).
  - plugins-01-test-toolchain page containing the `hyperpress_countdown` shortcode renders, and a page using `[time-restrict]`, `[hyper_emphasis]` and `[hyper_season_count]` renders with `hyperpress-shortcodes` active (and as literal text with it deactivated).
  - An SVG and an AVIF file upload in the media library.
  - PHP error log is clean on these pages.

- [ ] **Step 4: Record outcome** in the notes below. Any defect found becomes a new failing test first, then a fix, committed separately.

---

## Task 4: Close out

- [ ] **Step 1: Final verification (fresh run, read the output)**

Run: `docker compose run --rm php composer lint && docker compose run --rm php composer test:unit && docker compose run --rm php composer test:integration`
Expected: all exit 0.

- [ ] **Step 2: Residual checks**

Run: `grep -rn "GitHub Plugin URI" hyperpress-* | head` (expect nothing) and `grep -rn "function_exists" hyperpress-*/src hyperpress-*/hyperpress-*.php` (expect nothing: the only legitimate optional-dependency checks are `class_exists` calls in `hyperpress-media` for NextGen).

- [ ] **Step 3: Update the plans and specs**

Add the `STATUS: COMPLETED` banner (date, commit range) to plugins-01 to plugins-04 and this plan, flip every checkbox, and fill their "Execution notes". Mark Spec 1 as implemented in its header. Then:

```bash
git add docs/superpowers
git commit -m "docs: close out convention and test-toolchain plans" -- docs/superpowers
```

## Execution notes

(Fill in during execution: phpcs counts per plugin, smoke-test results, suppressions added.)
