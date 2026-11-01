<?php
namespace HyperPress\ThemeTests\Integration;

use WP_UnitTestCase;

/**
 * The banner template renders a hero under the banner when a `hyperpress_banner_content` filter sets the optional
 * `image` key to an attachment ID. The theme's own fallbacks never set or clear it.
 */
final class ArchiveHeroTest extends WP_UnitTestCase {

	private const POST_TYPE = 'hp_test_hero';

	/** @var callable|null */
	private $filter = null;

	public function set_up(): void {
		parent::set_up();
		register_post_type(
			self::POST_TYPE,
			array(
				'public'      => true,
				'has_archive' => true,
			)
		);
		remove_action( 'wp_body_open', 'the_block_template_skip_link' );
	}

	public function tear_down(): void {
		if ( null !== $this->filter ) {
			remove_filter( 'hyperpress_banner_content', $this->filter, 10 );
			$this->filter = null;
		}
		unregister_post_type( self::POST_TYPE );
		parent::tear_down();
	}

	/** @param mixed $image Value for the banner's `image` key. */
	private function set_image( $image ): void {
		$this->filter = static function ( $banner ) use ( $image ) {
			$banner['image'] = $image;
			return $banner;
		};
		add_filter( 'hyperpress_banner_content', $this->filter, 10 );
	}

	private function make_attachment(): int {
		$file = DIR_TESTDATA . '/images/canola.jpg';
		return self::factory()->attachment->create_upload_object( $file );
	}

	private function render_banner(): string {
		ob_start();
		include get_template_directory() . '/template-parts/banner.php';
		return (string) ob_get_clean();
	}

	public function test_an_image_renders_one_hero_with_four_sizes_after_the_banner(): void {
		$id = $this->make_attachment();
		$this->assertGreaterThan( 0, $id );
		$this->set_image( $id );

		$html = $this->render_banner();

		$this->assertSame( 1, substr_count( $html, 'class="featured-hero"' ) );
		$this->assertSame( 1, preg_match( '#data-interchange="([^"]*)"#', $html, $m ) );
		foreach ( array( 'small', 'medium', 'large', 'xlarge' ) as $name ) {
			$url = wp_get_attachment_image_url( $id, 'featured-' . $name );
			$this->assertNotEmpty( $url );
			$this->assertStringContainsString( '[' . $url . ', ' . $name . ']', $m[1] );
		}
		$this->assertSame( 4, substr_count( $m[1], '[' ) );
		$this->assertStringContainsString( 'class="banner', $html );
	}

	public function test_the_hero_directly_follows_the_banner_and_precedes_the_after_banner_hook(): void {
		$id = $this->make_attachment();
		$this->set_image( $id );
		add_action(
			'hyperpress_after_banner',
			static function () {
				echo '<!--after-banner-probe-->';
			}
		);

		$html = $this->render_banner();

		$this->assertSame( 1, preg_match( '#<header role="banner" class="banner[^"]*"[^>]*>.*?</header>\s*<header class="featured-hero"#s', $html ), 'The hero must directly follow the banner\'s closing </header>.' );
		$this->assertSame( 1, preg_match( '#<header class="featured-hero".*?</header>\s*<!--after-banner-probe-->#s', $html ), 'hyperpress_after_banner must run after the hero.' );
	}

	public function test_a_numeric_string_image_is_accepted(): void {
		$id = $this->make_attachment();
		$this->set_image( (string) $id );

		$this->assertSame( 1, substr_count( $this->render_banner(), 'class="featured-hero"' ) );
	}

	public function test_no_image_renders_no_hero(): void {
		$this->assertStringNotContainsString( 'featured-hero', $this->render_banner() );
	}

	public function test_an_empty_image_renders_no_hero(): void {
		$this->set_image( 0 );
		$this->assertStringNotContainsString( 'featured-hero', $this->render_banner() );
	}

	public function test_a_deleted_attachment_renders_no_hero_and_no_notice(): void {
		$id = $this->make_attachment();
		wp_delete_attachment( $id, true );
		$this->set_image( $id );

		$errors = array();
		set_error_handler(
			static function ( $no, $str ) use ( &$errors ) {
				$errors[] = $str;
				return true;
			}
		);
		try {
			$html = $this->render_banner();
		} finally {
			restore_error_handler();
		}

		$this->assertStringNotContainsString( 'featured-hero', $html );
		$this->assertSame( array(), $errors );
	}

	public function test_a_non_image_attachment_renders_no_hero(): void {
		$id = self::factory()->attachment->create_object(
			'doc.pdf',
			0,
			array(
				'post_mime_type' => 'application/pdf',
			)
		);
		$this->set_image( $id );

		$this->assertStringNotContainsString( 'featured-hero', $this->render_banner() );
	}

	public function test_a_non_numeric_string_image_renders_no_hero(): void {
		$this->set_image( 'abc' );
		$this->assertStringNotContainsString( 'featured-hero', $this->render_banner() );
	}

	public function test_a_negative_image_renders_no_hero(): void {
		$this->set_image( -5 );
		$this->assertStringNotContainsString( 'featured-hero', $this->render_banner() );
	}

	public function test_an_array_or_object_image_renders_no_hero_and_no_warning(): void {
		$id = $this->make_attachment();

		foreach ( array( array( $id ), array( 1 ), new \stdClass() ) as $junk ) {
			$this->set_image( $junk );

			$errors = array();
			set_error_handler(
				static function ( $no, $str ) use ( &$errors ) {
					$errors[] = $str;
					return true;
				}
			);
			try {
				$html = $this->render_banner();
			} finally {
				restore_error_handler();
			}

			$this->assertStringNotContainsString( 'featured-hero', $html );
			$this->assertSame( array(), $errors );

			remove_filter( 'hyperpress_banner_content', $this->filter, 10 );
			$this->filter = null;
		}
	}

	public function test_the_image_survives_the_theme_fallback_filters_on_an_archive(): void {
		$id = $this->make_attachment();
		$this->set_image( $id );
		$this->go_to( home_url( '/?post_type=' . self::POST_TYPE ) );
		$this->assertTrue( is_archive() );

		$banner = apply_filters( 'hyperpress_banner_content', array() );
		$this->assertSame( $id, $banner['image'] );
		$this->assertSame( 'archive', $banner['type'] );

		$this->assertSame( 1, substr_count( $this->render_banner(), 'class="featured-hero"' ) );
	}

	public function test_the_image_survives_the_theme_fallback_filters_on_a_category_archive(): void {
		$id = $this->make_attachment();
		$this->set_image( $id );
		$this->go_to( get_term_link( self::factory()->category->create(), 'category' ) );
		$this->assertTrue( is_category() );

		$banner = apply_filters( 'hyperpress_banner_content', array() );

		$this->assertSame( $id, $banner['image'] );
		$this->assertSame( 'category', $banner['type'] );
	}

	public function test_the_hero_part_prints_nothing_for_an_invalid_id(): void {
		foreach ( array( 0, -5, 999999 ) as $bad ) {
			ob_start();
			get_template_part( 'template-parts/hero', null, array( 'attachment_id' => $bad ) );
			$this->assertSame( '', trim( (string) ob_get_clean() ) );
		}
		ob_start();
		get_template_part( 'template-parts/hero' );
		$this->assertSame( '', trim( (string) ob_get_clean() ) );
	}
}
