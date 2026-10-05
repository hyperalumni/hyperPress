<?php
namespace HyperPress\Tests\Integration\Utils;

use HyperPress\Utils\Controls\CategoryDropdownControl;
use HyperPress\Utils\Controls\PostTypeDropdownControl;
use ReflectionMethod;
use WP_Customize_Manager;
use WP_UnitTestCase;

final class ControlDescriptionEscapingTest extends WP_UnitTestCase {

	public static function controls(): array {
		return array(
			'post types' => array( PostTypeDropdownControl::class ),
			'categories' => array( CategoryDropdownControl::class ),
		);
	}

	/** @dataProvider controls */
	public function test_description_keeps_harmless_markup_and_drops_scripts( string $class ): void {
		require_once ABSPATH . WPINC . '/class-wp-customize-manager.php';
		$manager = new WP_Customize_Manager();
		$manager->add_setting( 'hyperpress_test_setting', array( 'default' => '' ) );

		$control = new $class(
			$manager,
			'hyperpress_test_setting',
			array(
				'settings'    => 'hyperpress_test_setting',
				'label'       => 'Label',
				'description' => 'See <a href="https://example.org/docs">the docs</a> and <em>note</em><script>alert(1)</script><img src=x onerror=alert(2)>',
			)
		);

		ob_start();
		( new ReflectionMethod( $control, 'render_content' ) )->invoke( $control );
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( '<a href="https://example.org/docs">the docs</a>', $html );
		$this->assertStringContainsString( '<em>note</em>', $html );
		$this->assertStringNotContainsString( '<script', $html );
		$this->assertStringNotContainsString( 'onerror', $html );
	}
}
