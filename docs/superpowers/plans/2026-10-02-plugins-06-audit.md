# Plugins 06: Security and Correctness Audit

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans. Steps use checkbox (`- [ ]`) syntax.

**Goal:** Review every plugin for security and correctness defects, verify each finding in source, get the user's approval on the fix list, then fix each approved finding test-first.

**Prerequisite:** the earlier plugin plans complete, so the audit reads the final code and every fix has a test harness.
**Spec:** `docs/superpowers/specs/2026-10-02-plugins-tests-and-audit-design.md` (audit section).

**Why this plan has two phases:** the fix tasks depend on what the audit finds, so they cannot be written in advance. Phase 1 (Tasks 1 to 3) is fully specified. Phase 2 (Task 4) is a repeatable loop with one worked example; after the user approves the finding list, one task per approved finding is appended to this plan before any fix starts.

**Conventions:** as before. Phase 1 is read-only until Task 3 writes the report.

---

## Task 1: Checklist and known candidates

- [ ] **Step 1: Audit areas** (each file in each plugin is read against all of them)
  1. Output escaping: every echo/printf of a variable, including shortcode output, widget output, `$args['before_widget']`-style values, admin notices, Customizer controls.
  2. Shortcodes: attribute handling, `extract()`, whether attribute values reach output or queries unescaped.
  3. Input sanitizing: meta fields with no `sanitize_callback`, Customizer settings with no `sanitize_callback`, `$_GET`/`$_POST`/`$_REQUEST` reads.
  4. Capabilities and nonces: anything that writes data, AJAX handlers, REST routes, admin actions.
  5. Queries: `WP_Query`/`get_posts`/`get_terms` arguments built from user input, raw `$wpdb`.
  6. Data exposure: non-public types reachable by URL or REST, private meta returned publicly.
  7. Correctness: wrong/dead conditions, variables used before assignment, wrong return types, deprecated-in-8.5 constructs.
  8. Assets: unversioned or externally loaded scripts, handle collisions, vendored library age (`TimeCircles.js`).
  9. Dependencies: calls into Meta Box, the theme, or other plugins without a presence check; undeclared dependencies.

- [ ] **Step 2: Known candidates to verify first** (each must be confirmed or rejected by reading the code, never assumed)

| # | Candidate | Where |
|---|---|---|
| 1 | Sponsor description saved without sanitizing | `hyperpress-sponsor/src/MetaBoxes.php` (legacy `lib/meta-boxes.php:46-52`) |
| 2 | All eight competition fields have no `sanitize_callback` | `hyperpress-competition/src/MetaBoxes.php` |
| 3 | `extract()` in the countdown shortcode | `hyperpress-countdown/src/Shortcode.php` (legacy `lib/shortcodes/countdown.php:14`) |
| 4 | `extract()` in the show/hide shortcodes | search `grep -rn "extract(" hyperpress-*` |
| 5 | Socials widget echoes `$args` values | `hyperpress-socials/src/Widgets/DisplaySocialsWidget.php` (legacy `display-socials.php:89,91,114`), likely fine |
| 6 | `PostTypeDropdownControl` calls `esc_attr( $this->id )` without `echo`, so the `for`/`name`/`id` attributes are empty; `selected()` compares an array value to a string | `hyperpress-utils/src/Controls/PostTypeDropdownControl.php` |
| 7 | Newsletter banner overwrites the theme-mod colour with the per-post meta value even when that is empty | `hyperpress-newsletter/src/BannerFilter.php` (behaviour pinned by `NewsletterBannerTest`) |
| 8 | Competition banner reads theme mod `hyperpress_competition_banner_subtitle`, but no Customizer control defines it | `hyperpress-competition/src/Plugin.php` |
| 9 | Season rewrite rule: confirm `$matches[2]` can only be a registered post type key | `hyperpress-season/src/Rewrite.php` |
| 10 | REST API. DECIDED: `show_in_rest` is false for every post type and taxonomy (nothing in the repo uses the routes; Classic Editor is a required plugin). Consequence: the block editor is unavailable for these types and term pickers use the classic UI. Revisit only if an external consumer or the block editor is wanted | `PostTypeRegistrar` and `TaxonomyRegistrar` defaults |
| 11 | Every `// phpcs:ignore ... -- plugins audit` comment added in plugins-05-standards-and-smoke-test | `grep -rn 'plugins audit' hyperpress-*` |
| 12 | `Count Downs` vs `Countdown(s)` label inconsistency in countdown | `hyperpress-countdown/src/Plugin.php` |
| 13 | Dropped TODO: multiselect category control could extend `Walker_Category_Checklist` | `hyperpress-utils/src/Controls/CategoryDropdownControl.php` (improvement, not a defect) |
| 14 | A Google Maps `api_key` is hard-coded in the map field (committed secret; the value must never be reproduced in notes or reports). Needs rotation/restriction by the owner and moving to a constant or option | `hyperpress-competition/src/MetaBoxes.php` (map field), `git log -S'api_key'` for history |
| 15 | `__( '', 'hyperpress' )` on an empty string (gettext misuse) carried over from the legacy label description | `hyperpress-competition/src/MetaBoxes.php` |

- [ ] **Step 3: Commit nothing** (read-only).

---

## Task 2: Review every file

- [ ] **Step 1: Dispatch read-only reviewers**, one per group of plugins, so each reads its files in full against the Task 1 checklist and returns findings as structured items (file:line, what, why it matters, suggested fix). Suggested groups: (utils, season, program), (competition, robot, award), (countdown, newsletter), (sponsor, socials, label, student). Give each reviewer the exact checklist and candidate rows for its plugins. Reviewers do not edit files.

- [ ] **Step 2: Verify every finding yourself in the source.** Open the cited file:line and confirm the code does what the finding says, and that the data really is user-controlled or the behaviour really is wrong. Discard anything that does not hold up; downgrade anything exaggerated. plugins-01-test-toolchain reviewer's claim is a lead, not evidence.

---

## Task 3: Write the report and get approval

**Files:**
- Create: `docs/superpowers/audit/2026-10-02-audit.md`

- [ ] **Step 1: Write the report** with: method, per-plugin coverage (every file listed), and the findings table sorted by severity (critical, high, medium, low, info). Each finding has: ID, severity, plugin, file:line, evidence (the quoted code), impact, proposed fix, and the test that will prove it. Include the rejected candidates with the reason.

- [ ] **Step 2: Commit the report**

```bash
git add docs/superpowers/audit/2026-10-02-audit.md
git commit -m "docs(audit): add security and correctness audit report" -- docs/superpowers/audit/2026-10-02-audit.md
```

- [ ] **Step 3: STOP. Ask the user to approve the fix list** (which findings to fix, which to accept or defer). Do not start Phase 2 without that answer.

---

## Task 4: Fix loop (one task per approved finding, appended after approval)

Each fix is its own task and its own commit:

1. Write a failing test that demonstrates the defect (unit if it can be pure, integration otherwise).
2. Run it and watch it fail for the right reason.
3. Make the smallest fix.
4. Run the full suite and `composer lint`: all green.
5. Commit: `git add <code> <test> docs/superpowers/audit/2026-10-02-audit.md` then `git commit -m "fix(<plugin>): <finding id> <summary>" -- <same paths>`, with the audit entry marked fixed in the same commit.

### Worked example (candidate 1, if confirmed): sanitize the sponsor description

- [ ] **Step 1: Failing test** `tests/integration/SponsorDescriptionSanitizeTest.php`

```php
<?php
namespace HyperPress\Tests\Integration;

use WP_UnitTestCase;

final class SponsorDescriptionSanitizeTest extends WP_UnitTestCase {

	private function description_field(): array {
		foreach ( apply_filters( 'rwmb_meta_boxes', array() ) as $box ) {
			foreach ( $box['fields'] ?? array() as $field ) {
				if ( 'hyperpress_sponsor_description' === ( $field['id'] ?? '' ) ) {
					return $field;
				}
			}
		}
		$this->fail( 'hyperpress_sponsor_description field not found' );
	}

	public function test_description_has_a_sanitize_callback_that_strips_scripts(): void {
		$field = $this->description_field();
		$this->assertArrayHasKey( 'sanitize_callback', $field );
		$clean = call_user_func( $field['sanitize_callback'], '<p>Hi</p><script>alert(1)</script>' );
		$this->assertStringNotContainsString( '<script', $clean );
		$this->assertStringContainsString( '<p>Hi</p>', $clean );
	}
}
```

- [ ] **Step 2: Run, expect FAIL** (`sanitize_callback` key missing).
- [ ] **Step 3: Fix:** add `'sanitize_callback' => 'wp_kses_post'` to that field in `hyperpress-sponsor/src/MetaBoxes.php`.
- [ ] **Step 4: Run the test and the full suite: green.**
- [ ] **Step 5: Commit** per the loop above.

---

## Task 5: Close out

- [ ] **Step 1:** Run `composer lint`, `composer test:unit`, `composer test:integration`: all exit 0.
- [ ] **Step 2:** Confirm every approved finding is marked fixed in the report and every deferred/accepted one has the user's stated reason recorded.
- [ ] **Step 3:** Add the `STATUS: COMPLETED` banner to this plan and commit it with the final report state.
