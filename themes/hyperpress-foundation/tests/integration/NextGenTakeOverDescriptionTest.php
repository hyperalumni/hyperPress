<?php
namespace HyperPress\ThemeTests\Integration;

use WP_UnitTestCase;

final class NextGenTakeOverDescriptionTest extends WP_UnitTestCase {

	private const HOOK = 'ngg_displayed_gallery_rendering';

	public function tear_down(): void {
		remove_all_filters( self::HOOK );
		remove_all_filters( 'some_other_hook' );
		parent::tear_down();
	}

	private function fake_controller(): object {
		return new class() {
			public function add_description_to_legacy_templates( $html ) {
				return $html;
			}

			public function add_breadcrumbs_to_legacy_templates( $html ) {
				return $html;
			}
		};
	}

	public function test_it_removes_nextgens_description_filter_at_priority_8(): void {
		$controller = $this->fake_controller();
		add_filter( self::HOOK, array( $controller, 'add_description_to_legacy_templates' ), 8, 2 );

		hyperpress_nextgen_take_over_description();

		$this->assertFalse( has_filter( self::HOOK, array( $controller, 'add_description_to_legacy_templates' ) ) );
	}

	public function test_it_leaves_other_callbacks_at_priority_8(): void {
		$controller = $this->fake_controller();
		$closure    = static fn( $html ) => $html;
		add_filter( self::HOOK, $closure, 8 );
		add_filter( self::HOOK, 'trim', 8 );
		add_filter( self::HOOK, array( $controller, 'add_breadcrumbs_to_legacy_templates' ), 8 );
		add_filter( self::HOOK, array( $controller, 'add_description_to_legacy_templates' ), 8 );

		hyperpress_nextgen_take_over_description();

		$this->assertSame( 8, has_filter( self::HOOK, $closure ) );
		$this->assertSame( 8, has_filter( self::HOOK, 'trim' ) );
		$this->assertSame( 8, has_filter( self::HOOK, array( $controller, 'add_breadcrumbs_to_legacy_templates' ) ) );
		$this->assertFalse( has_filter( self::HOOK, array( $controller, 'add_description_to_legacy_templates' ) ) );
	}

	public function test_it_leaves_the_same_method_at_other_priorities(): void {
		$controller = $this->fake_controller();
		add_filter( self::HOOK, array( $controller, 'add_description_to_legacy_templates' ), 9 );

		hyperpress_nextgen_take_over_description();

		$this->assertSame( 9, has_filter( self::HOOK, array( $controller, 'add_description_to_legacy_templates' ) ) );
	}

	public function test_it_leaves_the_same_method_on_other_hooks(): void {
		$controller = $this->fake_controller();
		add_filter( 'some_other_hook', array( $controller, 'add_description_to_legacy_templates' ), 8 );

		hyperpress_nextgen_take_over_description();

		$this->assertSame( 8, has_filter( 'some_other_hook', array( $controller, 'add_description_to_legacy_templates' ) ) );
	}

	public function test_it_is_safe_with_nothing_registered_and_when_called_twice(): void {
		hyperpress_nextgen_take_over_description();

		$controller = $this->fake_controller();
		add_filter( self::HOOK, array( $controller, 'add_description_to_legacy_templates' ), 8 );
		hyperpress_nextgen_take_over_description();
		hyperpress_nextgen_take_over_description();

		$this->assertFalse( has_filter( self::HOOK, array( $controller, 'add_description_to_legacy_templates' ) ) );
	}

	public function test_it_is_safe_when_only_other_priorities_have_callbacks(): void {
		add_filter( self::HOOK, 'trim', 10 );

		hyperpress_nextgen_take_over_description();

		$this->assertSame( 10, has_filter( self::HOOK, 'trim' ) );
	}
}
