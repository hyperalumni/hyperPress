<?php
namespace HyperPress\Tests\Integration\Event;

use HyperPress\Event\Plugin;
use WP_UnitTestCase;

final class DeactivationTest extends WP_UnitTestCase {

	public function tear_down(): void {
		// Put the registrations and the rules back for the tests that follow.
		if ( ! post_type_exists( Plugin::POST_TYPE ) ) {
			Plugin::register();
		}
		flush_rewrite_rules();
		parent::tear_down();
	}

	/**
	 * @return list<string> The stored rewrite rule patterns that start with `event/`.
	 */
	private function event_rules(): array {
		$rules = get_option( 'rewrite_rules' );

		return is_array( $rules ) ? array_values( array_filter( array_keys( $rules ), static fn( $rule ): bool => 0 === strpos( (string) $rule, 'event/' ) ) ) : array();
	}

	public function test_deactivation_hook_is_registered_by_the_main_file(): void {
		$main = (string) file_get_contents( dirname( __DIR__, 3 ) . '/hyperpress-event/hyperpress-event.php' );

		$this->assertStringContainsString( "register_deactivation_hook( __FILE__, array( \\HyperPress\\Event\\Plugin::class, 'deactivate' ) )", $main );
	}

	public function test_deactivate_removes_the_event_rules_and_the_version_option(): void {
		$this->set_permalink_structure( '/%postname%/' );
		Plugin::register();
		flush_rewrite_rules();
		update_option( 'hyperpress_event_rewrite_version', '2026.0.0+2' );
		$this->assertNotSame( array(), $this->event_rules(), 'Precondition: the event rules exist.' );

		// A real deactivation request has generated no rules yet; WP_Rewrite keeps the ones this process generated.
		global $wp_rewrite;
		foreach ( array_keys( $wp_rewrite->extra_rules_top ) as $regex ) {
			if ( 0 === strpos( (string) $regex, 'event/' ) ) {
				unset( $wp_rewrite->extra_rules_top[ $regex ] );
			}
		}

		Plugin::deactivate();

		$this->assertSame( array(), $this->event_rules() );
		$this->assertFalse( get_option( 'hyperpress_event_rewrite_version' ) );
		$this->assertFalse( post_type_exists( Plugin::POST_TYPE ) );
		$this->assertFalse( taxonomy_exists( Plugin::SERIES ) );
	}
}
