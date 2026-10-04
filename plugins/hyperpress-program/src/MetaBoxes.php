<?php
namespace HyperPress\Program;

defined( 'ABSPATH' ) || exit;

final class MetaBoxes {

	public static function register(): void {
		add_filter( 'rwmb_meta_boxes', array( self::class, 'customize_metabox' ) );
	}

	public static function customize_metabox( $meta_boxes ) {
		$meta_boxes[] = array(
			'title'    => __( 'Programs', 'hyperpress' ),
			'id'       => 'hyperpress_program',
			'panel'    => '',
			'priority' => 130,
		);

		return $meta_boxes;
	}
}
