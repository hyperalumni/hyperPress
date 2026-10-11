<?php
namespace HyperPress\ThemeTests\Integration;

use WP_UnitTestCase;

/**
 * Pins the behavior of code that used loose comparisons, so switching to strict ones changes nothing visible.
 */
final class StrictComparisonsTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		remove_action( 'wp_footer', 'the_block_template_skip_link' );
		remove_action( 'wp_body_open', 'the_block_template_skip_link' );
		$GLOBALS['wp_styles'] = null;
	}

	/* ---- Nav walker: is-active ---------------------------------------------------------------------------- */

	public static function menu_items(): array {
		return array(
			'current'           => array( true, false, true ),
			'ancestor'          => array( false, true, true ),
			'both'              => array( true, true, true ),
			'neither'           => array( false, false, false ),
			'unset properties'  => array( null, null, false ),
			'integer flags'     => array( 1, 0, true ),
		);
	}

	/** @dataProvider menu_items */
	public function test_nav_items_get_is_active( $current, $ancestor, bool $active ): void {
		$item = (object) array();
		if ( null !== $current ) {
			$item->current = $current;
		}
		if ( null !== $ancestor ) {
			$item->current_item_ancestor = $ancestor;
		}

		$classes = apply_filters( 'nav_menu_css_class', array( 'menu-item' ), $item, (object) array(), 0 );

		$this->assertSame( $active, in_array( 'is-active', $classes, true ) );
		$this->assertContains( 'menu-item', $classes );
	}

	/* ---- Comments pagination ------------------------------------------------------------------------------ */

	private function pagination( int $page, int $pages, array $args = array() ): string {
		update_option( 'page_comments', '1' );
		update_option( 'comments_per_page', '5' );
		update_option( 'thread_comments', '0' );

		$post_id = self::factory()->post->create();
		self::factory()->comment->create_many( 5 * $pages, array( 'comment_post_ID' => $post_id, 'comment_approved' => 1 ) );
		$this->go_to( get_permalink( $post_id ) );
		$GLOBALS['wp_query']->comments = get_comments( array( 'post_id' => $post_id, 'status' => 'approve' ) );
		set_query_var( 'cpage', $page );

		return hyperpress_get_the_comments_pagination( $args );
	}

	public function test_first_page_has_a_disabled_previous_item_and_a_next_link(): void {
		$html = $this->pagination( 1, 3 );

		$this->assertStringContainsString( '<li class="page-item disabled">&laquo;</li>', $html );
				$this->assertStringContainsString( 'page-item active', $html );
		$this->assertStringNotContainsString( '<li class="page-item disabled">&raquo;</li>', $html );
	}

	public function test_last_page_has_a_disabled_next_item(): void {
		$html = $this->pagination( 3, 3 );

		$this->assertStringContainsString( '<li class="page-item disabled">&raquo;</li>', $html );
		$this->assertStringNotContainsString( '<li class="page-item disabled">&laquo;</li>', $html );
	}

	public function test_middle_page_has_neither_disabled_item(): void {
		$html = $this->pagination( 2, 3 );

		$this->assertStringNotContainsString( '<li class="page-item disabled">&laquo;</li>', $html );
		$this->assertStringNotContainsString( '<li class="page-item disabled">&raquo;</li>', $html );
	}

	public static function sizes(): array {
		return array(
			'large'   => array( 'large', ' pagination-lg' ),
			'small'   => array( 'small', ' pagination-sm' ),
			'default' => array( 'default', '' ),
			'garbage' => array( 'huge', '' ),
		);
	}

	/** @dataProvider sizes */
	public function test_size_class( string $size, string $suffix ): void {
		$html = $this->pagination( 2, 3, array( 'size' => $size ) );

		$this->assertStringContainsString( '<ul class="pagination' . $suffix . '">', $html );
	}

	public static function current_pages(): array {
		return array(
			'first'  => array( 1, 3 ),
			'middle' => array( 2, 3 ),
			'last'   => array( 3, 3 ),
		);
	}

	/** @dataProvider current_pages */
	public function test_the_current_page_is_the_only_active_item_and_says_so_to_screen_readers( int $page, int $pages ): void {
		$html = $this->pagination( $page, $pages );

		$this->assertSame( 1, substr_count( $html, 'page-item active' ), 'exactly one active item' );
		$this->assertMatchesRegularExpression(
			'~<li class="page-item active"><a class="page-link" href="\#" aria-current="page">' . $page . '</a></li>~',
			$html,
			'the active item is a page-link holding the current page number'
		);
		$this->assertStringNotContainsString( '<span', $html, 'no span left over from core markup inside the pagination list' );
	}

	public function test_dots_between_page_ranges_are_disabled_items(): void {
		$html = $this->pagination( 5, 10 );

		$this->assertMatchesRegularExpression( '~<li class="page-item disabled"><a class="page-link" href="\#">&hellip;</a></li>~', $html );
		$this->assertSame( 1, substr_count( $html, 'page-item active' ) );
	}

	/* ---- post_gallery filter ------------------------------------------------------------------------------ */

	public static function gallery_overrides(): array {
		return array(
			'a string handles the gallery'     => array( '<p>custom</p>', '<p>custom</p>' ),
			'an empty string falls through'    => array( '', null ),
			'null falls through, like core'    => array( null, null ),
			'false falls through, like core'   => array( false, null ),
		);
	}

	/** @dataProvider gallery_overrides */
	public function test_post_gallery_override( $returned, ?string $expected ): void {
		$id = self::factory()->attachment->create_object(
			array(
				'post_mime_type' => 'image/jpeg',
				'post_title'     => 'pic',
				'post_status'    => 'inherit',
			)
		);
		update_post_meta( $id, '_wp_attached_file', 'pic.jpg' );
		add_filter( 'post_gallery', static fn() => $returned, 10 );

		$html = (string) do_shortcode( '[gallery ids="' . $id . '"]' );

		if ( null !== $expected ) {
			$this->assertSame( $expected, $html );
		} else {
			$this->assertStringContainsString( 'fp-gallery', $html, 'the theme gallery is rendered when the filter does not handle it' );
		}
	}

	/* ---- Gallery link types ------------------------------------------------------------------------------- */

	public function test_gallery_link_types_still_pick_the_right_markup(): void {
		$id = self::factory()->attachment->create_object(
			array(
				'post_mime_type' => 'image/jpeg',
				'post_title'     => 'pic',
				'post_excerpt'   => 'Cap',
				'post_status'    => 'inherit',
			)
		);
		update_post_meta( $id, '_wp_attached_file', 'pic.jpg' );

		$file = do_shortcode( '[gallery ids="' . $id . '" link="file"]' );
		$none = do_shortcode( '[gallery ids="' . $id . '" link="none"]' );
		$page = do_shortcode( '[gallery ids="' . $id . '"]' );

		$this->assertStringContainsString( 'fp-gallery-lightbox', $file );
		$this->assertStringNotContainsString( '<a ', $none );
		$this->assertStringContainsString( 'class="thumbnail"', $page );
		$this->assertStringNotContainsString( 'fp-gallery-lightbox', $page );
	}

	/* ---- Admin screen ------------------------------------------------------------------------------------- */

	public function test_admin_scripts_tolerate_a_missing_screen(): void {
		$GLOBALS['current_screen'] = null;

		hyperpress_admin_scripts();

		$this->assertFalse( wp_style_is( 'wp-admin-svg-support', 'enqueued' ) );
	}

	public function test_the_svg_admin_style_loads_on_the_post_screen(): void {
		set_current_screen( 'post' );

		hyperpress_admin_scripts();

		$this->assertTrue( wp_style_is( 'wp-admin-svg-support', 'enqueued' ) );
	}

	public function test_the_svg_admin_style_does_not_load_on_other_screens(): void {
		set_current_screen( 'dashboard' );

		hyperpress_admin_scripts();

		$this->assertFalse( wp_style_is( 'wp-admin-svg-support', 'enqueued' ) );
	}
}
