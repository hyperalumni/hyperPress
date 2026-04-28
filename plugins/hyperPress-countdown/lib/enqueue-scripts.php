<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'hyperpress_countdown_scripts' ) ) :
	function hyperpress_countdown_scripts(): void {
		// Register TimeCircles
		wp_register_script( 'timecircles', plugin_dir_url( __DIR__ ) . '/assets/js/TimeCircles.js', array( 'jquery' ), '1.5.3', false );
		wp_register_style( 'timecircles', plugin_dir_url( __DIR__ ) . '/assets/css/TimeCircles.css', array(), '1.5.3', 'screen' );
	}

	add_action( 'wp_enqueue_scripts', 'hyperpress_countdown_scripts' );
endif;

if ( ! function_exists( 'hyperpress_admin_countdown_scripts' ) ) :
	function hyperpress_admin_countdown_scripts(): void {
		wp_register_script( 'homepage-countdown-metabox', plugin_dir_url( __DIR__ ) . '/assets/js/homepage-countdown-metabox.js', array( 'jquery' ), '2.11.1', true );
	}

	add_action( 'admin_enqueue_scripts', 'hyperpress_admin_countdown_scripts' );
endif;

if ( ! function_exists( 'hyperpress_countdown_metabox_editing_front' ) ) :
	function hyperpress_countdown_metabox_editing_front( $hook ): void {
		if ( $hook === 'post.php' ) {
			if ( $_GET['action'] == 'edit' ) {
				$post_id = $_GET['post'];
				if ( ! empty( $post_id ) ) {
					$template_file = get_post_meta( $post_id, '_wp_page_template', true );
					if ( $template_file !== 'page-templates/front.php' ) {
						wp_enqueue_script( 'homepage-countdown-metabox' );
					}
				}
			}
		}
	}

	add_action( 'admin_enqueue_scripts', 'hyperpress_countdown_metabox_editing_front' );
endif;