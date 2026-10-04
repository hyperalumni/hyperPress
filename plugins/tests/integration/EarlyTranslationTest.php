<?php
namespace HyperPress\Tests\Integration;

use WP_UnitTestCase;

final class EarlyTranslationTest extends WP_UnitTestCase {

	/**
	 * @return array<string, array{0: class-string}>
	 */
	public static function plugin_classes(): array {
		return array(
			'competition' => array( \HyperPress\Competition\Plugin::class ),
			'newsletter'  => array( \HyperPress\Newsletter\Plugin::class ),
			'sponsor'     => array( \HyperPress\Sponsor\Plugin::class ),
		);
	}

	/**
	 * Banner registration calls __(), which must not run before init.
	 *
	 * @dataProvider plugin_classes
	 */
	public function test_banner_registration_is_deferred_to_init( string $class ): void {
		$this->assertNotFalse( has_action( 'init', array( $class, 'register_banner' ) ) );
	}
}
