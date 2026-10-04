<?php

namespace HYPER_Press_Season;

// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

if ( ! function_exists( '\HYPER_Press_Season\hyperpress_season_rewrite' ) ) :
	add_action( 'init', '\HYPER_Press_Season\hyperpress_season_rewrite' );

	function hyperpress_season_rewrite(): void {
		add_rewrite_rule( '^seasons/([^/]+)/([^/]+)?s$', query: 'index.php?hyper_season=$matches[1]&post_type=$matches[2]', after: 'top' );
	}
endif;