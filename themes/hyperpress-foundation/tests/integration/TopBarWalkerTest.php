<?php
namespace HyperPress\ThemeTests\Integration;

use HyperPress_Theme_Top_Bar_Walker;
use WP_UnitTestCase;

final class TopBarWalkerTest extends WP_UnitTestCase {

	public function test_nested_list_is_hidden_before_foundation_js_runs(): void {
		$output = '';
		( new HyperPress_Theme_Top_Bar_Walker() )->start_lvl( $output, 0, (object) array() );

		$this->assertStringContainsString( 'is-dropdown-submenu', $output );
		$this->assertStringContainsString( 'dropdown menu vertical', $output );
		$this->assertStringContainsString( 'data-toggle', $output );
	}
}
