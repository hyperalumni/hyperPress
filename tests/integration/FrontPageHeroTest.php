<?php
namespace HyperPress\ThemeTests\Integration;

use WP_UnitTestCase;

final class FrontPageHeroTest extends WP_UnitTestCase {

	private const SIZES = array( 'small', 'medium', 'large', 'xlarge' );

	public function set_up(): void {
		parent::set_up();
		// WordPress core still hooks the deprecated the_block_template_skip_link() on these actions, which trips the test case's deprecation check.
		remove_action( 'wp_footer', 'the_block_template_skip_link' );
		remove_action( 'wp_body_open', 'the_block_template_skip_link' );
	}

	/** Render the front page through front-page.php and return the HTML. */
	private function render_front_page(): string {
		global $post; // The template loader includes templates in global scope, so $post is available there.
		$this->go_to( home_url( '/' ) );
		$template = get_front_page_template();
		$this->assertSame( 'front-page.php', basename( $template ) );
		ob_start();
		include $template;
		$html = ob_get_clean();
		$this->assertStringNotContainsString( 'Fatal error', $html );
		return $html;
	}

	/** Create a static front page, optionally with a featured image that has the four hero sizes. */
	private function create_front_page( bool $with_image ): int {
		$page_id = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_title'  => 'Static Front',
			)
		);
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $page_id );

		if ( $with_image ) {
			$attachment_id = self::factory()->attachment->create_object(
				array(
					'file'           => 'hero.jpg',
					'post_parent'    => $page_id,
					'post_mime_type' => 'image/jpeg',
				)
			);
			$sizes         = array();
			foreach ( array(
				'small'  => array( 640, 300 ),
				'medium' => array( 1280, 400 ),
				'large'  => array( 1440, 600 ),
				'xlarge' => array( 1920, 600 ),
			) as $name => $dimensions ) {
				$sizes[ 'front-hero-' . $name ] = array(
					'file'      => sprintf( 'hero-%dx%d.jpg', $dimensions[0], $dimensions[1] ),
					'width'     => $dimensions[0],
					'height'    => $dimensions[1],
					'mime-type' => 'image/jpeg',
				);
			}
			wp_update_attachment_metadata(
				$attachment_id,
				array(
					'width'  => 2400,
					'height' => 1200,
					'file'   => 'hero.jpg',
					'sizes'  => $sizes,
				)
			);
			set_post_thumbnail( $page_id, $attachment_id );
		}

		return $page_id;
	}

	public function test_front_page_registers_the_hero_image_sizes_with_hard_crop(): void {
		$expected = array(
			'front-hero-small'  => array( 640, 300 ),
			'front-hero-medium' => array( 1280, 400 ),
			'front-hero-large'  => array( 1440, 600 ),
			'front-hero-xlarge' => array( 1920, 600 ),
		);
		$sizes    = wp_get_additional_image_sizes();
		foreach ( $expected as $name => $dimensions ) {
			$this->assertArrayHasKey( $name, $sizes );
			$this->assertSame( $dimensions, array( $sizes[ $name ]['width'], $sizes[ $name ]['height'] ) );
			$this->assertTrue( $sizes[ $name ]['crop'] );
		}
	}

	public function test_hero_is_rendered_with_interchange_urls_when_there_is_no_slider_and_a_featured_image(): void {
		$this->assertFalse( function_exists( 'add_revslider' ) );
		$this->create_front_page( true );

		$html = $this->render_front_page();

		$this->assertStringContainsString( 'class="hyperpress-front-hero"', $html );
		$this->assertSame( 1, preg_match( '/<div class="hyperpress-front-hero"[^>]*data-interchange="([^"]*)"/', $html, $matches ) );
		$interchange = html_entity_decode( $matches[1] );
		foreach ( array(
			'small'  => 'hero-640x300.jpg',
			'medium' => 'hero-1280x400.jpg',
			'large'  => 'hero-1440x600.jpg',
			'xlarge' => 'hero-1920x600.jpg',
		) as $breakpoint => $file ) {
			$this->assertMatchesRegularExpression( '#\[[^\],]*' . preg_quote( $file, '#' ) . ', ' . $breakpoint . '\]#', $interchange );
		}
		// No-JS fallback uses the large size.
		$this->assertMatchesRegularExpression( '#style="background-image:url\(\'[^\']*hero-1440x600\.jpg\'\)"#', $html );
	}

	public function test_hero_is_not_rendered_when_the_front_page_has_no_featured_image(): void {
		$this->assertFalse( function_exists( 'add_revslider' ) );
		$this->create_front_page( false );

		$html = $this->render_front_page();

		$this->assertStringContainsString( 'Static Front', $html ); // The page itself still rendered.
		$this->assertStringNotContainsString( 'hyperpress-front-hero', $html );
	}

	/**
	 * Runs in its own process because a function cannot be undefined again and
	 * defining add_revslider() would change the other tests in this class.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_hero_is_not_rendered_when_slider_revolution_is_available(): void {
		if ( ! function_exists( 'add_revslider' ) ) {
			// phpcs:ignore Universal.Files.SeparateFunctionsFromOO.Mixed -- stub for Slider Revolution, declared inside the isolated process only
			eval( 'function add_revslider( $alias ) { echo "<div class=\"slider-stub\">" . $alias . "</div>"; }' ); // phpcs:ignore Squiz.PHP.Eval.Discouraged -- test stub
		}
		$this->create_front_page( true );

		$html = $this->render_front_page();

		$this->assertStringContainsString( 'slider-stub', $html );
		$this->assertStringNotContainsString( 'hyperpress-front-hero', $html );
	}
}
