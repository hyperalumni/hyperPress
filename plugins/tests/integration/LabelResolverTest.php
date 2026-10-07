<?php
/**
 * Tests for the label Resolver.
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
		foreach ( array( 'post', 'page', 'attachment' ) as $type ) {
			remove_theme_mod( Resolver::enabled_key( $type ) );
			remove_theme_mod( Resolver::labels_key( $type ) );
		}
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
		set_theme_mod( Resolver::labels_key( 'post' ), array( 'b' ) );

		$this->assertTrue( Resolver::is_enabled( 'post', 'b' ) );
		$this->assertFalse( Resolver::is_enabled( 'post', 'a' ) );
	}

	public function test_disabled_post_type_shows_nothing(): void {
		set_theme_mod( Resolver::labels_key( 'post' ), array( 'a', 'b' ) );
		set_theme_mod( Resolver::enabled_key( 'post' ), false );

		$this->assertFalse( Resolver::is_enabled( 'post', 'a' ) );
		$this->assertFalse( Resolver::is_enabled( 'post', 'b' ) );

		set_theme_mod( Resolver::enabled_key( 'post' ), true );

		$this->assertTrue( Resolver::is_enabled( 'post', 'a' ) );
		$this->assertTrue( Resolver::is_enabled( 'post', 'b' ) );
	}

	public function test_stale_ids_are_ignored(): void {
		set_theme_mod( Resolver::labels_key( 'post' ), array( 'gone', 'a' ) );

		$this->assertTrue( Resolver::is_enabled( 'post', 'a' ) );
		$this->assertFalse( Resolver::is_enabled( 'post', 'gone' ) );
	}

	public function test_empty_saved_list_shows_nothing(): void {
		set_theme_mod( Resolver::labels_key( 'post' ), array() );

		$this->assertFalse( Resolver::is_enabled( 'post', 'a' ) );
		$this->assertFalse( Resolver::is_enabled( 'post', 'b' ) );
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
