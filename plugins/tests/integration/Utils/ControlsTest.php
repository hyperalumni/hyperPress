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

	private function rendered_options( array $options ): string {
		$control = new PostTypeDropdownControl( $this->manager(), 'hyperpress_test_setting', array( 'settings' => 'hyperpress_test_setting' ), $options );
		ob_start();
		( new ReflectionMethod( $control, 'render_content' ) )->invoke( $control );
		return (string) ob_get_clean();
	}

	public function test_post_type_control_hides_non_public_types_by_default_and_lists_them_with_show_ui(): void {
		register_post_type( 'hyperpress_hidden', array( 'public' => false, 'show_ui' => true, 'label' => 'Hidden' ) );

		$this->assertStringNotContainsString( 'value="hyperpress_hidden"', $this->rendered_options( array() ) );
		$this->assertStringContainsString( 'value="hyperpress_hidden"', $this->rendered_options( array( 'show_ui' => true ) ) );

		unregister_post_type( 'hyperpress_hidden' );
	}

	public function test_sanitizers_accept_non_public_types_only_with_show_ui(): void {
		register_post_type( 'hyperpress_hidden', array( 'public' => false, 'show_ui' => true ) );
		register_post_type( 'hyperpress_nowhere', array( 'public' => false, 'show_ui' => false ) );
		$input = array( 'post', 'hyperpress_hidden', 'hyperpress_nowhere', 'nope' );

		$this->assertSame( array( 'post' ), PostTypeDropdownControl::sanitize_post_types( $input ) );
		$this->assertSame( array( 'post', 'hyperpress_hidden' ), PostTypeDropdownControl::sanitize_show_ui_post_types( $input ) );

		unregister_post_type( 'hyperpress_hidden' );
		unregister_post_type( 'hyperpress_nowhere' );
	}

	public function test_category_control_type(): void {
		$control = new CategoryDropdownControl( $this->manager(), 'hyperpress_test_setting', array( 'settings' => 'hyperpress_test_setting' ) );
		$this->assertSame( 'dropdown-category', $control->type );
	}
}
