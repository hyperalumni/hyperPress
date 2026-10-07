<?php
namespace HyperPress\Tests\Integration;

use WP_Error;
use WP_UnitTestCase;

final class NewsletterSeasonErrorTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		\HyperPress\Label\Registry::reset();
		\HyperPress\Label\Renderer::register();
		// Simulate the season taxonomy being unregistered (season plugin off).
		add_filter(
			'get_object_terms',
			static function ( $terms, $object_ids, $taxonomies ) {
				return in_array( 'hyper_season', (array) $taxonomies, true ) ? new WP_Error( 'invalid_taxonomy', 'Invalid taxonomy.' ) : $terms;
			},
			10,
			3
		);
	}

	public function tear_down(): void {
		remove_all_filters( 'hyperpress_labels_content' );
		\HyperPress\Label\Registry::reset( true );
		parent::tear_down();
	}

	private function go_to_newsletter(): int {
		$post_id = self::factory()->post->create( array( 'post_type' => 'hyper_newsletter' ) );
		$this->go_to( get_permalink( $post_id ) );
		the_post();
		return $post_id;
	}

	public function test_labels_omit_the_season_label_when_terms_are_a_wp_error(): void {
		$this->go_to_newsletter();
		$labels = \HyperPress\Label\Renderer::labels();
		$this->assertNotEmpty( $labels );
		$this->assertNotContains( 'Posted During', array_column( $labels, 'title' ) );
	}

	public function test_content_template_renders_when_terms_are_a_wp_error(): void {
		$this->go_to_newsletter();
		ob_start();
		include dirname( __DIR__, 2 ) . '/hyperpress-newsletter/templates/content.php';
		ob_end_clean();
		$this->assertTrue( true );
	}
}
