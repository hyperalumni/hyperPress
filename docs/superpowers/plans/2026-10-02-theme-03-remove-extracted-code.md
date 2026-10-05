# Theme 03: Remove the Code That Moved to Plugins

> **STATUS: COMPLETED** (2026-10-04). Commits c310f3a..f8c4aed. Integration 16 tests pass in both modes (extracted plugins loaded and skipped); the only red unit test is the GitHub header (theme-04).

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans. Steps use checkbox (`- [x]`) syntax.

**Goal:** Delete, from the theme, the shortcodes, upload support, NextGen integration and HYPER plugin recommendations that now live in plugins, without losing any behavior.

**Prerequisite:** theme-02-renames-and-breadcrumbs complete. plugins-03-core-plugins (season shortcode) and plugins-04-remaining-plugins Tasks 6 and 7 (`hyperpress-shortcodes`, `hyperpress-media`) complete with their parity tests passing. **Do not start a task here until its plugin counterpart exists and passes**; otherwise the site loses a feature.
**Followed by:** theme-04-cleanup.
**Spec:** `docs/superpowers/specs/2026-10-02-theme-cleanup-and-extraction-design.md` (sections 4 and 7).

**Conventions:** as in theme-01-tooling-and-baseline. Every deletion is one commit with an explicit pathspec.

**Mechanics for each removal:** delete the file, delete its `require_once`/router line, run the tests.

---

## Task 1: Remove the extracted shortcodes

**Files:** Delete `library/shortcodes/{show-hide-content,date-distance,raw,emphasis,banner,season-count}.php`; modify `library/shortcodes.php` (router) and possibly `functions.php` (if it requires `library/shortcodes.php`).

- [x] **Step 1: Gate check.** In the plugins repo run `docker compose run --rm php composer test:integration -- --filter 'ShortcodeParityTest|SeasonCountTest'` and confirm PASS. If it does not pass, stop.

- [x] **Step 2: Write the failing test** `tests/integration/ExtractedShortcodesTest.php`

```php
<?php
namespace HyperPress\ThemeTests\Integration;

use WP_UnitTestCase;

final class ExtractedShortcodesTest extends WP_UnitTestCase {

	private const TAGS = array(
		'time-restrict', 'time-restrict-repeat', 'time-restrict-repeat-1', 'time-restrict-repeat-2', 'time-restrict-repeat-3',
		'hyper_date_distance', 'raw', 'hyper_emphasis', 'hyper_banner', 'hyper_season_count',
	);

	public function test_the_theme_does_not_register_the_extracted_shortcodes(): void {
		// With the extracted plugins skipped (HYPERPRESS_TEST_WITHOUT_EXTRACTED=1) only the theme is loaded.
		if ( ! getenv( 'HYPERPRESS_TEST_WITHOUT_EXTRACTED' ) ) {
			$this->markTestSkipped( 'Run with HYPERPRESS_TEST_WITHOUT_EXTRACTED=1' );
		}
		foreach ( self::TAGS as $tag ) {
			if ( 'hyper_season_count' === $tag ) {
				continue; // Registered by hyperpress-season, which stays loaded.
			}
			$this->assertFalse( shortcode_exists( $tag ), "[$tag] must not be registered by the theme" );
		}
	}

	public function test_with_the_plugins_active_every_tag_exists(): void {
		if ( getenv( 'HYPERPRESS_TEST_WITHOUT_EXTRACTED' ) ) {
			$this->markTestSkipped( 'Needs the extracted plugins' );
		}
		foreach ( self::TAGS as $tag ) {
			$this->assertTrue( shortcode_exists( $tag ), "[$tag] must be provided by a plugin" );
		}
	}
}
```

Run: `docker compose run --rm -e HYPERPRESS_TEST_WITHOUT_EXTRACTED=1 php composer test:integration -- --filter ExtractedShortcodesTest`
Expected: FAIL (the theme still registers them).

- [x] **Step 3: Delete and unwire.** Read `library/shortcodes.php` (12 lines). Remove the `require_once` lines for the six files above. If nothing remains in the router, delete `library/shortcodes.php` and its `require` in `functions.php`; otherwise keep it. Delete the six files. Leave the `gallery` shortcode override in `library/foundation.php` (presentation).

- [x] **Step 4: Run both modes**

Run: `docker compose run --rm -e HYPERPRESS_TEST_WITHOUT_EXTRACTED=1 php composer test:integration -- --filter ExtractedShortcodesTest` — PASS (first test), second skipped.
Run: `docker compose run --rm php composer test:integration` — all PASS.

- [x] **Step 5: Commit**

```bash
git add -A library/shortcodes library/shortcodes.php functions.php tests/integration/ExtractedShortcodesTest.php
git commit -m "refactor: remove shortcodes now provided by hyperpress-shortcodes and hyperpress-season" -- library/shortcodes library/shortcodes.php functions.php tests/integration/ExtractedShortcodesTest.php
```

(`git add -A` is limited to the listed paths, so unrelated changes are not staged. Check `git status --short` first and confirm only these paths are staged by this command.)

---

## Task 2: Remove upload support and the NextGen integration

**Files:** Delete `library/supports/svg.php`, `library/supports/avif.php`, `library/customize/nggallery.php`, `library/breadcrumbs/nggallery.php`; modify `functions.php`, `library/customize.php`, `library/breadcrumbs.php`.

- [x] **Step 1: Gate check:** in the plugins repo, `composer test:integration -- --filter MediaTest` PASS.

- [x] **Step 2: Write the failing test** `tests/integration/ExtractedMediaTest.php`

```php
<?php
namespace HyperPress\ThemeTests\Integration;

use WP_UnitTestCase;

final class ExtractedMediaTest extends WP_UnitTestCase {

	public function test_theme_alone_does_not_add_svg_or_avif_uploads(): void {
		if ( ! getenv( 'HYPERPRESS_TEST_WITHOUT_EXTRACTED' ) ) {
			$this->markTestSkipped( 'Run with HYPERPRESS_TEST_WITHOUT_EXTRACTED=1' );
		}
		$mimes = apply_filters( 'upload_mimes', array() );
		$this->assertArrayNotHasKey( 'svg', $mimes );
		$this->assertArrayNotHasKey( 'avif', $mimes );
		$this->assertFalse( has_filter( 'hyperpress_breadcrumbs_content', 'hyperpress_breadcrumbs_nggallery' ) );
	}

	public function test_with_the_media_plugin_uploads_are_allowed(): void {
		if ( getenv( 'HYPERPRESS_TEST_WITHOUT_EXTRACTED' ) ) {
			$this->markTestSkipped( 'Needs the media plugin' );
		}
		$mimes = apply_filters( 'upload_mimes', array() );
		$this->assertArrayHasKey( 'svg', $mimes );
		$this->assertArrayHasKey( 'avif', $mimes );
	}
}
```

Run with `HYPERPRESS_TEST_WITHOUT_EXTRACTED=1`: expected FAIL.

- [x] **Step 3: Delete and unwire.** Remove the `require_once` lines for `supports/svg.php` and `supports/avif.php` from `functions.php`; the `nggallery` line from `library/customize.php`; the `nggallery` line from `library/breadcrumbs.php`. Delete the four files. Check the theme's `nggallery/` directory (templates for the plugin) is left alone: it is presentation.

- [x] **Step 4: Run both modes** as in Task 1 Step 4. Expected PASS.

- [x] **Step 5: Commit** with an explicit pathspec listing the deleted files, the three modified files and the new test.

---

## Task 3: Trim the TGM plugin list and remove the committed key

**Files:** Modify `library/plugins.php`; create `tests/unit/PluginsListTest.php`.

- [x] **Step 1: Write the failing test**

```php
<?php
namespace HyperPress\ThemeTests\Unit;

use PHPUnit\Framework\TestCase;

final class PluginsListTest extends TestCase {

	private static function source(): string {
		return (string) file_get_contents( dirname( __DIR__, 2 ) . '/library/plugins.php' );
	}

	public function test_hyper_plugins_are_not_registered_with_tgm(): void {
		$this->assertDoesNotMatchRegularExpression( "/'slug'\s*=>\s*'hyperpress-/", self::source(), 'HYPER plugins declare their dependencies via Requires Plugins' );
	}

	public function test_no_api_key_is_committed(): void {
		$this->assertDoesNotMatchRegularExpression( '/api_key\s*=/i', self::source() );
		$this->assertDoesNotMatchRegularExpression( '/[?&](token|key|api_key|apikey)=[A-Za-z0-9]{16,}/i', self::source() );
	}
}
```

Run: `docker compose run --rm php composer test:unit -- --filter PluginsListTest` — expected FAIL (both).

- [x] **Step 2: Edit `library/plugins.php`**

- Delete the eight `hyperpress-*` entries (`'source' => '#'`). Do not delete the file; the third-party recommendations stay.
- The Meta Box AIO entry (`meta-box-aio`) currently has a `'source'` URL embedding an `api_key`. Remove the `source` value so TGM no longer downloads it with a committed key: keep the entry with `name`, `slug`, `external_url` (`https://metabox.io/aio/`) and `required => true`; TGM then shows a "download and install manually" notice. (`meta-box-aio` is also declared by the plugins' `Requires Plugins`.)
- Add recommendation entries for `hyperpress-shortcodes` and `hyperpress-media` only if they need to be discoverable: they have no download source, so do not add them; document them in the README (theme-04-cleanup).

- [x] **Step 3: The key is still in git history.** This plan does NOT rewrite history. Record in the notes, and tell the owner, that the key that was committed must be rotated at the vendor; the audit (theme-05-audit) lists it as a finding. Do not print the key anywhere.

- [x] **Step 4: Run** `composer test:unit` and `composer test:integration` (the latter still boots the theme, which includes `library/plugins.php`): PASS.

- [x] **Step 5: Commit**

```bash
git add library/plugins.php tests/unit/PluginsListTest.php
git commit -m "refactor: drop HYPER plugins and the committed download key from the TGM list" -- library/plugins.php tests/unit/PluginsListTest.php
```

---

## Task 4: Close out theme-03-remove-extracted-code

- [x] **Step 1:** Run both suites, in both modes (`HYPERPRESS_TEST_WITHOUT_EXTRACTED` unset and set). Only the theme-01-tooling-and-baseline header test may fail.
- [x] **Step 2:** `grep -rn "library/shortcodes/\|supports/svg\|supports/avif\|nggallery" functions.php library | head` should show only the legitimate `nggallery/` template directory references, if any.
- [x] **Step 3:** Flip checkboxes, add the `STATUS: COMPLETED` banner, fill in notes, commit the plan with an explicit pathspec.

## Execution notes

- Deleted from the theme: the six shortcode files and the `library/shortcodes.php` router (it held only those requires); `library/supports/{svg,avif}.php`; `library/customize/nggallery.php`; `library/breadcrumbs/nggallery.php`; eight HYPER entries in `library/plugins.php`. No other theme code called the removed handler functions or used the removed tags. The theme's `gallery` shortcode override and its `nggallery/` templates stay.
- The committed Meta Box download URL (with its key) was removed from `library/plugins.php`; the `meta-box-aio` entry keeps only `external_url`. The key remains in git history: **the owner must rotate it with Meta Box.** This is finding 1 of theme-05-audit.
- Deploy order: deploy the theme together with `hyperpress-shortcodes` and `hyperpress-media`; otherwise shortcodes render as literal text, and NextGen crumbs are missing (or doubled while both exist).
- New tests: `ExtractedShortcodesTest` and `ExtractedMediaTest` (both modes via `HYPERPRESS_TEST_WITHOUT_EXTRACTED=1`), `PluginsListTest`.
