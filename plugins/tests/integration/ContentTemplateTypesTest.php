<?php
namespace HyperPress\Tests\Integration;

use WP_UnitTestCase;

/**
 * The theme front page passes a WP_Post as the default of `content_template`,
 * so plugin callbacks must accept any value and pass it through untouched.
 */
final class ContentTemplateTypesTest extends WP_UnitTestCase {

	/**
	 * @return array<string, array{0: string}>
	 */
	public function handled_post_types(): array {
		return array(
			'newsletter' => array( 'hyper_newsletter' ),
			'sponsor'    => array( 'hyper_sponsor' ),
		);
	}

	/**
	 * @dataProvider handled_post_types
	 */
	public function test_content_template_passes_a_non_string_default_through_for_other_post_types( string $post_type ): void {
		if ( ! post_type_exists( $post_type ) ) {
			$this->markTestSkipped( "$post_type is not registered." );
		}
		$post_id = self::factory()->post->create( array( 'post_type' => 'post' ) );
		$this->go_to( get_permalink( $post_id ) );
		$default = get_post( $post_id );

		$result = apply_filters( 'content_template', $default );

		$this->assertSame( $default, $result );
	}

	/**
	 * @dataProvider handled_post_types
	 */
	public function test_content_template_still_supplies_the_plugin_path_for_the_handled_post_type( string $post_type ): void {
		if ( ! post_type_exists( $post_type ) ) {
			$this->markTestSkipped( "$post_type is not registered." );
		}
		$post_id = self::factory()->post->create( array( 'post_type' => $post_type ) );
		$this->go_to( get_permalink( $post_id ) );

		$result = apply_filters( 'content_template', get_post( self::factory()->post->create() ) );

		$this->assertIsString( $result );
		$this->assertFileIsReadable( $result );
	}

	public function test_sponsor_archive_template_passes_a_non_string_default_through_for_other_post_types(): void {
		$post_id = self::factory()->post->create( array( 'post_type' => 'post' ) );
		$this->go_to( get_permalink( $post_id ) );
		$default = get_post( $post_id );

		$result = apply_filters( 'archive_template', $default );

		$this->assertSame( $default, $result );
	}

	public function test_sponsor_archive_template_still_supplies_the_plugin_path_for_sponsors(): void {
		self::factory()->post->create( array( 'post_type' => 'hyper_sponsor' ) );
		flush_rewrite_rules();
		$this->go_to( home_url( '/sponsors/' ) );

		$result = apply_filters( 'archive_template', '' );

		$this->assertIsString( $result );
		$this->assertFileIsReadable( $result );
	}
}
