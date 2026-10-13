<?php
namespace HyperPress\Season;

defined( 'ABSPATH' ) || exit;

final class MetaBoxes {

	public static function register(): void {
		add_filter( 'rwmb_meta_boxes', array( self::class, 'boxes' ) );
	}

	/**
	 * Adds the Season box.
	 *
	 * @param array<int,array<string,mixed>> $boxes Meta boxes.
	 * @return array<int,array<string,mixed>>
	 */
	public static function boxes( array $boxes ): array {
		$boxes[] = array(
			'id'             => 'hyperpress_season_box',
			'title'          => __( 'Season', 'hyperpress' ),
			'settings_pages' => SettingsPage::ID,
			'fields'         => array(
				array(
					'id'                => Settings::CURRENT,
					'name'              => __( 'Current Season', 'hyperpress' ),
					'desc'              => __( 'Select the current season for the site', 'hyperpress' ),
					'type'              => 'select_advanced',
					'options'           => self::season_options(),
					'sanitize_callback' => array( Sanitizers::class, 'current' ),
				),
				array(
					'id'                => Settings::BANNER_SUBTITLE,
					'name'              => __( 'Season Banner Subtitle', 'hyperpress' ),
					'desc'              => __( 'Shown under the title on season pages. Shortcodes such as [hyperpress_emphasis]text[/hyperpress_emphasis] work, and so do <strong>, <em> and <br> tags; other markup is removed when the banner is displayed.', 'hyperpress' ),
					'type'              => 'textarea',
					'raw'               => true,
					'sanitize_callback' => array( Sanitizers::class, 'banner_subtitle' ),
				),
				array(
					'id'                => Settings::POST_TYPES,
					'name'              => __( 'Seasons Post Types', 'hyperpress' ),
					'desc'              => __( 'Select the Post Types that will use the Season Taxonomy', 'hyperpress' ),
					'type'              => 'select_advanced',
					'multiple'          => true,
					'options'           => self::post_type_options(),
					'sanitize_callback' => array( Sanitizers::class, 'post_types' ),
				),
			),
		);

		return $boxes;
	}

	/**
	 * Seasons as slug => name, with an empty choice first.
	 *
	 * @return array<string,string>
	 */
	private static function season_options(): array {
		$options = array( '' => __( 'None', 'hyperpress' ) );
		$seasons = get_terms(
			array(
				'taxonomy'   => 'hyper_season',
				'hide_empty' => false,
				'orderby'    => 'name',
			)
		);
		foreach ( is_wp_error( $seasons ) ? array() : $seasons as $season ) {
			$options[ $season->slug ] = $season->name;
		}
		return $options;
	}

	/**
	 * Post types that show a UI as name => label.
	 *
	 * @return array<string,string>
	 */
	private static function post_type_options(): array {
		$options = array();
		foreach ( get_post_types( array( 'show_ui' => true ), 'objects' ) as $name => $object ) {
			$options[ $name ] = $object->labels->name;
		}
		return $options;
	}
}
