<?php
namespace HyperPress\ThemeTests\Integration;

use WP_UnitTestCase;

final class ContentTemplateTest extends WP_UnitTestCase {

	public static function post_types(): array {
		return array(
			'newsletter' => array( 'hyper_newsletter' ),
			'sponsor'    => array( 'hyper_sponsor' ),
		);
	}

	/** @dataProvider post_types */
	public function test_plugin_supplies_a_readable_template_inside_its_plugin_directory( string $post_type ): void {
		$id = self::factory()->post->create( array( 'post_type' => $post_type ) );
		$this->go_to( get_permalink( $id ) );

		$path = apply_filters( 'content_template', '' );

		$this->assertNotSame( '', $path, "$post_type must supply a content template" );
		$this->assertFileIsReadable( $path );
		$this->assertStringStartsWith( '/plugins/', wp_normalize_path( realpath( $path ) ), 'template must live inside a plugin' );
	}

	public function test_other_types_get_no_override(): void {
		$id = self::factory()->post->create();
		$this->go_to( get_permalink( $id ) );
		$this->assertSame( '', apply_filters( 'content_template', '' ) );
	}
}
