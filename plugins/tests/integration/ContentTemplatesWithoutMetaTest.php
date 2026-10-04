<?php
namespace HyperPress\Tests\Integration;

use WP_UnitTestCase;

final class ContentTemplatesWithoutMetaTest extends WP_UnitTestCase {

	public static function post_types(): array {
		return array(
			'newsletter' => array( 'hyper_newsletter', '/hyperpress-newsletter/templates/content.php' ),
			'sponsor'    => array( 'hyper_sponsor', '/hyperpress-sponsor/templates/content.php' ),
		);
	}

	/**
	 * @dataProvider post_types
	 */
	public function test_template_renders_a_post_without_meta_without_warnings( string $post_type, string $template ): void {
		$post_id = self::factory()->post->create( array( 'post_type' => $post_type ) );
		$this->go_to( get_permalink( $post_id ) );
		the_post();

		ob_start();
		include dirname( __DIR__, 2 ) . $template;
		ob_end_clean();

		$this->assertTrue( true );
	}
}
