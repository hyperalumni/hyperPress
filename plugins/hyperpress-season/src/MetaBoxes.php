<?php
namespace HyperPress\Season;

defined( 'ABSPATH' ) || exit;

final class MetaBoxes {

	public static function register(): void {
		add_filter( 'rwmb_meta_boxes', array( self::class, 'customize_metabox' ) );
	}

	public static function customize_metabox( $meta_boxes ) {
		$meta_boxes[] = array(
			'title'    => __( 'Seasons', 'hyperpress' ),
			'id'       => 'hyperpress_season',
			'panel'    => '',
			'priority' => 130,
		);

		return $meta_boxes;
	}
}
