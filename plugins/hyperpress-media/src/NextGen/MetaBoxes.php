<?php
namespace HyperPress\Media\NextGen;

defined( 'ABSPATH' ) || exit;

final class MetaBoxes {

	public static function register(): void {
		add_filter( 'rwmb_meta_boxes', array( self::class, 'boxes' ) );
	}

	/**
	 * Adds the Media settings box.
	 *
	 * @param array<int,array<string,mixed>> $boxes Meta boxes.
	 * @return array<int,array<string,mixed>>
	 */
	public static function boxes( array $boxes ): array {
		$boxes[] = array(
			'id'             => 'hyperpress_media_box',
			'title'          => __( 'NextGen Gallery', 'hyperpress' ),
			'settings_pages' => SettingsPage::ID,
			'fields'         => array(
				array(
					'id'                => Settings::PHOTOS_PAGE,
					'name'              => __( 'Page Displaying Photos', 'hyperpress' ),
					'desc'              => __( 'For breadcrumb generation', 'hyperpress' ),
					'type'              => 'post',
					'post_type'         => 'page',
					'field_type'        => 'select_advanced',
					'placeholder'       => __( 'Select a page', 'hyperpress' ),
					'query_args'        => array(
						'post_status'    => 'publish',
						'posts_per_page' => -1,
					),
					'sanitize_callback' => 'absint',
				),
			),
		);

		return $boxes;
	}
}
