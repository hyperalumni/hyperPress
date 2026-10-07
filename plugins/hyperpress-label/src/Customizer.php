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
	 * Public types except `attachment`, plus any type named in a label's default types.
	 *
	 * @return array<string,string>
	 */
	public static function post_types(): array {
		$types = array();
		foreach ( get_post_types( array( 'public' => true ), 'objects' ) as $name => $object ) {
			if ( 'attachment' !== $name ) {
				$types[ $name ] = (string) $object->labels->name;
			}
		}

		$registry = Registry::instance();
		foreach ( $registry->ids() as $id ) {
			foreach ( $registry->default_post_types( $id ) as $name ) {
				if ( ! is_string( $name ) || '' === $name || isset( $types[ $name ] ) ) {
					continue;
				}
				$object         = get_post_type_object( $name );
				$types[ $name ] = null !== $object ? (string) $object->labels->name : $name;
			}
		}

		return $types;
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
		if ( ! is_array( $value ) ) {
			return array();
		}
		$registered = Registry::instance()->ids();
		$clean      = array();
		foreach ( $value as $id ) {
			if ( is_string( $id ) && in_array( $id, $registered, true ) && ! in_array( $id, $clean, true ) ) {
				$clean[] = $id;
			}
		}
		return $clean;
	}
}
