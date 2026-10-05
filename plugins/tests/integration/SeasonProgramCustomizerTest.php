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
			'season'  => array( 'hyperpress_season_post_types' ),
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

	public function test_the_banner_subtitle_sanitises_scripts_away(): void {
		$setting = $this->customizer()->get_setting( 'hyperpress_season_banner_subtitle' );

		$sanitized = $setting->sanitize( '<script>x</script><b>ok</b>' );

		$this->assertStringNotContainsString( '<script', $sanitized );
		$this->assertStringContainsString( '<b>ok</b>', $sanitized );
	}

	public function test_the_current_season_accepts_only_an_existing_season(): void {
		$term = self::factory()->term->create_and_get(
			array(
				'taxonomy' => 'hyper_season',
				'name'     => 'Season 2026',
				'slug'     => 'season-2026',
			)
		);
		$setting = $this->customizer()->get_setting( 'hyperpress_season_current' );

		$this->assertSame( $term->slug, $setting->sanitize( $term->slug ) );
		$this->assertSame( '', $setting->sanitize( 'no-such-season' ) );
		$this->assertSame( '', $setting->sanitize( array( $term->slug ) ) );
	}
}
