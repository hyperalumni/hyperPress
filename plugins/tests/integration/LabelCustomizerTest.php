<?php
/**
 * Tests for the label Customizer section.
 *
 * @package HyperPress\Tests
 */

namespace HyperPress\Tests\Integration;

use HyperPress\Label\Customizer;
use HyperPress\Label\Registry;
use HyperPress\Label\Resolver;
use WP_Customize_Manager;
use WP_UnitTestCase;

/**
 * @covers \HyperPress\Label\Customizer
 * @covers \HyperPress\Label\Controls\LabelDropdownControl
 */
final class LabelCustomizerTest extends WP_UnitTestCase {

	/**
	 * A callable that returns nothing useful.
	 *
	 * @return array<int,array<string,string>>
	 */
	public static function noop(): array {
		return array();
	}

	private function customizer(): WP_Customize_Manager {
		require_once ABSPATH . WPINC . '/class-wp-customize-manager.php';
		$manager = new WP_Customize_Manager();
		do_action( 'customize_register', $manager );
		return $manager;
	}

	public function set_up(): void {
		parent::set_up();
		Registry::reset();
	}

	public function tear_down(): void {
		foreach ( array( 'post', 'page', 'hyper_x' ) as $type ) {
			remove_theme_mod( Resolver::enabled_key( $type ) );
			remove_theme_mod( Resolver::labels_key( $type ) );
		}
		Registry::reset();
		parent::tear_down();
	}

	public function test_section_exists_with_priority_140(): void {
		$section = $this->customizer()->get_section( 'hyperpress_label' );

		$this->assertNotNull( $section );
		$this->assertSame( 'Labels', $section->title );
		$this->assertSame( 140, $section->priority );
	}

	public function test_each_public_post_type_has_both_settings_and_controls_in_the_section(): void {
		$manager = $this->customizer();

		foreach ( array( 'post', 'page' ) as $type ) {
			foreach ( array( Resolver::enabled_key( $type ), Resolver::labels_key( $type ) ) as $key ) {
				$this->assertNotNull( $manager->get_setting( $key ), $key );
				$this->assertSame( 'theme_mod', $manager->get_setting( $key )->type, $key );
				$this->assertSame( 'hyperpress_label', $manager->get_control( $key )->section, $key );
			}
		}
		$this->assertSame( 'checkbox', $manager->get_control( Resolver::enabled_key( 'post' ) )->type );
		$this->assertSame( 'dropdown-labels', $manager->get_control( Resolver::labels_key( 'post' ) )->type );
		$this->assertNull( $manager->get_setting( Resolver::enabled_key( 'attachment' ) ) );
		$this->assertNull( $manager->get_control( Resolver::labels_key( 'attachment' ) ) );
	}

	public function test_labels_control_explains_the_empty_selection(): void {
		$control = $this->customizer()->get_control( Resolver::labels_key( 'post' ) );

		$this->assertSame(
			'Labels selected here appear on this post type. Select none to show no labels.',
			$control->description
		);
	}

	public function test_defaults_match_the_resolver(): void {
		$manager = $this->customizer();

		$this->assertTrue( $manager->get_setting( Resolver::enabled_key( 'post' ) )->default );
		$this->assertSame(
			Resolver::default_labels( 'post' ),
			$manager->get_setting( Resolver::labels_key( 'post' ) )->default
		);
		$this->assertContains( 'post.date', Resolver::default_labels( 'post' ) );
		$this->assertFalse( $manager->get_setting( Resolver::enabled_key( 'page' ) )->default );
		$this->assertSame( array(), $manager->get_setting( Resolver::labels_key( 'page' ) )->default );
	}

	public function test_sanitize_labels_keeps_only_registered_ids(): void {
		$this->assertSame(
			array( 'post.date' ),
			Customizer::sanitize_labels( array( 'post.date', 'nope', 'post.date', 5 ) )
		);
	}

	public function test_sanitize_labels_rejects_non_arrays(): void {
		$this->assertSame( array(), Customizer::sanitize_labels( 'x' ) );
		$this->assertSame( array(), Customizer::sanitize_labels( null ) );
	}

	public function test_sanitize_labels_runs_through_the_setting(): void {
		$setting = $this->customizer()->get_setting( Resolver::labels_key( 'post' ) );

		$this->assertSame( array( 'post.author' ), $setting->sanitize( array( 'post.author', 'post.author', 'x' ) ) );
	}

	public function test_stale_saved_ids_do_not_break_registration(): void {
		set_theme_mod( Resolver::labels_key( 'post' ), array( 'gone' ) );

		$manager = $this->customizer();

		$this->assertNotNull( $manager->get_control( Resolver::labels_key( 'post' ) ) );
	}

	public function test_the_dropdown_renders_escaped_group_and_title_options(): void {
		$registry = Registry::instance();
		$registry->add(
			'x.one',
			array(
				'group'    => 'G<b>',
				'title'    => 'T&1',
				'callback' => array( self::class, 'noop' ),
			)
		);
		$control = $this->customizer()->get_control( Resolver::labels_key( 'post' ) );

		ob_start();
		( new \ReflectionMethod( $control, 'render_content' ) )->invoke( $control );
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( 'multiple', $html );
		$this->assertStringContainsString( 'value="x.one"', $html );
		$this->assertStringContainsString( 'G&lt;b&gt;: T&amp;1', $html );
		$this->assertStringNotContainsString( '<b>', $html );
	}

	public function test_the_dropdown_description_keeps_harmless_markup_and_drops_scripts(): void {
		Registry::instance()->add(
			'x.one',
			array(
				'group'    => 'G',
				'title'    => 'T',
				'callback' => array( self::class, 'noop' ),
			)
		);
		$control              = $this->customizer()->get_control( Resolver::labels_key( 'post' ) );
		$control->description = 'See <a href="https://example.org/docs">the docs</a> and <em>note</em><script>alert(1)</script><img src=x onerror=alert(2)>';

		ob_start();
		( new \ReflectionMethod( $control, 'render_content' ) )->invoke( $control );
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( '<a href="https://example.org/docs">the docs</a>', $html );
		$this->assertStringContainsString( '<em>note</em>', $html );
		$this->assertStringNotContainsString( '<script', $html );
		$this->assertStringNotContainsString( 'onerror', $html );
	}

	public function test_a_post_type_named_only_in_label_defaults_gets_controls(): void {
		Registry::reset( true );
		Registry::instance()->add(
			'x.one',
			array(
				'group'              => 'X',
				'title'              => 'One',
				'callback'           => array( self::class, 'noop' ),
				'default_post_types' => array( 'hyper_x' ),
			)
		);

		$manager = $this->customizer();

		$this->assertNotNull( $manager->get_control( Resolver::enabled_key( 'hyper_x' ) ) );
		$this->assertNotNull( $manager->get_control( Resolver::labels_key( 'hyper_x' ) ) );
		$this->assertTrue( $manager->get_setting( Resolver::enabled_key( 'hyper_x' ) )->default );
		$this->assertSame( array( 'x.one' ), $manager->get_setting( Resolver::labels_key( 'hyper_x' ) )->default );
	}
}
