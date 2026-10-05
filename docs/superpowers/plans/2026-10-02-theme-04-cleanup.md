# Theme 04: Theme Cleanup (Header, Prefixes, Guards, Standards)

> **STATUS: COMPLETED** (2026-10-04). Commits 5d31a5b..1899776 on branch cleanup/convention-and-tests. `composer lint` (syntax + phpcs) exits 0; unit 16 and integration 16 (2 skipped) pass in both modes.

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans. Steps use checkbox (`- [x]`) syntax.

**Goal:** Remove the updater header, rename `foundationpress_` identifiers to `hyperpress_`, remove redundant `function_exists` guards, bring the theme to the coding-standards baseline, and document the extracted plugins. Behavior does not change; theme-01-tooling-and-baseline render baselines and the theme-02 and theme-03 tests must stay green throughout.

**Prerequisite:** theme-03-remove-extracted-code complete.
**Followed by:** theme-05-audit.
**Spec:** `docs/superpowers/specs/2026-10-02-theme-cleanup-and-extraction-design.md` (section 8).

**Conventions:** as in theme-01-tooling-and-baseline. Commit per task with an explicit pathspec. Security-class findings (escaping, sanitizing, nonces, `extract`, the `include` of a filter return) are NOT fixed here; they are suppressed with `// phpcs:ignore <Sniff> -- theme audit` so the baseline reaches zero without changing behavior, and theme-05-audit finds them with `grep -rn 'theme audit'`.

---

## Task 1: Theme header

**Files:** Modify `style.css`.

- [x] **Step 1:** Remove the line `GitHub Theme URI: https://github.com/hyperalumni/hyperPress` from the `style.css` header. Keep every other field.
- [x] **Step 2:** Run `docker compose run --rm php composer test:unit -- --filter ThemeHeaderTest` — expected PASS (this flips the one intentionally failing test from theme-01-tooling-and-baseline).
- [x] **Step 3: Commit**

```bash
git add style.css
git commit -m "chore: remove the GitHub Theme URI header" -- style.css
```

---

## Task 2: `foundationpress_` to `hyperpress_` (and class names)

**Files:** Many under `library/`, plus templates that call the renamed functions. The exact set comes from the inventory in Step 1.

- [x] **Step 1: Inventory (read-only)**

Run (from `theme/`):

```bash
grep -rnoE "(function\s+|'|\")(foundationpress_[a-z0-9_]+)" --include='*.php' . | grep -vE '^\./(vendor|node_modules|dist|packaged|tests)/' | sort -u
grep -rnE "\bFoundationPress[_A-Za-z]*" --include='*.php' . | grep -vE '^\./(vendor|node_modules|dist|packaged|tests)/' | grep -E "class |new |::" | sort -u
grep -rnE "foundationpress|FoundationPress" --include='*.php' --include='*.js' --include='*.json' --include='*.scss' . | grep -vE '^\./(vendor|node_modules|dist|packaged|tests)/|@package|@since|@link' | grep -vE "function |class " | head -40
```

Record three lists: (a) `foundationpress_*` function names; (b) `FoundationPress_*` class names; (c) other `foundationpress` strings (CSS class names, text domains, script handles, option names, file names such as `class-foundationpress-comments.php`).

- [x] **Step 2: Decide what must NOT be renamed.** Flag and keep: (i) strings that are stored data or external contracts (option names, theme mods, hook names the plugins or WordPress consume: grep `../plugins` for each name before touching it); (ii) CSS class names and JS handles that the compiled assets in `dist/` and `src/` use (the frontend build is not run here, so renaming them would desync the markup from the compiled CSS); (iii) anything a child theme could override. Check for a child theme: `ls ../hyperalumni/*/wp-content/themes ../hyperonline/*/wp-content/themes 2>/dev/null` (adjust to how those environments are laid out). Write the keep-list into the notes with the reason for each entry.

- [x] **Step 3: Test first.** Create `tests/unit/NoFoundationPressPrefixTest.php`:

```php
<?php
namespace HyperPress\ThemeTests\Unit;

use PHPUnit\Framework\TestCase;

final class NoFoundationPressPrefixTest extends TestCase {

	/** Identifiers deliberately kept, with the reason in the plan notes. */
	private const KEEP = array(
		// e.g. 'some_stored_option_name',
	);

	public function test_no_foundationpress_prefixed_functions_or_classes(): void {
		$root = dirname( __DIR__, 2 );
		$hits = array();
		$iter = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $root, \FilesystemIterator::SKIP_DOTS ) );
		foreach ( $iter as $file ) {
			$path = $file->getPathname();
			if ( 'php' !== $file->getExtension() || preg_match( '#/(vendor|node_modules|dist|packaged|tests|docs)/#', $path ) ) {
				continue;
			}
			if ( preg_match_all( '/\b(?:function|class)\s+((?:foundationpress_|FoundationPress_)\w*)/', (string) file_get_contents( $path ), $m ) ) {
				foreach ( $m[1] as $name ) {
					if ( ! in_array( $name, self::KEEP, true ) ) {
						$hits[] = substr( $path, strlen( $root ) + 1 ) . ": $name";
					}
				}
			}
		}
		$this->assertSame( array(), $hits, "Rename to hyperpress_ / HyperPress_Theme_:\n" . implode( "\n", $hits ) );
	}
}
```

Run it; expected FAIL listing every definition (about 37 functions plus the classes).

- [x] **Step 4: Rename in batches of one file at a time.** For each file: rename the function or class definitions, update every call site in the theme (templates, `functions.php`, hooks that name the function as a string such as `add_action( 'init', 'foundationpress_x' )`), and rename the file if its name contains `foundationpress` (`class-foundationpress-comments.php` becomes `class-hyperpress-comments.php`, and update the `require`). Classes: `FoundationPress_Foo` becomes `HyperPress_Theme_Foo`. After each file run `composer lint:syntax`, `composer test:unit` and `composer test:integration`; all green before the next file. Never use a blind repository-wide sed: each rename is verified against the keep-list from Step 2.

- [x] **Step 5: Commit per file group** (for example one commit for the walkers, one for comments, one for enqueue and cleanup), each with an explicit pathspec of the files changed in that group.

---

## Task 3: Remove redundant `function_exists` guards

The theme defines its own functions once; guards are only needed for a documented override point.

**Files:** Many under `library/`.

- [x] **Step 1: Inventory**

Run: `grep -rnE "function_exists" --include='*.php' library functions.php template-parts *.php | grep -vE 'class-tgm-plugin-activation'`

Classify each: (a) a redeclaration guard around the theme's own function or router (remove); (b) a check for a function from another plugin or WordPress feature that may be absent (keep, and add a comment naming the dependency); (c) a documented child-theme override (keep; list it in the notes).

- [x] **Step 2: Test first.** Create `tests/unit/NoRedeclarationGuardsTest.php`:

```php
<?php
namespace HyperPress\ThemeTests\Unit;

use PHPUnit\Framework\TestCase;

final class NoRedeclarationGuardsTest extends TestCase {

	public function test_theme_functions_are_not_wrapped_in_redeclaration_guards(): void {
		$root = dirname( __DIR__, 2 );
		$hits = array();
		$iter = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $root . '/library', \FilesystemIterator::SKIP_DOTS ) );
		foreach ( $iter as $file ) {
			$path = $file->getPathname();
			if ( 'php' !== $file->getExtension() || false !== strpos( $path, 'class-tgm-plugin-activation' ) ) {
				continue;
			}
			// if ( ! function_exists( 'x' ) ) : followed by function x(
			if ( preg_match_all( "/if\s*\(\s*!\s*function_exists\(\s*['\"]([\w\\\\]+)['\"]\s*\)\s*\)\s*:?\s*\{?\s*(?:\/\/[^\n]*\n\s*)*function\s+\\1\b/s", (string) file_get_contents( $path ), $m ) ) {
				foreach ( $m[1] as $name ) {
					$hits[] = substr( $path, strlen( $root ) + 1 ) . ": $name";
				}
			}
		}
		$this->assertSame( array(), $hits, "Redeclaration guards remain:\n" . implode( "\n", $hits ) );
	}
}
```

Run; expected FAIL. (The regex matches the guard-then-same-named-function shape. If the theme writes guards in another shape, extend the regex until it finds all redeclaration guards from Step 1 class (a), then proceed.)

- [x] **Step 3: Remove the class (a) guards** one file at a time: delete the `if ( ! function_exists(...) ) :` opener and its `endif;` (or braces), keep the function body, re-indent. Run lint and both suites after each file.

- [x] **Step 4: Commit per group** with explicit pathspecs.

---

## Task 4: Coding-standards baseline to zero, then gate it

- [x] **Step 1: Measure:** `docker compose run --rm php vendor/bin/phpcs --report=summary`. Record counts per directory.
- [x] **Step 2: Auto-fix a directory at a time:** `docker compose run --rm php vendor/bin/phpcbf library/<dir>` then run `composer test:unit` and `composer test:integration`. plugins-01-test-toolchain failing test means the fixer changed behavior: `git checkout -- <those files>` is NOT acceptable here because the repo has unrelated uncommitted work in the same files. Instead restore from the copy you took first: before each phpcbf run, `cp -r library/<dir> /tmp/opencode/<dir>.bak`, and restore from it if needed.
- [x] **Step 3: Fix the remainder by hand.** Security-class findings get `// phpcs:ignore <Sniff> -- theme audit` (see the conventions above), nothing else. If a sniff is impractical for the whole theme, exclude it in `codesniffer.ruleset.xml` with an XML comment giving the reason, in its own commit.
- [x] **Step 4: Gate:** `docker compose run --rm php composer lint` exits 0.
- [x] **Step 5: Commit per directory** with explicit pathspecs.

---

## Task 5: Documentation

**Files:** Modify `README.md` (and `CHANGELOG.md` if it is maintained; read it first).

- [x] **Step 1:** Read `README.md`. Replace FoundationPress-starter boilerplate that no longer applies only where it is now false; add short sections: **Development** (`docker compose run --rm php composer lint|test:unit|test:integration`), **Plugins** (the theme expects `hyperpress-utils` and the other HYPER plugins; `hyperpress-shortcodes` must be active for sites whose content uses `time-restrict`, `hyper_emphasis`, `raw`, `hyper_banner`, `hyper_date_distance`, and `hyperpress-media` for SVG and AVIF uploads), **Hooks** (`hyperpress_banner_content`, `hyperpress_breadcrumbs_content`, `content_template`, `hyperpress_labels_content` and what each expects), and **Upgrading** (reset the dev database, re-select the homepage countdowns and blog post types in the Customizer).
- [x] **Step 2: Commit**

```bash
git add README.md
git commit -m "docs: document tooling, plugin expectations, hooks and upgrade notes" -- README.md
```

---

## Task 6: Close out theme-04-cleanup

- [x] **Step 1:** Fresh run: `composer lint && composer test:unit && composer test:integration`, and the integration suite again with `HYPERPRESS_TEST_WITHOUT_EXTRACTED=1`. All exit 0.
- [x] **Step 2:** Flip checkboxes, add the `STATUS: COMPLETED` banner, fill in notes (keep-list, override-point list, phpcs counts), commit the plan with an explicit pathspec.

## Execution notes

- Header: the `GitHub Theme URI` field is gone from `style.css`.
- Prefix: 30 `foundationpress_*` functions became `hyperpress_*`, four `Foundationpress_*` classes became `HyperPress_Theme_*`, and the four `class-foundationpress-*.php` files were renamed. Kept on purpose: four public `do_action` tags (`foundationpress_before_content`, `foundationpress_after_content`, `foundationpress_page_before_entry_content`, `foundationpress_page_after_entry_content`) as possible extension points, and upstream attribution text. There is no child theme in the dev stacks. 26 (templates) plus the `library/` occurrences of the `'foundationpress'` text domain were switched to `'hyperpress'`; `languages/FoundationPress.pot` was not renamed.
- Guards: 68 redeclaration `function_exists` wrappers were removed from 47 files; one real optional dependency was kept and commented (`add_revslider`). Redundant checks for `hyperpress_pagination` and `the_custom_logo` remain as a possible follow-up.
- Standards: 1417 violations down to 0 (`composer lint` gates). The ruleset excludes docblock-presence sniffs, `Generic.Files.LineEndings.InvalidEOLChar` (mixed CRLF/LF working tree), two filename sniffs, `.superpowers` and `tests/`; it no longer carries the stale `WordPress.XSS.EscapeOutput.*` entries. 84 `// phpcs:ignore ... -- theme audit` suppressions mark security-class and quality findings for theme-05-audit (`grep -rn 'theme audit' --include='*.php' .`).
- `library/foundation.php` was reformatted the most (braces added, arrays and calls wrapped); reviewed: non-whitespace changes are equivalent. A manual check of pagination, the missing-menu notice and the gallery shortcode is still worth doing.
- README and AGENTS.md updated.
