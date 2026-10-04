<?php
namespace HyperPress\Tests\Integration;

use HyperPress\Tests\Support\Convention;
use WP_Customize_Manager;
use WP_UnitTestCase;

final class IdentifierConventionTest extends WP_UnitTestCase {

	private function assert_identifier( string $id, string $context ): void {
		$this->assertMatchesRegularExpression( Convention::IDENTIFIER_PATTERN, $id, "$context: '$id'" );
	}

	public function test_meta_box_and_field_ids(): void {
		$boxes = apply_filters( 'rwmb_meta_boxes', array() );
		$this->assertNotEmpty( $boxes, 'No meta boxes were registered through rwmb_meta_boxes' );

		foreach ( $boxes as $box ) {
			$this->assert_identifier( (string) ( $box['id'] ?? '' ), 'meta box id' );
			foreach ( $box['fields'] ?? array() as $field ) {
				// Presentational field types legitimately carry no id.
				$no_id_types = array( 'heading', 'divider', 'custom_html', 'button' );
				if ( ( ! isset( $field['id'] ) || '' === $field['id'] ) && in_array( $field['type'] ?? '', $no_id_types, true ) ) {
					continue;
				}
				$this->assert_identifier( (string) ( $field['id'] ?? '' ), 'meta field id in ' . $box['id'] );
			}
			foreach ( (array) ( $box['post_types'] ?? array() ) as $post_type ) {
				$this->assertArrayHasKey( $post_type, Convention::POST_TYPES + array( 'post' => '', 'page' => '' ), "box {$box['id']} targets '$post_type'" );
			}
		}
	}

	public function test_customizer_identifiers(): void {
		require_once ABSPATH . WPINC . '/class-wp-customize-manager.php';
		$manager = new WP_Customize_Manager();
		do_action( 'customize_register', $manager );

		// Every HYPERpress setting already carries the hyperpress_ prefix in the legacy code, so that
		// prefix identifies "ours" both before and after the migration. Core settings never match.
		$our_settings = array_filter(
			$manager->settings(),
			static fn( $s ) => str_starts_with( $s->id, 'hyperpress_' )
		);
		$this->assertNotEmpty( $our_settings, 'No HYPERpress customizer settings were registered' );

		foreach ( $our_settings as $setting ) {
			$this->assert_identifier( $setting->id, 'customizer setting' );
		}

		// Controls bound to one of our settings: id must equal the setting id, section must follow the
		// convention unless it is a WordPress core section.
		$core_sections = array( 'colors', 'title_tagline', 'header_image', 'background_image', 'static_front_page', 'custom_css' );
		$used          = array();
		foreach ( $manager->controls() as $control ) {
			$setting_id = isset( $control->settings['default'] ) ? $control->settings['default']->id : '';
			if ( ! isset( $our_settings[ $setting_id ] ) ) {
				continue;
			}
			$this->assertSame( $setting_id, $control->id, "control '{$control->id}' must equal its setting id '$setting_id'" );
			$used[ $control->section ] = true;
		}
		foreach ( array_keys( $used ) as $section_id ) {
			if ( ! in_array( $section_id, $core_sections, true ) ) {
				$this->assert_identifier( (string) $section_id, 'customizer section used by a HYPERpress control' );
			}
		}
		foreach ( $manager->panels() as $panel ) {
			if ( str_starts_with( $panel->id, 'hyperpress' ) ) {
				$this->assert_identifier( $panel->id, 'customizer panel' );
			}
		}
	}

	public function test_widget_id_bases(): void {
		global $wp_widget_factory;
		$ours = 0;
		foreach ( $wp_widget_factory->widgets as $widget ) {
			// Factory keys are the strings passed to register_widget() (often with a leading backslash), so use the real class name.
			$class = ltrim( get_class( $widget ), '\\' );
			if ( ! str_starts_with( $class, 'HyperPress' ) && ! str_starts_with( $class, 'HYPER_Press' ) ) {
				continue;
			}
			++$ours;
			$this->assert_identifier( $widget->id_base, "widget $class" );
		}
		$this->assertGreaterThan( 0, $ours, 'No HYPERpress widgets found' );
	}

	public function test_script_and_style_handles(): void {
		do_action( 'wp_enqueue_scripts' );
		foreach ( array( wp_scripts(), wp_styles() ) as $registry ) {
			$this->assertArrayNotHasKey( 'timecircles', $registry->registered, 'Handle "timecircles" must be renamed to hyperpress-countdown-timecircles' );
		}
	}
}
