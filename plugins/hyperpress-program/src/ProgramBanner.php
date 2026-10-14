<?php
namespace HyperPress\Program;

use HyperPress\Utils\RequestTerm;

defined( 'ABSPATH' ) || exit;

final class ProgramBanner {

	public static function register(): void {
		// Priority 20: the plugin banners (season, competition, newsletter, ...) run at 10 and claim mixed requests first; the theme fallbacks run at 80 and 90.
		add_filter( 'hyperpress_banner_content', array( self::class, 'filter' ), 20 );
	}

	public static function filter( $banner ) {
		if ( ! is_array( $banner ) || ! empty( $banner['type'] ) || ! is_tax() || is_post_type_archive() ) {
			return $banner;
		}

		// is_tax( $taxonomy ) only matches the one taxonomy WP picks as the queried object, so ask the program itself.
		$term = RequestTerm::get( Plugin::TAXONOMY );
		if ( ! $term ) {
			return $banner;
		}

		$banner['type']            = 'program';
		$banner['backgroundColor'] = get_theme_mod( 'hyperpress_hyper_red' );

		if ( empty( $banner['title'] ) ) {
			$banner['title'] = $term->name;
		}

		if ( empty( $banner['subtitle'] ) ) {
			// The banner template only allows a few inline tags and escapes later; stripping is the safe choice for a term description.
			$banner['subtitle'] = trim( wp_strip_all_tags( $term->description ) );
		}

		return $banner;
	}
}
