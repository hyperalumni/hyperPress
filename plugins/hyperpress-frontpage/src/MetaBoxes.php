<?php
namespace HyperPress\Frontpage;

defined( 'ABSPATH' ) || exit;

/**
 * Meta Box fields for the Front Page settings page.
 */
final class MetaBoxes {

	public static function register(): void {
		add_filter( 'rwmb_meta_boxes', array( self::class, 'boxes' ) );
	}

	/**
	 * Adds the Front Page box.
	 *
	 * @param array<int,array<string,mixed>> $boxes Meta boxes.
	 * @return array<int,array<string,mixed>>
	 */
	public static function boxes( array $boxes ): array {
		$boxes[] = array(
			'id'             => 'hyperpress_frontpage_box',
			'title'          => __( 'Front Page', 'hyperpress' ),
			'settings_pages' => SettingsPage::ID,
			'fields'         => array(
				array(
					'id'                => Settings::CATEGORIES,
					'name'              => __( 'Categories', 'hyperpress' ),
					'type'              => 'select_advanced',
					'multiple'          => true,
					'options'           => self::category_options(),
					'std'               => array(),
					'placeholder'       => __( 'Select categories', 'hyperpress' ),
					'sanitize_callback' => array( Sanitizers::class, 'categories' ),
				),
				array(
					'id'                => Settings::POST_TYPES,
					'name'              => __( 'Post Types', 'hyperpress' ),
					'type'              => 'select_advanced',
					'multiple'          => true,
					'options'           => self::post_type_options(),
					'std'               => array( 'post' ),
					'sanitize_callback' => array( Sanitizers::class, 'post_types' ),
				),
				array(
					'id'         => Settings::BANNER_TITLE,
					'name'       => __( 'Banner Title', 'hyperpress' ),
					'type'       => 'text',
					'limit'      => 4,
					'limit_type' => 'word',
				),
				array(
					'id'         => Settings::BANNER_SUBTITLE,
					'name'       => __( 'Banner Subtitle', 'hyperpress' ),
					'type'       => 'textarea',
					'raw'        => true,
					'limit'      => 100,
					'limit_type' => 'word',
				),
				array(
					'id'   => Settings::BANNER_BACKGROUND_COLOR,
					'name' => __( 'Background Color', 'hyperpress' ),
					'type' => 'color',
				),
				array(
					'id'         => Settings::BANNER_BUTTON_TEXT,
					'name'       => __( 'Banner Button Text', 'hyperpress' ),
					'type'       => 'text',
					'limit'      => 4,
					'limit_type' => 'word',
				),
				array(
					'id'          => Settings::BANNER_BUTTON_PAGE,
					'name'        => __( 'Banner Button Page', 'hyperpress' ),
					'type'        => 'post',
					'post_type'   => 'page',
					'field_type'  => 'select_advanced',
					'placeholder' => __( 'Select a page', 'hyperpress' ),
					'query_args'  => array(
						'post_status'    => 'publish',
						'posts_per_page' => -1,
					),
				),
				array(
					'id'          => Settings::COUNTDOWNS,
					'name'        => __( 'Countdowns', 'hyperpress' ),
					'type'        => 'post',
					'post_type'   => array( 'hyper_countdown' ),
					'multiple'    => true,
					'parent'      => false,
					'field_type'  => 'select_advanced',
					'placeholder' => __( 'Select a countdown', 'hyperpress' ),
				),
			),
		);

		return $boxes;
	}

	/**
	 * Categories as term id => name (empty ones included).
	 *
	 * @return array<int,string>
	 */
	private static function category_options(): array {
		$options = array();
		foreach ( get_categories( array( 'hide_empty' => false ) ) as $category ) {
			$options[ $category->term_id ] = $category->name;
		}
		return $options;
	}

	/**
	 * Public post types as value => label.
	 *
	 * @return array<string,string>
	 */
	private static function post_type_options(): array {
		$options = array();
		foreach ( get_post_types( array( 'public' => true ), 'objects' ) as $name => $object ) {
			$options[ $name ] = $object->labels->name;
		}
		return $options;
	}
}
