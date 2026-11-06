# NextGEN Legacy Templates on Foundation 6 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Move the theme's two NextGEN legacy template overrides (`nggallery/gallery.php`, `nggallery/album-compact.php`) to clean, escaped Foundation 6 markup, with `gallery.php` following the gallery's `columns` setting.

**Architecture:** Both files stay NextGEN legacy overrides (used only when a display's "Template" setting selects the parent theme). `gallery.php` becomes an XY grid of `cell > a.thumbnail > img` without any `ngg-*` classes or NextGEN's inline `style`. `album-compact.php` is re-nested as `cell > card > card-section` with flex utilities for equal heights. Both print NextGEN's pagination through a small helper, `hyperpress_nextgen_pagination()`, that rebuilds it as Foundation `ul.pagination` markup. Both are covered by integration render tests that stub the NextGEN classes.

**Tech Stack:** PHP 8.3 (WordPress, PHPUnit integration tests in Docker), Foundation 6.9 (XY grid, Card, Thumbnail, flex utilities).

**Spec:** `docs/superpowers/specs/2026-11-05-theme-nggallery-foundation-templates-design.md`

## Global Constraints

Theme root is `themes/hyperpress-foundation/` (paths below are relative to it unless prefixed `docs/`).

- Starting point: both `nggallery/*.php` files have uncommitted working-tree changes. `gallery.php` is NextGEN 4.5.5's own `src/Legacy/view/gallery.php`, reformatted (ABSPATH guard, `use` statements, `esc_attr` / `esc_html`, `phpcs:ignore` notes), and `album-compact.php` has a small edit. The task commits below include the whole files, so those changes land with them. Read the files before editing; do not revert them first.
- NextGEN 4.5.5 builds the `<img>` attributes with `\Imagely\NGG\Display\GalleryImage::attributes( $extra )` (adds `class="ngg-gallery-thumbnail-img"` and runs the `ngg_gallery_image_attributes` filter that site owners and lazy-load plugins use). Keep calling it so that filter keeps working; pass `loading="lazy"` through it as `$extra`. No NextGEN CSS or JS targets that class.
- NextGEN's `static/Thumbnails/ajax_pagination.js` handles clicks on `a.page-numbers, a.prev, a.next`, finds the gallery through the ancestor `.ngg-galleryoverview` and its `id`, reads `data-pageid`, then replaces that element with the new page's `div[id*="ngg-gallery-<id>"]`. So `gallery.php` keeps `class="ngg-galleryoverview"` and the anchor `id` on its outer wrapper (the grid inside has no `ngg-*` class), the pagination stays inside that wrapper, and the converted pagination keeps every anchor's NextGEN classes and attributes.
- NextGEN's pagination (`DisplayType/Controller.php` `create_pagination()`) is `<div class='ngg-navigation'>` with `span.current`, `a.page-numbers[data-pageid]`, `span.ellipsis` and prev/next anchors whose classes are swapped (`class='next'` is the previous link). Templates only receive that HTML string (plus `pagination_next` / `pagination_prev` URLs), not the page count.
- Commit with explicit paths (`git add <paths>` then `git commit -m "..." -- <paths>`), never `git add -A`. Commit only when the owner asks.
- Commit message style: `type(scope): lowercase summary`, for example `refactor(theme): ...`.
- Tool setup per shell call: `source ~/.nvm/nvm.sh && nvm use && export PATH="$HOME/.local/share/pnpm/bin:$PATH"` (needed for `pnpm`; PHP runs only in Docker).
- PHP tooling from `themes/hyperpress-foundation/`: `docker compose run --rm php composer test:integration -- --filter NggalleryTemplatesTest`. `lint` must stay at 0 violations. `tests/` is excluded from PHPCS.
- Escaping: `esc_attr` / `esc_html` / `wp_kses_post` for text. `Router::esc_url()` (NextGEN's own escaper), `$image->thumbcode`, `$image->size` and `$pagination` are NextGEN-built and stay raw with a `phpcs:ignore ... -- escaped by NextGen` note.
- Out of scope: the modern templates, srcset, and any change to the NextGEN or `hyperpress-media` plugins. The other legacy templates and the random-image widget are in the follow-up plan `docs/superpowers/plans/2026-11-05-theme-nggallery-remaining-templates.md`.

## File Structure

| File                                               | Change                                         |
| -------------------------------------------------- | ---------------------------------------------- |
| `library/helpers.php`                              | Modify: add `hyperpress_nextgen_pagination()`  |
| `tests/integration/NextGenPaginationTest.php`      | Create: tests for the pagination helper        |
| `tests/integration/stubs/nextgen.php`              | Create: stub `Router` and `I18N` for the tests |
| `tests/integration/NggalleryTemplatesTest.php`     | Create: render tests for both templates        |
| `nggallery/gallery.php`                            | Rewrite                                        |
| `nggallery/album-compact.php`                      | Rewrite                                        |
| `docs/superpowers/audit/2026-10-02-theme-audit.md` | Update the T42 notes (lines 85, 348, 720)      |

---

### Task 0: Foundation pagination helper

NextGEN builds its pagination as one HTML string; this helper reshapes it into [Foundation pagination](https://get.foundation/sites/docs/pagination.html) markup for both templates.

**Files:**

- Create: `tests/integration/NextGenPaginationTest.php`
- Modify: `library/helpers.php` (next to `hyperpress_nextgen_ratio()`)

- [x] **Step 1: Write the failing tests**

```php
<?php
namespace HyperPress\ThemeTests\Integration;

use WP_UnitTestCase;

final class NextGenPaginationTest extends WP_UnitTestCase {

	private const SAMPLE = "<div class='ngg-navigation'><a class='next' data-pageid='1' href='?nggpage=1'>&#9668;</a><a class='page-numbers' data-pageid='1' href='?nggpage=1'>1</a><span class='current'>2</span><a class='page-numbers' data-pageid='3' href='?nggpage=3'>3</a><span class='ellipsis'>...</span><a class='page-numbers' data-pageid='9' href='?nggpage=9'>9</a><a class='prev' data-pageid='3' href='?nggpage=3'>&#9658;</a></div>";

	public function test_it_builds_foundation_pagination_markup(): void {
		$html = hyperpress_nextgen_pagination( self::SAMPLE );

		$this->assertStringStartsWith( '<nav aria-label="Pagination"><ul class="pagination text-center">', $html );
		$this->assertStringEndsWith( '</ul></nav>', $html );
		$this->assertStringContainsString( '<li class="current" aria-current="page"><span class="show-for-sr">You are on page</span> 2</li>', $html );
		$this->assertStringContainsString( '<li class="ellipsis" aria-hidden="true"></li>', $html );
	}

	public function test_it_keeps_the_classes_and_attributes_nextgens_ajax_script_binds_to(): void {
		$html = hyperpress_nextgen_pagination( self::SAMPLE );

		$this->assertStringContainsString( "<li><a class='page-numbers' data-pageid='3' href='?nggpage=3'>3</a></li>", $html );
		$this->assertStringContainsString( "class='next'", $html );
		$this->assertStringContainsString( "class='prev'", $html );
	}

	public function test_it_labels_previous_and_next_by_page_number_not_by_nextgens_swapped_classes(): void {
		$html = hyperpress_nextgen_pagination( self::SAMPLE );

		$this->assertStringContainsString( "<a class='next' data-pageid='1' href='?nggpage=1' aria-label=\"Previous page\">", $html );
		$this->assertStringContainsString( "<a class='prev' data-pageid='3' href='?nggpage=3' aria-label=\"Next page\">", $html );
	}

	public function test_it_leaves_unrecognised_markup_unchanged(): void {
		$inputs = array(
			'',
			'<p>nothing</p>',
			"<div class='ngg-navigation'><a class='page-numbers' data-pageid='2' href='?p=2'>2</a></div>",
			"<div class='ngg-navigation'><span class='current'>1</span><span class='mystery'>x</span></div>",
		);

		foreach ( $inputs as $input ) {
			$this->assertSame( $input, hyperpress_nextgen_pagination( $input ) );
		}
	}
}
```

- [x] **Step 2: Run the tests and confirm they fail**

Run: `docker compose run --rm php composer test:integration -- --filter NextGenPaginationTest`
Expected: FAIL with `Call to undefined function hyperpress_nextgen_pagination()`.

- [x] **Step 3: Add the helper to `library/helpers.php`**

```php
/**
 * Rebuild NextGEN's pagination HTML as Foundation 6 pagination markup.
 *
 * Anchors keep NextGEN's own classes and data attributes, because its AJAX pagination script binds to them.
 * Previous/next links are labelled by page number: NextGEN's own prev/next class names are swapped.
 * Returns the input unchanged when it is not the expected NextGEN markup.
 *
 * @param string $html NextGEN's `$pagination` string.
 * @return string Foundation pagination HTML, or $html when it is not recognised.
 */
function hyperpress_nextgen_pagination( string $html ): string {
	if ( ! preg_match_all( '#<(a|span)\b([^>]*)>(.*?)</\1>#is', $html, $items, PREG_SET_ORDER ) ) {
		return $html;
	}

	$current = 0;
	foreach ( $items as $item ) {
		if ( 'span' === strtolower( $item[1] ) && preg_match( '/class=([\'"])current\1/i', $item[2] ) ) {
			$current = (int) $item[3];
		}
	}
	if ( $current < 1 ) {
		return $html;
	}

	$list = '';
	foreach ( $items as $item ) {
		$attrs = $item[2];

		if ( 'a' === strtolower( $item[1] ) ) {
			$page = preg_match( '/data-pageid=([\'"])(\d+)\1/i', $attrs, $match ) ? (int) $match[2] : 0;
			if ( $page > 0 && preg_match( '/class=([\'"])(?:next|prev)\1/i', $attrs ) ) {
				$label  = $page < $current ? __( 'Previous page', 'hyperpress' ) : __( 'Next page', 'hyperpress' );
				$attrs .= ' aria-label="' . esc_attr( $label ) . '"';
			}
			$list .= '<li><a' . $attrs . '>' . $item[3] . '</a></li>';
		} elseif ( preg_match( '/class=([\'"])current\1/i', $attrs ) ) {
			$list .= '<li class="current" aria-current="page"><span class="show-for-sr">' . esc_html__( 'You are on page', 'hyperpress' ) . '</span> ' . $current . '</li>';
		} elseif ( preg_match( '/class=([\'"])ellipsis\1/i', $attrs ) ) {
			$list .= '<li class="ellipsis" aria-hidden="true"></li>';
		} else {
			return $html;
		}
	}

	return '<nav aria-label="' . esc_attr__( 'Pagination', 'hyperpress' ) . '"><ul class="pagination text-center">' . $list . '</ul></nav>';
}
```

- [x] **Step 4: Run the tests and lint**

Run: `docker compose run --rm php composer test:integration -- --filter NextGenPaginationTest` then `docker compose run --rm php composer lint`
Expected: PASS and 0 violations. The helper output contains NextGEN-built anchor attributes and link text; the templates pass it through `wp_kses_post()`, so the helper itself needs no escaping beyond what it adds.

- [x] **Step 5: Tick the Task 0 boxes in this plan and commit**

```sh
git add themes/hyperpress-foundation/library/helpers.php themes/hyperpress-foundation/tests/integration/NextGenPaginationTest.php docs/superpowers/plans/2026-11-05-theme-nggallery-foundation-templates.md
git commit -m "feat(theme): foundation pagination markup for nextgen galleries" -- themes/hyperpress-foundation/library/helpers.php themes/hyperpress-foundation/tests/integration/NextGenPaginationTest.php docs/superpowers/plans/2026-11-05-theme-nggallery-foundation-templates.md
```

### Task 1: Test harness and `gallery.php` tests (red)

**Files:**

- Create: `tests/integration/stubs/nextgen.php`
- Create: `tests/integration/NggalleryTemplatesTest.php`

- [ ] **Step 1: Write the NextGEN stubs**

```php
<?php
// Minimal stand-ins for the NextGEN classes the legacy templates call.
// NextGEN is not installed in the theme's test environment.

namespace Imagely\NGG\Util {
	if ( ! class_exists( Router::class, false ) ) {
		class Router {
			public static function esc_url( $url ) {
				return esc_url( (string) $url );
			}
		}
	}
}

namespace Imagely\NGG\Display {
	if ( ! class_exists( I18N::class, false ) ) {
		class I18N {
			public static function ngg_plain_text_alt_title_attributes( $text ) {
				return wp_strip_all_tags( (string) $text );
			}
		}
	}

	if ( ! class_exists( GalleryImage::class, false ) ) {
		class GalleryImage {
			public static function attributes( $extra = array() ) {
				$attributes = apply_filters( 'ngg_gallery_image_attributes', array_merge( array( 'class' => 'ngg-gallery-thumbnail-img' ), $extra ) );
				$html       = '';
				foreach ( $attributes as $name => $value ) {
					if ( false === $value || null === $value ) {
						continue;
					}
					$html .= true === $value ? ' ' . esc_attr( $name ) : ' ' . esc_attr( $name ) . '="' . esc_attr( $value ) . '"';
				}
				return $html;
			}
		}
	}
}
```

- [ ] **Step 2: Write the test file with the `gallery.php` tests**

```php
<?php
namespace HyperPress\ThemeTests\Integration;

use WP_UnitTestCase;

require_once __DIR__ . '/stubs/nextgen.php';

final class NggalleryTemplatesTest extends WP_UnitTestCase {

	private function render( string $file, array $vars ): string {
		$path = dirname( __DIR__, 2 ) . '/nggallery/' . $file;
		ob_start();
		( static function ( string $template_file, array $template_vars ): void {
			extract( $template_vars ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
			include $template_file;
		} )( $path, $vars );
		return (string) ob_get_clean();
	}

	private function gallery( array $over = array() ): object {
		return (object) array_merge(
			array(
				'anchor'              => 'ngg-gallery-1-1',
				'columns'             => 4,
				'show_slideshow'      => false,
				'slideshow_link'      => 'https://example.test/?show=slide',
				'slideshow_link_text' => '[Show slideshow]',
			),
			$over
		);
	}

	private function image( array $over = array() ): object {
		return (object) array_merge(
			array(
				'pid'          => 5,
				'imageURL'     => 'https://example.test/full-5.jpg',
				'thumbnailURL' => 'https://example.test/thumb-5.jpg',
				'description'  => 'A caption',
				'alttext'      => 'Alt <b>text</b>',
				'thumbcode'    => 'class="lightbox" data-image-id="5"',
				'size'         => "width='240' height='160'",
				'style'        => 'style="width:25%;"',
				'hidden'       => false,
			),
			$over
		);
	}

	/** @return array<string,array{int,string}> */
	public static function columns(): array {
		return array(
			'four'     => array( 4, 'small-up-2 medium-up-3 large-up-4' ),
			'one'      => array( 1, 'small-up-1 medium-up-1 large-up-1' ),
			'two'      => array( 2, 'small-up-2 medium-up-2 large-up-2' ),
			'zero'     => array( 0, 'small-up-2 medium-up-3 large-up-4' ),
			'negative' => array( -3, 'small-up-2 medium-up-3 large-up-4' ),
			'too many' => array( 12, 'small-up-2 medium-up-3 large-up-8' ),
		);
	}

	/** @dataProvider columns */
	public function test_gallery_grid_follows_columns( int $columns, string $expected ): void {
		$html = $this->render(
			'gallery.php',
			array(
				'gallery'    => $this->gallery( array( 'columns' => $columns ) ),
				'images'     => array( $this->image() ),
				'pagination' => '',
			)
		);

		$this->assertStringContainsString( 'grid-x grid-margin-x ' . $expected, $html );
	}

	public function test_gallery_renders_a_visible_image_as_a_foundation_thumbnail(): void {
		$html = $this->render(
			'gallery.php',
			array(
				'gallery'    => $this->gallery(),
				'images'     => array( $this->image() ),
				'pagination' => '',
			)
		);

		$this->assertStringContainsString( '<div class="cell" id="ngg-image-5">', $html );
		$this->assertStringContainsString( 'class="thumbnail"', $html );
		$this->assertStringContainsString( 'data-image-id="5"', $html );
		$this->assertStringContainsString( 'loading="lazy"', $html );
		$this->assertStringContainsString( "width='240' height='160'", $html );
		$this->assertStringContainsString( 'alt="Alt text"', $html );
		$this->assertStringContainsString( 'src="https://example.test/thumb-5.jpg"', $html );
	}

	public function test_gallery_image_attributes_go_through_the_nextgen_filter(): void {
		$add = static fn( array $attributes ): array => $attributes + array( 'data-no-lazy' => '1' );
		add_filter( 'ngg_gallery_image_attributes', $add );

		$html = $this->render(
			'gallery.php',
			array(
				'gallery'    => $this->gallery(),
				'images'     => array( $this->image() ),
				'pagination' => '',
			)
		);

		remove_filter( 'ngg_gallery_image_attributes', $add );

		$this->assertMatchesRegularExpression( '/<img [^>]*class="ngg-gallery-thumbnail-img"[^>]*data-no-lazy="1"/', $html );
		$this->assertStringContainsString( 'loading="lazy"', $html );
	}

	public function test_gallery_drops_legacy_nextgen_markup(): void {
		$html = $this->render(
			'gallery.php',
			array(
				'gallery'    => $this->gallery(),
				'images'     => array( $this->image(), $this->image( array( 'pid' => 6 ) ) ),
				'pagination' => '',
			)
		);

		$this->assertStringNotContainsString( 'ngg-gallery-thumbnail-box', $html );
		$this->assertStringNotContainsString( 'class="ngg-gallery-thumbnail"', $html );
		$this->assertStringContainsString( '<div class="ngg-galleryoverview" id="ngg-gallery-1-1">', $html );
		$this->assertStringNotContainsString( 'grid-x grid-margin-x ngg-', $html );
		$this->assertStringNotContainsString( 'style=', $html );
		$this->assertStringNotContainsString( '<br', $html );
	}

	public function test_gallery_puts_hidden_images_outside_the_grid_without_an_img(): void {
		$html = $this->render(
			'gallery.php',
			array(
				'gallery'    => $this->gallery(),
				'images'     => array( $this->image(), $this->image( array( 'pid' => 9, 'hidden' => true ) ) ),
				'pagination' => '',
			)
		);

		$this->assertSame( 1, substr_count( $html, '<img' ) );
		$this->assertMatchesRegularExpression( '/<div hidden>\s*<a [^>]*id="ngg-image-9"[^>]*data-image-id="5"/s', $html );
		$this->assertLessThan( strpos( $html, '<div hidden>' ), strrpos( $html, 'id="ngg-image-5"' ) );
	}

	public function test_gallery_escapes_plain_text_values(): void {
		$html = $this->render(
			'gallery.php',
			array(
				'gallery'    => $this->gallery(
					array(
						'anchor'              => '"><script>alert(1)</script>',
						'show_slideshow'      => true,
						'slideshow_link_text' => '<img src=x onerror=alert(1)>',
					)
				),
				'images'     => array( $this->image( array( 'description' => '" onmouseover="x' ) ) ),
				'pagination' => '',
			)
		);

		$this->assertStringNotContainsString( '<script>', $html );
		$this->assertStringNotContainsString( '<img src=x', $html );
		$this->assertStringNotContainsString( '" onmouseover="x', $html );
		$this->assertStringContainsString( '&lt;img src=x onerror=alert(1)&gt;', $html );
	}

	public function test_gallery_shows_the_slideshow_link_only_when_enabled(): void {
		$vars = array(
			'images'     => array( $this->image() ),
			'pagination' => '',
		);

		$off = $this->render( 'gallery.php', $vars + array( 'gallery' => $this->gallery() ) );
		$on  = $this->render( 'gallery.php', $vars + array( 'gallery' => $this->gallery( array( 'show_slideshow' => true ) ) ) );

		$this->assertStringNotContainsString( 'Show slideshow', $off );
		$this->assertStringContainsString( 'class="button small hollow" href="https://example.test/?show=slide"', $on );
		$this->assertStringContainsString( '[Show slideshow]', $on );
	}

	public function test_gallery_prints_foundation_pagination_inside_the_wrapper(): void {
		$html = $this->render(
			'gallery.php',
			array(
				'gallery'    => $this->gallery(),
				'images'     => array( $this->image() ),
				'pagination' => "<div class='ngg-navigation'><span class='current'>1</span><a class='page-numbers' data-pageid='2' href='?nggpage=2'>2</a></div>",
			)
		);

		$this->assertStringContainsString( '<ul class="pagination text-center">', $html );
		$this->assertStringContainsString( 'class="page-numbers" data-pageid="2"', $html );
		$this->assertLessThan( strrpos( $html, '</div>' ), strpos( $html, 'class="pagination' ) );
	}

	public function test_gallery_shows_a_callout_when_it_has_no_images(): void {
		$html = $this->render( 'gallery.php', array( 'gallery' => $this->gallery(), 'images' => array(), 'pagination' => '' ) );

		$this->assertStringContainsString( 'callout secondary', $html );
		$this->assertStringNotContainsString( 'grid-x', $html );
	}

	public function test_gallery_renders_nothing_without_a_gallery(): void {
		$this->assertSame(
			'',
			trim( $this->render( 'gallery.php', array( 'gallery' => null, 'images' => array(), 'pagination' => '' ) ) )
		);
	}
}
```

- [ ] **Step 3: Run the tests and confirm the `gallery.php` ones fail**

Run: `docker compose run --rm php composer test:integration -- --filter NggalleryTemplatesTest`
Expected: the `test_gallery_*` tests FAIL (current markup has `ngg-*` classes, `style=`, `<br`, no grid). `test_gallery_renders_nothing_without_a_gallery` may already pass. If the run errors on a missing stub class instead, fix the stub or the `require_once` path before continuing.

- [ ] **Step 4: Check the box and commit the red tests together with Task 2 (do not commit failing tests alone)**

No commit in this task.

### Task 2: Rewrite `gallery.php` (green)

**Files:**

- Modify: `nggallery/gallery.php`

- [ ] **Step 1: Replace the file content**

```php
<?php
/**
 * Template for the NextGEN gallery overview (Foundation 6 grid).
 *
 * The outer wrapper keeps NextGEN's `ngg-galleryoverview` class and the anchor id:
 * NextGEN's AJAX pagination script finds and replaces the gallery through them.
 *
 * @var object $gallery    Gallery data (anchor, columns, slideshow link settings).
 * @var array  $images     Image objects (pid, imageURL, thumbnailURL, thumbcode, size, hidden, ...).
 * @var string $pagination NextGEN's pagination HTML.
 */

// Don't load directly.
defined( 'ABSPATH' ) || exit;

use \Imagely\NGG\Display\GalleryImage;
use \Imagely\NGG\Display\I18N;
use \Imagely\NGG\Util\Router;

?>

<?php if ( ! empty( $gallery ) ) : ?>
	<?php
	$columns = max( 0, min( 8, (int) $gallery->columns ) );
	$up      = $columns > 0 ? array( min( 2, $columns ), min( 3, $columns ), $columns ) : array( 2, 3, 4 );
	$hidden  = array();
	?>

	<div class="ngg-galleryoverview" id="<?php echo esc_attr( $gallery->anchor ); ?>">

		<?php if ( $gallery->show_slideshow ) : ?>
			<p class="text-center"><a class="button small hollow" href="<?php echo Router::esc_url( $gallery->slideshow_link ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by NextGen's Router::esc_url() ?>"><?php echo esc_html( $gallery->slideshow_link_text ); ?></a></p>
		<?php endif; ?>

		<?php if ( empty( $images ) ) : ?>
			<div class="callout secondary"><?php esc_html_e( 'There are no photos in this gallery.', 'hyperpress' ); ?></div>
		<?php else : ?>
		<div class="grid-x grid-margin-x small-up-<?php echo (int) $up[0]; ?> medium-up-<?php echo (int) $up[1]; ?> large-up-<?php echo (int) $up[2]; ?>">
			<?php foreach ( $images as $image ) : ?>
				<?php
				if ( $image->hidden ) {
					$hidden[] = $image;
					continue;
				}
				?>
				<div class="cell" id="ngg-image-<?php echo (int) $image->pid; ?>">
					<a class="thumbnail" href="<?php echo Router::esc_url( $image->imageURL ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by NextGen's Router::esc_url() ?>"
						title="<?php echo esc_attr( $image->description ); ?>"
						<?php echo $image->thumbcode; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- lightbox attributes built by NextGen ?>>
						<img<?php echo GalleryImage::attributes( array( 'loading' => 'lazy' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- attributes() escapes each attribute with esc_attr() ?>
							src="<?php echo Router::esc_url( $image->thumbnailURL ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by NextGen's Router::esc_url() ?>"
							alt="<?php echo esc_attr( I18N::ngg_plain_text_alt_title_attributes( $image->alttext ) ); ?>"
							<?php echo $image->size; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- width/height attributes built by NextGen ?>>
					</a>
				</div>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>

		<?php if ( ! empty( $hidden ) ) : ?>
			<div hidden>
				<?php foreach ( $hidden as $image ) : ?>
					<a id="ngg-image-<?php echo (int) $image->pid; ?>"
						href="<?php echo Router::esc_url( $image->imageURL ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by NextGen's Router::esc_url() ?>"
						title="<?php echo esc_attr( $image->description ); ?>"
						<?php echo $image->thumbcode; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- lightbox attributes built by NextGen ?>></a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<?php echo wp_kses_post( hyperpress_nextgen_pagination( (string) $pagination ) ); ?>

	</div>

<?php endif; ?>
```

- [ ] **Step 2: Run the tests**

Run: `docker compose run --rm php composer test:integration -- --filter NggalleryTemplatesTest`
Expected: all `test_gallery_*` PASS. If `test_gallery_puts_hidden_images_outside_the_grid_without_an_img` fails on the ordering assertion, check that the hidden block comes after the grid; adjust the assertion's needle, not the markup.

- [ ] **Step 3: Lint**

Run: `docker compose run --rm php composer lint`
Expected: 0 violations. Fix any new `phpcs:ignore` needs with a `-- <reason>` note.

- [ ] **Step 4: Tick the Task 1 and Task 2 boxes in this plan and commit**

```sh
git add themes/hyperpress-foundation/nggallery/gallery.php themes/hyperpress-foundation/tests/integration/stubs/nextgen.php themes/hyperpress-foundation/tests/integration/NggalleryTemplatesTest.php docs/superpowers/plans/2026-11-05-theme-nggallery-foundation-templates.md
git commit -m "refactor(theme): nextgen gallery template on the foundation grid" -- themes/hyperpress-foundation/nggallery/gallery.php themes/hyperpress-foundation/tests/integration/stubs/nextgen.php themes/hyperpress-foundation/tests/integration/NggalleryTemplatesTest.php docs/superpowers/plans/2026-11-05-theme-nggallery-foundation-templates.md
```

### Task 3: `album-compact.php` tests (red)

The theme's earlier design (callout header plus a card per gallery, kept in git `HEAD`) is rebuilt on top of NextGEN 4.5.5's own template logic: lightbox option, `$pagination` and `$image_gen_params` are honoured, and the Foundation presentation replaces the `ngg-*` markup.

**Files:**

- Modify: `tests/integration/NggalleryTemplatesTest.php`

- [ ] **Step 1: Add helpers and tests to the test class** (before the closing brace)

```php
	private function album( array $over = array() ): object {
		return (object) array_merge(
			array(
				'name'      => 'Spring <i>2026</i>',
				'albumdesc' => '<strong>Photos</strong><script>x()</script>',
			),
			$over
		);
	}

	private function album_gallery( array $over = array() ): object {
		return (object) array_merge(
			array(
				'title'                                => 'Match day',
				'pagelink'                             => 'https://example.test/gallery/match-day',
				'previewurl'                           => 'https://example.test/preview.jpg',
				'previewpic'                           => 77,
				'previewpic_fullsized_url'             => 'https://example.test/full-77.jpg',
				'previewpic_fullsized_display_url'     => 'https://example.test/full-77.jpg?v=2',
				'previewpic_image'                     => (object) array(
					'alttext'     => 'Cover alt',
					'description' => 'Cover caption',
				),
				'entity_type'                          => 'gallery',
				'displayed_gallery'                    => (object) array( 'effect_code' => 'class="lightbox" rel="x"' ),
				'counter'                              => 12,
				'author'                               => 0,
				'galdesc'                              => 'Gallery <em>notes</em>',
			),
			$over
		);
	}

	private function album_vars( array $over = array() ): array {
		return array_merge(
			array(
				'album'            => $this->album(),
				'galleries'        => array( $this->album_gallery() ),
				'pagination'       => '',
				'image_gen_params' => array(
					'width'  => 300,
					'height' => 200,
				),
			),
			$over
		);
	}

	public function test_album_uses_the_foundation_card_structure(): void {
		$html = $this->render( 'album-compact.php', $this->album_vars() );

		$this->assertStringContainsString( 'grid-x grid-margin-x small-up-1 medium-up-2 large-up-3 xlarge-up-4', $html );
		$this->assertStringContainsString( '<div class="cell grid-y">', $html );
		$this->assertStringContainsString( '<div class="card flex-child-auto">', $html );
		$this->assertStringContainsString( '<div class="card-section flex-child-auto">', $html );
		foreach ( array( 'card-image', 'align-stretch', 'align-bottom', 'small-12', 'ngg-album', 'class="Thumb"' ) as $legacy ) {
			$this->assertStringNotContainsString( $legacy, $html );
		}
	}

	public function test_album_image_links_to_the_gallery_with_alt_and_lazy_loading(): void {
		$html = $this->render( 'album-compact.php', $this->album_vars() );

		$this->assertMatchesRegularExpression( '/<a href="https:\/\/example\.test\/gallery\/match-day">\s*<img [^>]*src="https:\/\/example\.test\/preview\.jpg"[^>]*alt="Match day"[^>]*loading="lazy"/', $html );
	}

	public function test_album_opens_the_preview_in_the_lightbox_when_the_option_is_on(): void {
		$html = $this->render( 'album-compact.php', $this->album_vars( array( 'open_gallery_in_lightbox' => 1 ) ) );

		$this->assertMatchesRegularExpression( '/<a class="lightbox" rel="x"\s+href="https:\/\/example\.test\/full-77\.jpg"/', $html );
		$this->assertStringContainsString( 'data-src="https://example.test/full-77.jpg?v=2"', $html );
		$this->assertStringContainsString( 'data-title="Cover alt"', $html );
		$this->assertStringContainsString( 'data-description="Cover caption"', $html );
		$this->assertStringContainsString( 'data-image-id="77"', $html );
		$this->assertStringContainsString( '<a href="https://example.test/gallery/match-day">Match day</a>', $html );
	}

	public function test_album_links_albums_to_their_page_even_with_the_lightbox_option(): void {
		$html = $this->render(
			'album-compact.php',
			$this->album_vars(
				array(
					'open_gallery_in_lightbox' => 1,
					'galleries'                => array( $this->album_gallery( array( 'entity_type' => 'album' ) ) ),
				)
			)
		);

		$this->assertStringNotContainsString( 'data-image-id', $html );
		$this->assertMatchesRegularExpression( '/<a href="https:\/\/example\.test\/gallery\/match-day">\s*<img /', $html );
	}

	public function test_album_falls_back_to_the_page_link_when_the_lightbox_data_is_missing(): void {
		$gallery = $this->album_gallery();
		unset( $gallery->previewpic_fullsized_url, $gallery->displayed_gallery );

		$html = $this->render(
			'album-compact.php',
			$this->album_vars(
				array(
					'open_gallery_in_lightbox' => 1,
					'galleries'                => array( $gallery ),
				)
			)
		);

		$this->assertStringNotContainsString( 'data-image-id', $html );
		$this->assertMatchesRegularExpression( '/<a href="https:\/\/example\.test\/gallery\/match-day">\s*<img /', $html );
	}

	public function test_album_placeholder_uses_the_generated_thumbnail_ratio(): void {
		$html = $this->render( 'album-compact.php', $this->album_vars( array( 'galleries' => array( $this->album_gallery( array( 'previewurl' => '' ) ) ) ) ) );

		$this->assertStringNotContainsString( '<img', $html );
		$this->assertStringContainsString( 'style="aspect-ratio:1.5"', $html );
	}

	public function test_album_placeholder_survives_missing_image_gen_params(): void {
		$vars = $this->album_vars( array( 'galleries' => array( $this->album_gallery( array( 'previewurl' => '' ) ) ) ) );
		unset( $vars['image_gen_params'] );

		$this->assertStringContainsString( 'style="aspect-ratio:1"', $this->render( 'album-compact.php', $vars ) );
	}

	public function test_album_escapes_and_sanitizes_text(): void {
		$html = $this->render( 'album-compact.php', $this->album_vars( array( 'galleries' => array( $this->album_gallery( array( 'title' => '<script>x()</script>' ) ) ) ) ) );

		$this->assertStringNotContainsString( '<script>', $html );
		$this->assertStringContainsString( 'Spring &lt;i&gt;2026&lt;/i&gt;', $html );
		$this->assertStringContainsString( '<strong>Photos</strong>', $html );
	}

	public function test_album_shows_the_photo_count_and_author_label(): void {
		$user = self::factory()->user->create( array( 'display_name' => 'Ann Author' ) );
		$html = $this->render( 'album-compact.php', $this->album_vars( array( 'galleries' => array( $this->album_gallery( array( 'author' => $user ) ) ) ) ) );

		$this->assertStringContainsString( '<strong>12</strong>', $html );
		$this->assertStringContainsString( 'Ann Author', $html );
	}

	public function test_album_omits_the_author_label_for_an_unknown_user(): void {
		$html = $this->render( 'album-compact.php', $this->album_vars( array( 'galleries' => array( $this->album_gallery( array( 'author' => 999999 ) ) ) ) ) );

		$this->assertStringNotContainsString( 'label warning', $html );
	}

	public function test_album_prints_the_pagination_after_the_grid(): void {
		$html = $this->render( 'album-compact.php', $this->album_vars( array( 'pagination' => "<div class='ngg-navigation'><span class='current'>1</span><a class='page-numbers' data-pageid='2' href='?nggpage=2'>2</a></div>" ) ) );

		$this->assertStringContainsString( '<ul class="pagination text-center">', $html );
		$this->assertGreaterThan( strrpos( $html, 'card-section' ), strpos( $html, 'class="pagination' ) );
	}

	public function test_album_renders_the_grid_without_an_album_header(): void {
		$html = $this->render( 'album-compact.php', $this->album_vars( array( 'album' => null ) ) );

		$this->assertStringNotContainsString( 'album-info', $html );
		$this->assertStringContainsString( 'gallery-grid', $html );
	}

	public function test_album_shows_a_callout_when_it_has_no_galleries(): void {
		$html = $this->render( 'album-compact.php', $this->album_vars( array( 'galleries' => array() ) ) );

		$this->assertStringContainsString( 'callout secondary', $html );
		$this->assertStringNotContainsString( 'gallery-grid', $html );
	}

	public function test_album_renders_nothing_without_an_album_or_galleries(): void {
		$this->assertSame( '', trim( $this->render( 'album-compact.php', $this->album_vars( array( 'album' => null, 'galleries' => array() ) ) ) ) );
	}
```

- [ ] **Step 2: Run the tests and confirm the card-structure test fails**

Run: `docker compose run --rm php composer test:integration -- --filter NggalleryTemplatesTest`
Expected: the `test_album_*` tests FAIL against the stock-style markup (no cards, `ngg-album-*` classes, no `loading`, and the author label and placeholder are missing). A test that errors on an undefined index in the stock template is also a valid red; do not weaken the test to avoid it.

### Task 4: Rewrite `album-compact.php` (green)

**Files:**

- Modify: `nggallery/album-compact.php`

- [ ] **Step 1: Replace the file content**

```php
<?php
/**
 * Template for Compact Album displays (Foundation 6 cards).
 * Logic follows NextGEN 4.5.5's src/Legacy/view/album-compact.php.
 *
 * @var object|null $album                    The album (name, albumdesc); not set for every source.
 * @var array       $galleries                The galleries inside the album.
 * @var string      $pagination               NextGEN's pagination HTML.
 * @var array       $image_gen_params         Expected preview size (width, height).
 * @var int|bool    $open_gallery_in_lightbox Display setting: open a gallery's preview in the lightbox.
 */

// Don't load directly.
defined( 'ABSPATH' ) || exit;

use \Imagely\NGG\Util\Router;

?>

<?php if ( ! empty( $album ) ) : ?>
	<div class="album-info callout primary">
		<?php if ( ! empty( $album->name ) ) : ?>
			<h5><?php echo esc_html( $album->name ); ?></h5>
		<?php endif; ?>
		<?php if ( ! empty( $album->albumdesc ) ) : ?>
			<p><?php echo wp_kses_post( $album->albumdesc ); ?></p>
		<?php endif; ?>
	</div>
<?php endif; ?>

<?php if ( ! empty( $galleries ) ) : ?>
	<div class="gallery-grid grid-x grid-margin-x small-up-1 medium-up-2 large-up-3 xlarge-up-4">
		<?php foreach ( $galleries as $gallery ) : ?>
			<?php
			$author_name = '';
			if ( ! empty( $gallery->author ) ) {
				$author      = get_userdata( $gallery->author );
				$author_name = $author ? (string) $author->display_name : '';
			}
			$use_lightbox = ! empty( $open_gallery_in_lightbox )
				&& 'gallery' === ( $gallery->entity_type ?? '' )
				&& ! empty( $gallery->previewpic_fullsized_url )
				&& ! empty( $gallery->displayed_gallery );
			?>
			<div class="cell grid-y">
				<div class="card flex-child-auto">
					<?php if ( ! empty( $gallery->previewurl ) ) : ?>
						<?php if ( $use_lightbox ) : ?>
							<a <?php echo $gallery->displayed_gallery->effect_code ?? ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- lightbox attributes built by NextGen ?>
								href="<?php echo esc_attr( $gallery->previewpic_fullsized_url ); ?>"
								data-fullsize="<?php echo esc_attr( $gallery->previewpic_fullsized_display_url ?? '' ); ?>"
								data-src="<?php echo esc_attr( $gallery->previewpic_fullsized_display_url ?? '' ); ?>"
								data-thumbnail="<?php echo esc_attr( $gallery->previewurl ); ?>"
								data-title="<?php echo esc_attr( $gallery->previewpic_image->alttext ?? '' ); ?>"
								data-description="<?php echo esc_attr( stripslashes( $gallery->previewpic_image->description ?? '' ) ); ?>"
								data-image-id="<?php echo esc_attr( $gallery->previewpic ); ?>">
						<?php else : ?>
							<a href="<?php echo Router::esc_url( $gallery->pagelink ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by NextGen's Router::esc_url() ?>">
						<?php endif; ?>
							<img src="<?php echo Router::esc_url( $gallery->previewurl ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by NextGen's Router::esc_url() ?>" alt="<?php echo esc_attr( $gallery->title ?? '' ); ?>" loading="lazy">
						</a>
					<?php else : ?>
						<div style="aspect-ratio:<?php echo esc_attr( (string) hyperpress_nextgen_ratio( $image_gen_params['width'] ?? null, $image_gen_params['height'] ?? null ) ); ?>"></div>
					<?php endif; ?>
					<div class="card-section flex-child-auto">
						<?php if ( ! empty( $gallery->title ) ) : ?>
							<h4><a href="<?php echo Router::esc_url( $gallery->pagelink ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by NextGen's Router::esc_url() ?>"><?php echo esc_html( $gallery->title ); ?></a></h4>
						<?php endif; ?>
						<p>
							<?php if ( ! empty( $gallery->counter ) ) : ?>
								<span class="label primary"><strong><?php echo (int) $gallery->counter; ?></strong>&nbsp;<?php esc_html_e( 'Photos', 'nggallery' ); // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch -- 'nggallery' is NextGen's own text domain, reused on purpose ?></span>
							<?php endif; ?>
							<?php if ( '' !== $author_name ) : ?>
								<span class="label warning"><?php echo esc_html( $author_name ); ?></span>
							<?php endif; ?>
						</p>
						<?php if ( ! empty( $gallery->galdesc ) ) : ?>
							<p><?php echo wp_kses_post( $gallery->galdesc ); ?></p>
						<?php endif; ?>
					</div>
				</div>
			</div>
		<?php endforeach; ?>
	</div>

	<?php echo wp_kses_post( hyperpress_nextgen_pagination( (string) $pagination ) ); ?>
<?php elseif ( ! empty( $album ) ) : ?>
	<div class="callout secondary"><?php esc_html_e( 'There are no galleries in this album.', 'hyperpress' ); ?></div>
<?php endif; ?>
```

- [ ] **Step 2: Run the tests**

Run: `docker compose run --rm php composer test:integration -- --filter NggalleryTemplatesTest`
Expected: all tests PASS.

- [ ] **Step 3: Lint**

Run: `docker compose run --rm php composer lint`
Expected: 0 violations. If PHPCS flags the multi-line `<a ...>` echo or the `phpcs:ignore` placement (it fires on the line that holds the echo), move the comment to that line; do not add file-wide excludes.

- [ ] **Step 4: Tick the Task 3 and Task 4 boxes in this plan and commit**

```sh
git add themes/hyperpress-foundation/nggallery/album-compact.php themes/hyperpress-foundation/tests/integration/NggalleryTemplatesTest.php docs/superpowers/plans/2026-11-05-theme-nggallery-foundation-templates.md
git commit -m "refactor(theme): nextgen compact album with nested foundation cards" -- themes/hyperpress-foundation/nggallery/album-compact.php themes/hyperpress-foundation/tests/integration/NggalleryTemplatesTest.php docs/superpowers/plans/2026-11-05-theme-nggallery-foundation-templates.md
```

### Task 5: Full verification

**Files:** none

- [ ] **Step 1: Run the whole theme checks**

```sh
docker compose run --rm php composer lint
docker compose run --rm php composer test:unit
docker compose run --rm php composer test:integration
```

Expected: lint 0 violations; unit and integration suites pass (the pre-existing skipped tests stay skipped).

- [ ] **Step 2: Format check on the new Markdown**

From the repo root, after the Node setup line: `pnpm exec oxfmt --check docs/superpowers/specs/2026-11-05-theme-nggallery-foundation-templates-design.md docs/superpowers/plans/2026-11-05-theme-nggallery-foundation-templates.md`
Expected: no changes needed; if it reports differences run `pnpm exec oxfmt <files>` and re-check.

- [ ] **Step 3: Tick the boxes** (no separate commit; included in Task 6).

### Task 6: Correct the notes that call `gallery.php` empty

**Files:**

- Modify: `docs/superpowers/audit/2026-10-02-theme-audit.md` (lines 85, 348, 720-732)
- Modify: this plan (outcome notes, status banner)

- [ ] **Step 1: Re-grep before editing**

Run: `grep -rn -i "gallery.php\|T42" docs/superpowers`
Expected: the audit lines above, plus any new mentions; update every plan or audit line that still says `gallery.php` is empty.

- [ ] **Step 2: Update the audit notes**

Append to the T42 table row (line 85) and to the T42 section (line 720 onward) a short "Resolved" note: `gallery.php` is no longer empty; it renders the gallery as a Foundation XY grid (see `docs/superpowers/plans/2026-11-05-theme-nggallery-foundation-templates.md`). Do the same for the sentence on line 348. Keep the original finding text; add, do not rewrite history.

- [ ] **Step 3: Fill in this plan's "Outcome / execution notes" and add the COMPLETED banner at the top**

- [ ] **Step 4: Commit**

```sh
git add docs/superpowers/audit/2026-10-02-theme-audit.md docs/superpowers/plans/2026-11-05-theme-nggallery-foundation-templates.md
git commit -m "docs(theme): note that the nextgen gallery template is no longer empty" -- docs/superpowers/audit/2026-10-02-theme-audit.md docs/superpowers/plans/2026-11-05-theme-nggallery-foundation-templates.md
```

### Task 7: Owner visual check (manual)

**Files:** none

- [ ] **Step 1:** In the NextGEN display settings for the gallery and album displays you use, set Template to the parent theme's `gallery.php` / `album-compact.php`.
- [ ] **Step 2:** On a local stack (`docker/hyperalumni` or `docker/hyperonline`), load a gallery page and an album page at 400px, 800px and 1200px. Check: the column counts follow the gallery's "columns" setting; thumbnails show the Foundation border and hover state; the lightbox opens and steps through hidden images; pagination shows Foundation styling and, with NextGEN's AJAX navigation setting on, changes page in place; album cards in a row are equal height; no NextGEN border, float or background shows.
- [ ] **Step 3:** If album cards are not equal height, try the Equalizer plugin (`data-equalizer` on the grid, `data-equalizer-watch` on each card) and record the result in the outcome notes.

## Known follow-ups

- [ ] Responsive image files: NextGEN legacy templates expose only `thumbnailURL` and `imageURL`; a real `srcset` needs dynamic thumbnail sizes (`LegacyImage.php:525` builds them via `$storage->get_image_url()` with size parameters). Decide whether to call that from a theme helper or the `hyperpress-media` plugin.
- [ ] `album-extend`, `gallery-caption`, `imagebrowser`, `imagebrowser-caption`, `gallery-carousel` and the random-image widget: see the follow-up plan `2026-11-05-theme-nggallery-remaining-templates.md`. `singlepic` and `imagebrowser-exif` stay un-overridden unless the sites use them.
- [ ] Modern (non-legacy) NextGEN templates: no documented override path was found in NextGEN 4.5.5; revisit if the "Template" setting stops being practical.
- [ ] The theme does not enqueue or dequeue NextGEN's stylesheet; if any `ngg-*` styling is wanted later, it belongs in the `hyperpress-media` plugin, not the theme.

## Outcome / execution notes

(Filled in on completion.)
