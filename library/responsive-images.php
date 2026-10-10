<?php
/**
 * Configure responsive images sizes
 *
 * @package WordPress
 * @subpackage FoundationPress
 * @since FoundationPress 2.6.0
 */

// Don't load directly.
defined( 'ABSPATH' ) || exit;

// Add featured image sizes
//
// Sizes are optimized and cropped for landscape aspect ratio
// and optimized for HiDPI displays on 'small' and 'medium' screen sizes.
add_image_size( 'featured-small', 640, 200, true ); // name, width, height, crop
add_image_size( 'featured-medium', 1280, 400, true );
add_image_size( 'featured-large', 1440, 400, true );
add_image_size( 'featured-xlarge', 1920, 400, true );

// Add front page hero sizes (fallback when Slider Revolution is not available)
add_image_size( 'front-hero-small', 640, 300, true );
add_image_size( 'front-hero-medium', 1280, 400, true );
add_image_size( 'front-hero-large', 1440, 600, true );
add_image_size( 'front-hero-xlarge', 1920, 900, true );

// Add additional image sizes
add_image_size( 'fp-small', 640 );
add_image_size( 'fp-medium', 1024 );
add_image_size( 'fp-large', 1200 );
add_image_size( 'fp-xlarge', 1920 );

add_filter( 'image_size_names_choose', 'hyperpress_custom_sizes' );

// Register the new image sizes for use in the add media modal in wp-admin
function hyperpress_custom_sizes( $sizes ) {
	return array_merge(
		$sizes,
        array(
			'fp-small'  => __( 'FP Small', 'hyperpress' ),
			'fp-medium' => __( 'FP Medium', 'hyperpress' ),
			'fp-large'  => __( 'FP Large', 'hyperpress' ),
			'fp-xlarge' => __( 'FP XLarge', 'hyperpress' ),
		)
	);
}


add_filter( 'wp_calculate_image_sizes', 'hyperpress_adjust_image_sizes_attr', 10, 2 );

// Add custom image sizes attribute to enhance responsive image functionality for content images
function hyperpress_adjust_image_sizes_attr( $sizes, $size ) {

	// Actual width of image
	$width = $size[0];

	// Full width page template
	if ( is_page_template( 'page-templates/page-full-width.php' ) ) {
		if ( 1200 < $width ) {
			$sizes = '(max-width: 1199px) 98vw, 1200px';
		} else {
			$sizes = '(max-width: 1199px) 98vw, ' . $width . 'px';
		}
	} elseif ( 770 < $width ) { // Default 3/4 column post/page layout
			$sizes = '(max-width: 639px) 98vw, (max-width: 1199px) 64vw, 770px';
		} else {
		$sizes = '(max-width: 639px) 98vw, (max-width: 1199px) 64vw, ' . $width . 'px';
	}

	return $sizes;
}

add_filter( 'post_thumbnail_html', 'remove_thumbnail_dimensions', 10, 3 );

// Remove inline width and height attributes for post thumbnails
function remove_thumbnail_dimensions( $html, $post_id, $post_image_id ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- signature required by the post_thumbnail_html filter
	if ( ! strpos( $html, 'attachment-shop_single' ) ) {
		$html = preg_replace( '/^(width|height)=\"\d*\"\s/', '', $html );
	}

	return $html;
}
