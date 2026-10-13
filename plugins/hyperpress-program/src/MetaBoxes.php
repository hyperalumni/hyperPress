<?php
namespace HyperPress\Program;

defined( 'ABSPATH' ) || exit;

final class MetaBoxes {

	public static function register(): void {
		add_filter( 'rwmb_meta_boxes', array( self::class, 'boxes' ) );
	}

	/**
	 * Adds the Program box.
	 *
	 * @param array<int,array<string,mixed>> $boxes Meta boxes.
	 * @return array<int,array<string,mixed>>
	 */
	public static function boxes( array $boxes ): array {
		$boxes[] = array(
			'id'             => 'hyperpress_program_box',
			'title'          => __( 'Program', 'hyperpress' ),
			'settings_pages' => SettingsPage::ID,
			'fields'         => array(
				array(
					'id'                => Settings::POST_TYPES,
					'name'              => __( 'Programs Post Types', 'hyperpress' ),
					'desc'              => __( 'Select the Post Types that will use the Program Taxonomy', 'hyperpress' ),
					'type'              => 'select_advanced',
					'multiple'          => true,
					'options'           => self::post_type_options(),
					'std'               => array( 'post' ),
					'sanitize_callback' => array( Sanitizers::class, 'post_types' ),
				),
			),
		);

		return $boxes;
	}

	/**
	 * Public post types as name => label.
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
