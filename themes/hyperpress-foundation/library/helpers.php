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

/**
 * Stops NextGEN from printing its own plain description above a legacy template.
 *
 * When a gallery or album is drilled into, NextGEN registers `add_description_to_legacy_templates()`
 * on `ngg_displayed_gallery_rendering` (priority 8). That filter runs after the template has been
 * rendered and prepends a bare `<p>` with the description to the template's output. A template that
 * prints the description in its own callout calls this while it renders, so the description is not
 * shown twice. The callback is an array callback on a controller object we cannot reach, so it is
 * found by its method name. Only that method at priority 8 is removed; the breadcrumbs filter
 * (priority 9) and every other callback stay.
 *
 * In a sub-album view NextGEN does not use that filter: `index_action()` prepends the description
 * to the rendered template directly, so nothing can be unhooked. When `$description` and `$marker`
 * are both given, a one-time filter at priority 20 (after NextGEN's 8 and 9) removes the `<p>`
 * paragraphs in front of the template's own callout (the part before the tag that holds `$marker`)
 * whose text equals `$description` exactly (tags stripped, entities decoded, whitespace collapsed).
 * The filter removes itself the first time it runs, so it never touches another display.
 *
 * @param string $description The description the template prints itself.
 * @param string $marker      A class fragment of the template's callout, e.g. `album-info callout`.
 * @return void
 */
function hyperpress_nextgen_take_over_description( string $description = '', string $marker = '' ): void {
	$hook = 'ngg_displayed_gallery_rendering';

	// Read-only look at the global filter table; there is no API to list a hook's callbacks.
	$wp_filter = $GLOBALS['wp_filter'][ $hook ] ?? null;
	if ( $wp_filter instanceof WP_Hook && ! empty( $wp_filter->callbacks[8] ) ) {
		// Iterate over a copy: remove_filter() changes the callbacks array.
		foreach ( $wp_filter->callbacks[8] as $entry ) {
			$callback = $entry['function'] ?? null;
			if ( is_array( $callback ) && 2 === count( $callback ) && 'add_description_to_legacy_templates' === $callback[1] ) {
				remove_filter( $hook, $callback, 8 );
			}
		}
	}

	$wanted = hyperpress_nextgen_normalize_text( $description );
	if ( '' === $wanted || '' === $marker ) {
		return;
	}

	$cleanup = null;
	$cleanup = static function ( $html ) use ( &$cleanup, $hook, $wanted, $marker ) {
		// One-time: never affect another display on the page.
		remove_filter( $hook, $cleanup, 20 );

		if ( ! is_string( $html ) ) {
			return $html;
		}

		$position = strpos( $html, $marker );
		if ( false === $position ) {
			return $html;
		}

		// Cut at the opening tag that holds the marker; only what comes before it is looked at.
		$cut = strrpos( substr( $html, 0, $position ), '<' );
		$cut = false === $cut ? $position : $cut;

		$prefix = preg_replace_callback(
			'~<p\b[^>]*>.*?</p>~is',
			static fn( array $paragraph ): string => hyperpress_nextgen_normalize_text( $paragraph[0] ) === $wanted ? '' : $paragraph[0],
			substr( $html, 0, $cut )
		);

		return ( is_string( $prefix ) ? $prefix : substr( $html, 0, $cut ) ) . substr( $html, $cut );
	};

	add_filter( $hook, $cleanup, 20 );
}

/**
 * Normalises HTML or text for an exact comparison: strips tags, decodes entities, collapses
 * every whitespace run (including non-breaking spaces) to one space and trims.
 *
 * @param string $text HTML or plain text.
 * @return string
 */
function hyperpress_nextgen_normalize_text( string $text ): string {
	$text = html_entity_decode( wp_strip_all_tags( $text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$text = preg_replace( '/[\s\x{00A0}]+/u', ' ', $text );

	return trim( is_string( $text ) ? $text : '' );
}
