<?php
namespace HyperPress\Season;

use HyperPress\Utils\TaxonomyAdminFilter;

defined( 'ABSPATH' ) || exit;

final class AdminFilter {

	public static function register(): void {
		add_action( 'restrict_manage_posts', array( self::class, 'render' ) );
	}

	public static function render( $post_type = '' ): void {
		if ( ! is_string( $post_type ) ) {
			return;
		}
		TaxonomyAdminFilter::render( Plugin::TAXONOMY, __( 'All seasons', 'hyperpress' ), $post_type );
	}
}
