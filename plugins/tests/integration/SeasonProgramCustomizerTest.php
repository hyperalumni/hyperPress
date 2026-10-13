<?php
namespace HyperPress\Tests\Integration;

use WP_Customize_Manager;
use WP_UnitTestCase;

final class SeasonProgramCustomizerTest extends WP_UnitTestCase {

	private function customizer(): WP_Customize_Manager {
		require_once ABSPATH . WPINC . '/class-wp-customize-manager.php';
		$manager = new WP_Customize_Manager();
		do_action( 'customize_register', $manager );
		return $manager;
	}

	/** @return array<string,array{0:string}> */
	public static function post_type_settings(): array {
		return array(
			'program' => array( 'hyperpress_program_post_types' ),
		);
	}

	/** @dataProvider post_type_settings */
	public function test_unknown_and_markup_post_types_are_dropped( string $id ): void {
		$setting = $this->customizer()->get_setting( $id );

		$this->assertSame( array( 'post' ), $setting->sanitize( array( 'post', 'nope', '<script>' ) ) );
	}

	/** @dataProvider post_type_settings */
	public function test_non_public_post_types_are_dropped( string $id ): void {
		$setting = $this->customizer()->get_setting( $id );

		$this->assertSame( array( 'page' ), $setting->sanitize( array( 'nav_menu_item', 'page', 'attachment_x' ) ) );
	}

	/** @dataProvider post_type_settings */
	public function test_non_array_input_becomes_an_array( string $id ): void {
		$setting = $this->customizer()->get_setting( $id );

		$this->assertSame( array(), $setting->sanitize( 'nope' ) );
		$this->assertSame( array(), $setting->sanitize( null ) );
		$this->assertSame( array( 'post' ), $setting->sanitize( array( 'post', array( 'x' ), 5 ) ) );
	}
}
