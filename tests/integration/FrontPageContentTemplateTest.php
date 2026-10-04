<?php
namespace HyperPress\ThemeTests\Integration;

use WP_UnitTestCase;

final class FrontPageContentTemplateTest extends WP_UnitTestCase {

	private bool $stubbed_countdown = false;

	public function set_up(): void {
		parent::set_up();
		if ( ! shortcode_exists( 'hyperpress_countdown' ) ) {
			add_shortcode( 'hyperpress_countdown', '__return_empty_string' );
			$this->stubbed_countdown = true;
		}
	}

	public function tear_down(): void {
		if ( $this->stubbed_countdown ) {
			remove_shortcode( 'hyperpress_countdown' );
			$this->stubbed_countdown = false;
		}
		remove_all_filters( 'pre_do_shortcode_tag' );
		parent::tear_down();
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

	public function test_front_page_lists_a_post_type_without_a_content_template_override(): void {
		set_theme_mod( 'hyperpress_home_blog_post_types', array( 'hyper_award' ) );
		self::factory()->post->create(
			array(
				'post_type'   => 'hyper_award',
				'post_status' => 'publish',
				'post_title'  => 'Golden Widget Award',
			)
		);

		$html = $this->render_front_page();

		$this->assertStringContainsString( 'Golden Widget Award', $html );
	}

	public function test_front_page_only_passes_integer_countdown_ids_to_the_shortcode(): void {
		self::factory()->post->create( array( 'post_status' => 'publish' ) );
		set_theme_mod( 'hyperpress_homepage_customize_countdowns', array( '12', '7" x="y', 'abc' ) );

		$seen = array();
		add_filter(
			'pre_do_shortcode_tag',
			static function ( $output, $tag, $attr ) use ( &$seen ) {
				if ( 'hyperpress_countdown' === $tag ) {
					$seen[] = $attr;
					return ''; // Short-circuit: only the attributes matter here.
				}
				return $output;
			},
			10,
			3
		);

		$this->render_front_page();

		$this->assertSame(
			array(
				array( 'id' => '12' ),
				array( 'id' => '7' ),
			),
			$seen
		);
	}
}
