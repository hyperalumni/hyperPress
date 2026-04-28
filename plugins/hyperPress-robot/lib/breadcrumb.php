<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'hyperpress_breadcrumb_robot' ) ) :
	function hyperpress_breadcrumb_robot( $breadcrumbs ) {
		$hyper_robot = 'robot';
		if ( is_singular( $hyper_robot ) || is_post_type_archive( $hyper_robot ) ) {
			$hyper_robot_data = get_post_type_object( $hyper_robot );
			$robotLabel       = $hyper_robot_data->labels->name;

			if ( is_post_type_archive( $hyper_robot ) ) {
				$breadcrumbs[] = [
					"title"   => $robotLabel,
					"classes" => [
						'li'    => [
							'item-' . $robotLabel
						],
						'bread' => [
							'bread-' . $robotLabel
						]
					]
				];
			} else {
				$breadcrumbs[] = [
					"title"   => $robotLabel,
					'url'     => get_post_type_archive_link( $hyper_robot ),
					"classes" => [
						'li'    => [
							'item-parent',
							'item-parent-' . $hyper_robot
						],
						'bread' => [
							'bread-parent',
							'bread-parent-' . $hyper_robot
						]
					]
				];
				global $post;
				if ( is_singular( $hyper_robot ) ) {
					// Single Robot
					$breadcrumbs[] = [
						"title"   => get_the_title(),
						"classes" => [
							'li'    => [ 'item-robot', 'item-robot-' . $post->ID ],
							'bread' => [ 'bread-robot', 'bread-robot-' . $post->ID ]
						]
					];
				}
			}
		}

		return $breadcrumbs;
	}

	add_filter( 'hyperpress_breadcrumbs_content', 'hyperpress_breadcrumb_robot' );
endif;
