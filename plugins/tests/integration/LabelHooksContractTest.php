<?php
/**
 * Pins the hooks that hyperpress-label owns.
 *
 * @package HyperPress\Tests
 */

namespace HyperPress\Tests\Integration;

use HyperPress\Label\Registry;
use HyperPress\Label\Renderer;
use WP_UnitTestCase;

/**
 * @covers \HyperPress\Label\Renderer
 */
final class LabelHooksContractTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		Registry::reset( true );
		Renderer::register();
	}

	public function tear_down(): void {
		remove_all_filters( 'hyperpress_labels_content' );
		Registry::reset( true );
		parent::tear_down();
	}

	public function test_renderer_owns_the_hyperpress_labels_action(): void {
		$this->assertNotFalse( has_action( 'hyperpress_labels', array( Renderer::class, 'render' ) ) );
	}

	public function test_renderer_owns_the_hyperpress_labels_content_filter(): void {
		Registry::instance()->add(
			'a',
			array(
				'callback'           => static fn (): array => array( array( 'label' => 'A' ) ),
				'default_post_types' => array( 'post' ),
			)
		);
		$post_id = self::factory()->post->create();
		$this->go_to( get_permalink( $post_id ) );
		$GLOBALS['post'] = get_post( $post_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- test setup.
		setup_postdata( $GLOBALS['post'] );

		$received = null;
		add_filter(
			'hyperpress_labels_content',
			static function ( $labels ) use ( &$received ) {
				$received = $labels;
				return $labels;
			}
		);

		Renderer::labels();

		$this->assertIsArray( $received );
		$this->assertSame( 'A', $received[0]['label'] );
	}
}
