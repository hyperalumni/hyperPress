<?php
/**
 * Tests for the label Registry.
 *
 * @package HyperPress\Tests
 */

namespace HyperPress\Tests\Integration;

use HyperPress\Label\Registry;
use WP_UnitTestCase;

/**
 * @covers \HyperPress\Label\Registry
 */
final class LabelRegistryTest extends WP_UnitTestCase {

	/**
	 * A callable that returns nothing useful.
	 *
	 * @return array<int,array<string,string>>
	 */
	public static function noop(): array {
		return array();
	}

	public function test_add_fills_defaults(): void {
		Registry::reset( true );
		Registry::instance()->add( 'x.one', array( 'callback' => array( self::class, 'noop' ) ) );

		$all = Registry::instance()->all();

		$this->assertSame( 10, $all['x.one']['priority'] );
		$this->assertSame( '', $all['x.one']['group'] );
		$this->assertSame( array(), $all['x.one']['default_post_types'] );
	}

	public function test_all_sorts_by_priority_then_insertion(): void {
		Registry::reset( true );
		$registry = Registry::instance();
		$cb       = array( self::class, 'noop' );
		$registry->add( 'x.b', array( 'callback' => $cb, 'priority' => 20 ) );
		$registry->add( 'x.a', array( 'callback' => $cb, 'priority' => 10 ) );
		$registry->add( 'x.c', array( 'callback' => $cb, 'priority' => 10 ) );

		$this->assertSame( array( 'x.a', 'x.c', 'x.b' ), $registry->ids() );
	}

	public function test_action_fires_once_and_passes_the_registry(): void {
		Registry::reset();
		$calls = array();
		add_action(
			'hyperpress_label_register',
			static function ( $arg ) use ( &$calls ): void {
				$calls[] = $arg;
			}
		);

		Registry::instance()->all();
		Registry::instance()->all();

		$this->assertCount( 1, $calls );
		$this->assertInstanceOf( Registry::class, $calls[0] );
		$this->assertSame( Registry::instance(), $calls[0] );
	}

	public function test_default_post_types_applies_the_filter(): void {
		Registry::reset( true );
		Registry::instance()->add(
			'x.one',
			array(
				'callback'           => array( self::class, 'noop' ),
				'default_post_types' => array( 'post' ),
			)
		);
		$seen = array();
		add_filter(
			'hyperpress_label_default_post_types',
			static function ( array $types, string $label_id ) use ( &$seen ): array {
				$seen[] = $label_id;
				$types[] = 'page';
				return $types;
			},
			10,
			2
		);

		$this->assertSame( array( 'post', 'page' ), Registry::instance()->default_post_types( 'x.one' ) );
		$this->assertSame( array( 'x.one' ), $seen );
	}

	public function test_a_non_callable_callback_is_ignored(): void {
		Registry::reset( true );
		$registry = Registry::instance();
		$registry->add( 'x.bad', array( 'callback' => 'no_such_function_hyperpress' ) );
		$registry->add( 'x.none', array() );

		$this->assertSame( array(), $registry->ids() );
	}

	public function test_reset_recollects(): void {
		Registry::reset();
		$count = 0;
		add_action(
			'hyperpress_label_register',
			static function () use ( &$count ): void {
				++$count;
			}
		);

		Registry::instance()->all();
		Registry::reset();
		Registry::instance()->all();

		$this->assertSame( 2, $count );
	}
}
