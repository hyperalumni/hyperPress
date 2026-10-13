<?php
/**
 * Customizer section for labels.
 *
 * @package HyperPress\Label
 */

namespace HyperPress\Label;

use HyperPress\Label\Controls\LabelDropdownControl;
use WP_Customize_Manager;

defined( 'ABSPATH' ) || exit;

/**
 * Adds a "Labels" section with per-post-type settings.
 */
final class Customizer {

	/**
	 * Hook the Customizer registration.
	 */
	public static function register(): void {
		add_action( 'customize_register', array( self::class, 'customize' ) );
	}

	/**
	 * Post types that get controls, as name => plural label.
	 *
	 * @return array<string,string>
	 */
	public static function post_types(): array {
		return Settings::post_types();
	}

	/**
	 * Register the section, settings and controls.
	 *
	 * @param WP_Customize_Manager $manager Customizer manager.
	 */
	public static function customize( WP_Customize_Manager $manager ): void {
		$manager->add_section(
			'hyperpress_label',
			array(
				'title'    => __( 'Labels', 'hyperpress' ),
				'priority' => 140,
			)
		);

		foreach ( self::post_types() as $post_type => $plural ) {
			$enabled_key = Resolver::enabled_key( $post_type );
			$labels_key  = Resolver::labels_key( $post_type );

			$manager->add_setting(
				$enabled_key,
				array(
					'type'              => 'theme_mod',
					'transport'         => 'refresh',
					'default'           => Resolver::default_enabled( $post_type ),
					'sanitize_callback' => 'rest_sanitize_boolean',
				)
			);
			$manager->add_control(
				$enabled_key,
				array(
					'type'    => 'checkbox',
					'section' => 'hyperpress_label',
					/* translators: %s: plural post type name. */
					'label'   => sprintf( __( 'Show labels on %s', 'hyperpress' ), $plural ),
				)
			);

			$manager->add_setting(
				$labels_key,
				array(
					'type'              => 'theme_mod',
					'transport'         => 'refresh',
					'default'           => Resolver::default_labels( $post_type ),
					'sanitize_callback' => array( self::class, 'sanitize_labels' ),
				)
			);
			$manager->add_control(
				new LabelDropdownControl(
					$manager,
					$labels_key,
					array(
						'section'     => 'hyperpress_label',
						/* translators: %s: plural post type name. */
						'label'       => sprintf( __( 'Labels on %s', 'hyperpress' ), $plural ),
						'description' => __( 'Labels selected here appear on this post type. Select none to show no labels.', 'hyperpress' ),
					)
				)
			);
		}
	}

	/**
	 * Sanitize the chosen label IDs: keep registered IDs only, without duplicates.
	 *
	 * @param mixed $value Submitted value.
	 * @return string[]
	 */
	public static function sanitize_labels( $value ): array {
		return Settings::sanitize_labels( $value );
	}
}
