<?php
namespace HyperPress\Season;

defined( 'ABSPATH' ) || exit;

use HyperPress\Utils\Controls\PostTypeDropdownControl;
use WP_Customize_Code_Editor_Control;

final class Customizer {

	public static function register(): void {
		add_action( 'customize_register', array( self::class, 'customize' ) );
	}

	public static function customize( $wp_customize ): void {
		$wp_customize->add_setting(
			'hyperpress_season_banner_subtitle',
			array(
				'default'           => '',
				'type'              => 'theme_mod',
				'transport'         => 'refresh',
				'sanitize_callback' => 'wp_kses_post',
			)
		);
		$wp_customize->add_control(
			new WP_Customize_Code_Editor_Control(
				$wp_customize,
				'hyperpress_season_banner_subtitle',
				array(
					'label'    => __( 'Season Banner Subtitle', 'hyperpress' ),
					'settings' => 'hyperpress_season_banner_subtitle',
					'section'  => 'hyperpress_season',
				)
			)
		);

		$wp_customize->add_setting(
			'hyperpress_season_post_types',
			array(
				'type'              => 'theme_mod',
				'default'           => array( 'post' ),
				'sanitize_callback' => array( PostTypeDropdownControl::class, 'sanitize_show_ui_post_types' ),
			)
		);
		$wp_customize->add_control(
			new PostTypeDropdownControl(
				$wp_customize,
				'hyperpress_season_post_types',
				array(
					'section'     => 'hyperpress_season',
					'label'       => __( 'Seasons Post Types', 'hyperpress' ),
					'description' => __( 'Select the Post Types that will use the Season Taxonomy', 'hyperpress' ),
					'settings'    => 'hyperpress_season_post_types',
				),
				array( 'show_ui' => true )
			)
		);

		$wp_customize->add_setting(
			'hyperpress_season_current',
			array(
				'type'              => 'theme_mod',
				'default'           => '',
				'sanitize_callback' => array( self::class, 'sanitize_current' ),
			)
		);
		$seasons = get_terms(
			array(
				'taxonomy'   => 'hyper_season',
				'hide_empty' => false,
				'orderby'    => 'term_id',
				'order'      => 'ASC',
			)
		);
		$seasons = array_reduce(
			is_wp_error( $seasons ) ? array() : $seasons,
			function ( $carry, $season ) {
				$carry[ $season->slug ] = $season->name;

				return $carry;
			},
			array()
		);
		$wp_customize->add_control(
			'hyperpress_season_current',
			array(
				'section'     => 'hyperpress_season',
				'label'       => __( 'Current Season', 'hyperpress' ),
				'description' => __( 'Select the current season for the site', 'hyperpress' ),
				'settings'    => 'hyperpress_season_current',
				'type'        => 'select',
				'choices'     => $seasons,
			)
		);
	}

	/**
	 * Sanitize callback for the current season: the slug of an existing season, else an empty string.
	 *
	 * @param mixed $value Submitted value.
	 */
	public static function sanitize_current( $value ): string {
		if ( ! is_string( $value ) || '' === $value ) {
			return '';
		}
		return get_term_by( 'slug', $value, 'hyper_season' ) ? $value : '';
	}
}
