<?php
/**
 * Tests for the label Resolver, which reads the Labels settings page option.
 *
 * @package HyperPress\Tests
 */

namespace HyperPress\Tests\Integration;

use HyperPress\Label\Registry;
use HyperPress\Label\Resolver;
use WP_UnitTestCase;

/**
 * @covers \HyperPress\Label\Resolver
 */
final class LabelResolverTest extends WP_UnitTestCase {

	/**
	 * A callable that returns nothing useful.
	 *
	 * @return array<int,array<string,string>>
	 */
	public static function noop(): array {
		return array();
	}

	/**
	 * Merge values into the saved label settings option.
	 *
	 * @param string $key   Option array key.
	 * @param mixed  $value Value.
	 */
	private function save( string $key, $value ): void {
		update_option( 'hyperpress_label', array_merge( (array) get_option( 'hyperpress_label', array() ), array( $key => $value ) ) );
	}

	public function set_up(): void {
		parent::set_up();
		Registry::reset( true );
		$registry = Registry::instance();
		$registry->add(
			'a',
			array(
				'callback'           => array( self::class, 'noop' ),
				'default_post_types' => array( 'post' ),
			)
		);
		$registry->add(
			'b',
			array(
				'callback'           => array( self::class, 'noop' ),
				'default_post_types' => array( 'page' ),
			)
		);
	}

	public function tear_down(): void {
		delete_option( 'hyperpress_label' );
		Registry::reset( true );
		parent::tear_down();
	}

	public function test_keys_follow_convention(): void {
		$this->assertSame( 'hyperpress_label_post_enabled', Resolver::enabled_key( 'post' ) );
		$this->assertSame( 'hyperpress_label_post_labels', Resolver::labels_key( 'post' ) );
	}

	public function test_defaults_apply_with_nothing_saved(): void {
		$this->assertTrue( Resolver::is_enabled( 'post', 'a' ) );
		$this->assertFalse( Resolver::is_enabled( 'post', 'b' ) );
		$this->assertTrue( Resolver::default_enabled( 'post' ) );
		$this->assertFalse( Resolver::default_enabled( 'attachment' ) );
		$this->assertSame( array( 'a' ), Resolver::default_labels( 'post' ) );
	}

	public function test_saved_list_overrides_defaults(): void {
		$this->save( Resolver::labels_key( 'post' ), array( 'b' ) );

		$this->assertTrue( Resolver::is_enabled( 'post', 'b' ) );
		$this->assertFalse( Resolver::is_enabled( 'post', 'a' ) );
	}

	public function test_disabled_post_type_shows_nothing(): void {
		$this->save( Resolver::labels_key( 'post' ), array( 'a', 'b' ) );
		$this->save( Resolver::enabled_key( 'post' ), false );

		$this->assertFalse( Resolver::is_enabled( 'post', 'a' ) );
		$this->assertFalse( Resolver::is_enabled( 'post', 'b' ) );

		$this->save( Resolver::enabled_key( 'post' ), true );

		$this->assertTrue( Resolver::is_enabled( 'post', 'a' ) );
		$this->assertTrue( Resolver::is_enabled( 'post', 'b' ) );
	}

	public function test_stale_ids_are_ignored(): void {
		$this->save( Resolver::labels_key( 'post' ), array( 'gone', 'a' ) );

		$this->assertTrue( Resolver::is_enabled( 'post', 'a' ) );
		$this->assertFalse( Resolver::is_enabled( 'post', 'gone' ) );
	}

	public function test_disabled_without_saved_labels_shows_nothing(): void {
		$this->save( Resolver::enabled_key( 'post' ), false );

		$this->assertFalse( Resolver::is_enabled( 'post', 'a' ) );
	}

	public function test_empty_saved_list_shows_nothing(): void {
		$this->save( Resolver::labels_key( 'post' ), array() );

		$this->assertFalse( Resolver::is_enabled( 'post', 'a' ) );
		$this->assertFalse( Resolver::is_enabled( 'post', 'b' ) );
	}

	public function test_a_false_option_value_gives_the_defaults(): void {
		$this->assertFalse( get_option( 'hyperpress_label' ) );
		$this->assertTrue( Resolver::is_enabled( 'post', 'a' ) );
		$this->assertFalse( Resolver::is_enabled( 'post', 'b' ) );
		$this->assertTrue( Resolver::is_enabled( 'page', 'b' ) );
		$this->assertFalse( Resolver::is_enabled( 'attachment', 'a' ) );
	}

	public function test_a_missing_enabled_key_with_the_row_present_gives_the_default(): void {
		$this->save( Resolver::labels_key( 'post' ), array( 'a' ) );
		$this->save( Resolver::labels_key( 'attachment' ), array( 'a' ) );

		$this->assertTrue( Resolver::is_enabled( 'post', 'a' ), 'post defaults to on' );
		$this->assertFalse( Resolver::is_enabled( 'attachment', 'a' ), 'attachment defaults to off' );
	}

	public function test_enabled_true_turns_on_a_type_that_defaults_off(): void {
		$this->save( Resolver::labels_key( 'attachment' ), array( 'a' ) );
		$this->save( Resolver::enabled_key( 'attachment' ), true );

		$this->assertTrue( Resolver::is_enabled( 'attachment', 'a' ) );
	}

	public function test_a_missing_labels_key_with_the_row_present_gives_the_defaults(): void {
		$this->save( Resolver::enabled_key( 'post' ), true );

		$this->assertTrue( Resolver::is_enabled( 'post', 'a' ) );
		$this->assertFalse( Resolver::is_enabled( 'post', 'b' ) );
	}

	public function test_a_non_array_option_gives_the_defaults(): void {
		foreach ( array( 'x', 1, '' ) as $value ) {
			update_option( 'hyperpress_label', $value );

			$this->assertTrue( Resolver::is_enabled( 'post', 'a' ), var_export( $value, true ) );
			$this->assertFalse( Resolver::is_enabled( 'post', 'b' ), var_export( $value, true ) );
		}
	}

	public function test_a_non_array_labels_value_gives_the_defaults(): void {
		$this->save( Resolver::labels_key( 'post' ), 'b' );

		$this->assertTrue( Resolver::is_enabled( 'post', 'a' ) );
		$this->assertFalse( Resolver::is_enabled( 'post', 'b' ) );
	}

	public function test_a_saved_option_for_one_type_leaves_the_other_types_on_defaults(): void {
		$this->save( Resolver::labels_key( 'post' ), array() );

		$this->assertFalse( Resolver::is_enabled( 'post', 'a' ) );
		$this->assertTrue( Resolver::is_enabled( 'page', 'b' ) );
	}

	public function test_default_types_filter_is_honoured(): void {
		$add_page = static function ( array $types, string $id ): array {
			if ( 'a' === $id ) {
				$types[] = 'page';
			}
			return $types;
		};
		add_filter( 'hyperpress_label_default_post_types', $add_page, 10, 2 );

		$this->assertTrue( Resolver::is_enabled( 'page', 'a' ) );
		$this->assertTrue( Resolver::default_enabled( 'page' ) );
		$this->assertSame( array( 'a', 'b' ), Resolver::default_labels( 'page' ) );

		remove_filter( 'hyperpress_label_default_post_types', $add_page, 10 );
	}
}
