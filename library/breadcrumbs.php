<?php
// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

require_once( 'breadcrumbs/404.php' );
require_once( 'breadcrumbs/author.php' );
require_once( 'breadcrumbs/category.php' );
require_once( 'breadcrumbs/day.php' );
require_once( 'breadcrumbs/month.php' );
require_once( 'breadcrumbs/nggallery.php' );
require_once( 'breadcrumbs/page.php' );
require_once( 'breadcrumbs/paged.php' );
require_once( 'breadcrumbs/post.php' );
require_once( 'breadcrumbs/search.php' );
require_once( 'breadcrumbs/tag.php' );
require_once( 'breadcrumbs/year.php' );

if ( ! function_exists( 'hyperpress_breadcrumbs_custom_post_type' ) ) :
	function hyperpress_breadcrumbs_custom_post_type( $breadcrumbs, $post_type, $custom_tax = 'season' ) {
		if ( ! empty( $post_type ) ) {
			if ( is_singular( $post_type ) || is_post_type_archive( $post_type ) ) {
				echo "is_singular || is_post_type_archive";
				if ( is_singular( $post_type ) ) {
					echo "is_singular";
				}
				if ( is_post_type_archive( $post_type ) ) {
					echo "is_post_type_archive";
					$current_tax = get_queried_object();
					var_dump( $current_tax );
				}
				$post_type_data = get_post_type_object( $post_type );
				if ( ! empty( $post_type_data ) ) {
					$post_type_label = $post_type_data->labels->name;
					if ( ! empty( $post_type_label ) ) {
						$breadcrumbs[] = [
							"title"   => $post_type_label,
							'url'     => get_post_type_archive_link( $post_type ),
							"classes" => [
								'li'    => [
									'item-archive-' . $post_type_label
								],
								'bread' => [
									'bread-archive-' . $post_type_label
								]
							]
						];
					}
				}

				if ( is_tax( $custom_tax ) || is_singular( $post_type ) ) {
					echo "is_tax || is_singular";
					if ( is_singular( $post_type ) ) {
						echo "is_singular";
						$custom_taxes = wp_get_object_terms( get_the_ID(), $custom_tax, [
							'orderby' => 'name',
							'order'   => 'DESC'
						] );
						if ( ! empty( $custom_taxes ) ) {
							// still sort them by name, because the result of get_terms is inconsistent even with the orderby flag
							usort( $custom_taxes, fn( $a, $b ) => $b->name <=> $a->name );
							$current_tax = $custom_taxes[0];
						}
					} else {
						echo "is_tax";
						$current_tax = get_taxonomy($custom_tax);
					}

					if ( ! empty( $current_tax ) ) {
						$breadcrumbs[] = [
							"title"   => $current_tax->name,
							'url'     => get_category_link( $current_tax->term_id ) . $post_type_data->has_archive,
							"classes" => [
								'li'    => [
									'item-' . $current_tax->slug . '-' . $post_type_label
								],
								'bread' => [
									'bread-' . $current_tax->slug . '-' . $post_type_label
								]
							]
						];
					}
					if ( is_singular( $post_type ) ) {
						echo "is_singular";
						$post_type_name = $post_type_data->name;
						// add current sponsor
						$breadcrumbs[] = [
							"title"   => get_the_title(),
							"classes" => [
								'li'    => [
									'item-' . $post_type_name,
									'item-' . $post_type_name . get_the_ID()
								],
								'bread' => [
									'bread-' . $post_type_name,
									'bread-' . $post_type_name . get_the_ID()
								]
							]
						];
					}
				}
			}
		}

		return $breadcrumbs;
	}
endif;
