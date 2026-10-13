<?php
namespace HyperPress\Tests\Integration\Season;

use HyperPress\Season\Sanitizers;
use WP_UnitTestCase;

final class SeasonSanitizersTest extends WP_UnitTestCase {

	public function test_unknown_and_markup_post_types_are_dropped(): void {
		$this->assertSame( array( 'post' ), Sanitizers::post_types( array( 'post', 'nope', '<script>' ) ) );
	}

	public function test_post_types_without_a_ui_are_dropped(): void {
		$this->assertSame( array( 'page' ), Sanitizers::post_types( array( 'nav_menu_item', 'page', 'attachment_x' ) ) );
	}

	public function test_non_array_input_becomes_an_array(): void {
		$this->assertSame( array(), Sanitizers::post_types( 'nope' ) );
		$this->assertSame( array(), Sanitizers::post_types( null ) );
		$this->assertSame( array( 'post' ), Sanitizers::post_types( array( 'post', array( 'x' ), 5 ) ) );
	}

	public function test_post_types_are_unique(): void {
		$this->assertSame( array( 'post', 'page' ), Sanitizers::post_types( array( 'post', 'page', 'post' ) ) );
	}

	public function test_a_non_public_post_type_with_a_ui_is_accepted(): void {
		$this->assertSame(
			array( 'post', 'hyper_countdown' ),
			Sanitizers::post_types( array( 'post', 'hyper_countdown', 'nav_menu_item' ) )
		);
	}

	public function test_the_banner_subtitle_sanitizes_scripts_away(): void {
		$sanitized = Sanitizers::banner_subtitle( '<script>x</script><b>ok</b>' );

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

		$this->assertSame( $term->slug, Sanitizers::current( $term->slug ) );
		$this->assertSame( '', Sanitizers::current( 'no-such-season' ) );
		$this->assertSame( '', Sanitizers::current( array( $term->slug ) ) );
		$this->assertSame( '', Sanitizers::current( '' ) );
	}
}
