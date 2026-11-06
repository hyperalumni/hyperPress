<?php
/**
 * Helpers that wrap functions and classes from the HYPER plugins, so the theme keeps working (with less
 * data) when a plugin is deactivated or missing.
 *
 * @package hyperPress
 */

// Don't load directly.
defined( 'ABSPATH' ) || exit;

/**
 * Reads a Meta Box custom field, or '' when Meta Box is not active.
 *
 * @param string   $key     Field id.
 * @param int|null $post_id Post ID; null for the current post.
 * @return mixed
 */
function hyperpress_meta( $key, $post_id = null ) {
	if ( ! function_exists( 'rwmb_meta' ) ) {
		return '';
	}

	return rwmb_meta( $key, '', $post_id );
}

/**
 * Seasons of the current post, newest first, or an empty array when hyperpress-season is not active.
 *
 * @return array
 */
function hyperpress_sorted_seasons(): array {
	if ( ! class_exists( '\HyperPress\Season\SortedSeasons' ) ) {
		return array();
	}

	return (array) \HyperPress\Season\SortedSeasons::get();
}

/**
 * Width / height ratio for the NextGen thumbnail placeholder, or 1.0 when either value is missing,
 * non-numeric or not positive (avoids a DivisionByZeroError / TypeError on bad NextGen settings).
 *
 * @param mixed $width  Thumbnail width.
 * @param mixed $height Thumbnail height.
 */
function hyperpress_nextgen_ratio( $width, $height ): float {
	if ( ! is_numeric( $width ) || ! is_numeric( $height ) || (float) $width <= 0 || (float) $height <= 0 ) {
		return 1.0;
	}

	return (float) $width / (float) $height;
}

/**
 * Rebuild NextGEN's pagination HTML as Foundation 6 pagination markup.
 *
 * Anchors keep NextGEN's own classes and data attributes, because its AJAX pagination script binds to them.
 * Previous/next links are labelled by page number: NextGEN's own prev/next class names are swapped.
 * Returns the input unchanged when it is not the expected NextGEN markup.
 *
 * @param string $html NextGEN's `$pagination` string.
 * @return string Foundation pagination HTML, or $html when it is not recognised.
 */
function hyperpress_nextgen_pagination( string $html ): string {
	if ( ! preg_match_all( '#<(a|span)\b([^>]*)>(.*?)</\1>#is', $html, $items, PREG_SET_ORDER ) ) {
		return $html;
	}

	$current = 0;
	foreach ( $items as $item ) {
		if ( 'span' === strtolower( $item[1] ) && preg_match( '/class=([\'"])current\1/i', $item[2] ) ) {
			$current = (int) $item[3];
		}
	}
	if ( $current < 1 ) {
		return $html;
	}

	$list = '';
	foreach ( $items as $item ) {
		$attrs = $item[2];

		if ( 'a' === strtolower( $item[1] ) ) {
			$page = preg_match( '/data-pageid=([\'"])(\d+)\1/i', $attrs, $match ) ? (int) $match[2] : 0;
			if ( $page > 0 && preg_match( '/class=([\'"])(?:next|prev)\1/i', $attrs ) ) {
				$label  = $page < $current ? __( 'Previous page', 'hyperpress' ) : __( 'Next page', 'hyperpress' );
				$attrs .= ' aria-label="' . esc_attr( $label ) . '"';
			}
			$list .= '<li><a' . $attrs . '>' . $item[3] . '</a></li>';
		} elseif ( preg_match( '/class=([\'"])current\1/i', $attrs ) ) {
			$list .= '<li class="current" aria-current="page"><span class="show-for-sr">' . esc_html__( 'You are on page', 'hyperpress' ) . '</span> ' . $current . '</li>';
		} elseif ( preg_match( '/class=([\'"])ellipsis\1/i', $attrs ) ) {
			$list .= '<li class="ellipsis" aria-hidden="true"></li>';
		} else {
			return $html;
		}
	}

	return '<nav aria-label="' . esc_attr__( 'Pagination', 'hyperpress' ) . '"><ul class="pagination text-center">' . $list . '</ul></nav>';
}

/**
 * Prints the default content of the "Footer socials" widget area: the Customizer socials as an inline list, or nothing
 * when hyperpress-social is not active.
 */
function hyperpress_default_footer_socials(): void {
	if ( ! class_exists( '\HyperPress\Social\Widgets\DisplaySocialWidget' ) ) {
		return;
	}

	the_widget(
		// Widget factory keys have no leading backslash (register_widget( Class::class )).
		'HyperPress\Social\Widgets\DisplaySocialWidget',
		\HyperPress\Social\Widgets\DisplaySocialWidget::default_instance(),
		array(
			'before_widget' => '',
			'after_widget'  => '',
			'before_title'  => '',
			'after_title'   => '',
		)
	);
}
