<?php
if ( ! function_exists( 'hyperpress_socials_load_widgets' ) ) :

	function hyperpress_socials_load_widgets(): void {
		require_once( 'widget.php' );
		register_widget( 'hyperpress_socials_widget' );
	}

	add_action( 'widgets_init', 'hyperpress_socials_load_widgets' );
endif;
