<?php
namespace HyperPress\Tests\Integration\Utils;

use HyperPress\Utils\Controls\CategoryDropdownControl;
use HyperPress\Utils\Controls\PostTypeDropdownControl;
use ReflectionMethod;
use WP_Customize_Control;
use WP_Customize_Manager;
use WP_UnitTestCase;

final class ControlsTest extends WP_UnitTestCase {

	private function manager(): WP_Customize_Manager {
		require_once ABSPATH . WPINC . '/class-wp-customize-manager.php';
		$manager = new WP_Customize_Manager();
		$manager->add_setting( 'hyperpress_test_setting' );
		return $manager;
	}

	public function test_post_type_control_lists_public_post_types(): void {
		$control = new PostTypeDropdownControl( $this->manager(), 'hyperpress_test_setting', array( 'settings' => 'hyperpress_test_setting', 'label' => 'Types' ) );
		$this->assertInstanceOf( WP_Customize_Control::class, $control );
		$this->assertSame( 'dropdown-post-types', $control->type );

		ob_start();
		( new ReflectionMethod( $control, 'render_content' ) )->invoke( $control );
		$html = ob_get_clean();
		$this->assertStringContainsString( '<option value="post"', $html );
	}

	public function test_category_control_type(): void {
		$control = new CategoryDropdownControl( $this->manager(), 'hyperpress_test_setting', array( 'settings' => 'hyperpress_test_setting' ) );
		$this->assertSame( 'dropdown-category', $control->type );
	}
}
