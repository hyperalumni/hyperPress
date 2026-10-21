<?php
namespace HyperPress\Tests\Integration\Competition;

use HyperPress\Competition\AdminAssets;
use HyperPress\Competition\Plugin;
use WP_UnitTestCase;

final class AdminAssetsTest extends WP_UnitTestCase {

	private const RULE = '.ui-autocomplete.ui-front{z-index:999999}';

	public function set_up(): void {
		parent::set_up();
		// Meta Box is stubbed in the integration suite, so register the handle its osm field enqueues.
		wp_register_style( 'rwmb-osm', 'https://example.test/osm.css', array(), '1' );
	}

	public function tear_down(): void {
		wp_deregister_style( 'rwmb-osm' );
		unset( $GLOBALS['current_screen'] );
		parent::tear_down();
	}

	private function screen( string $base, string $post_type ): void {
		set_current_screen( $base );
		$screen            = get_current_screen();
		$screen->post_type = $post_type;
	}

	/** @return string[] */
	private function inline(): array {
		$data = wp_styles()->get_data( 'rwmb-osm', 'after' );
		return is_array( $data ) ? $data : array();
	}

	public function test_it_is_hooked_after_meta_box_enqueues_its_assets(): void {
		$priority = has_action( 'admin_enqueue_scripts', array( AdminAssets::class, 'enqueue' ) );

		$this->assertIsInt( $priority );
		// Meta Box enqueues its field assets on admin_enqueue_scripts at the default priority.
		$this->assertGreaterThan( 10, $priority );
	}

	public function test_the_competition_edit_screen_raises_the_autocomplete_menu(): void {
		$this->screen( 'post', Plugin::POST_TYPE );

		AdminAssets::enqueue( 'post.php' );

		$this->assertContains( self::RULE, $this->inline() );
	}

	public function test_the_new_competition_screen_raises_the_autocomplete_menu(): void {
		$this->screen( 'post', Plugin::POST_TYPE );

		AdminAssets::enqueue( 'post-new.php' );

		$this->assertContains( self::RULE, $this->inline() );
	}

	public function test_other_post_types_are_left_alone(): void {
		$this->screen( 'post', 'post' );

		AdminAssets::enqueue( 'post.php' );

		$this->assertSame( array(), $this->inline() );
	}

	public function test_other_competition_screens_are_left_alone(): void {
		$this->screen( 'edit', Plugin::POST_TYPE );

		AdminAssets::enqueue( 'edit.php' );

		$this->assertSame( array(), $this->inline() );
	}

	public function test_it_does_nothing_without_a_screen(): void {
		unset( $GLOBALS['current_screen'] );

		AdminAssets::enqueue( 'post.php' );

		$this->assertSame( array(), $this->inline() );
	}
}
