<?php
namespace HyperPress\Event;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

/**
 * The markup of the photos section. NextGEN's own wrapper ids and classes are left untouched.
 */
final class PhotosBlock {

	/** Images in the teaser: one row of the theme's five-column gallery grid; "View more" needs more than this. */
	public const LIMIT = 5;

	/** Handle of the inline style that hides NextGEN's page numbers inside the teaser. */
	public const STYLE = 'hyperpress-event-photos';

	/**
	 * Banner plus the gallery or album, a "View more" link for one big gallery, or the check-back note.
	 *
	 * @param array<int> $gallery_ids Existing selected gallery IDs.
	 * @param bool       $over        Whether the event is over.
	 * @param string     $banner      Section banner HTML.
	 * @param string     $permalink   The event permalink.
	 * @param string     $anchor      Anchor the "View more" link returns to.
	 * @param bool       $teaser      Whether this is NextGEN's first page: the page numbers are then hidden.
	 */
	public static function html( array $gallery_ids, bool $over, string $banner, string $permalink, string $anchor, bool $teaser = false ): string {
		if ( array() === $gallery_ids ) {
			if ( $over ) {
				return '';
			}

			return $banner . '<p class="hyperpress-event-photos-empty">' . esc_html__( 'Check back after the event.', 'hyperpress' ) . '</p>';
		}

		$count = count( $gallery_ids );
		$body  = 1 === $count
			? do_shortcode( NextGenShortcodes::single( $gallery_ids[0], self::LIMIT ) )
			: do_shortcode( NextGenShortcodes::album( $gallery_ids ) );

		$more = '';
		if ( 1 === $count && Galleries::image_count( $gallery_ids[0] ) > self::LIMIT ) {
			$url   = NextGenShortcodes::more_url( $permalink, 2 ) . ( '' !== $anchor ? '#' . $anchor : '' );
			$more  = '<p class="hyperpress-event-photos-more"><a class="button" href="' . esc_url( $url ) . '">';
			$more .= esc_html__( 'View more', 'hyperpress' ) . '</a></p>';
		}

		$class = 'hyperpress-event-photos';
		if ( $teaser ) {
			$class .= ' hyperpress-event-photos--teaser';
			self::enqueue_style();
		}

		return $banner . '<div class="' . $class . '">' . $body . $more . '</div>';
	}

	/**
	 * Hides NextGEN's page numbers inside the teaser ("View more" leads to the pages). The theme's reshaped
	 * pagination is a `nav` around `ul.pagination`; NextGEN's default markup is `.ngg-navigation`. Registered with
	 * no source, so only the inline rule is printed, and only when the block renders.
	 */
	private static function enqueue_style(): void {
		$base = '.hyperpress-event-photos--teaser';
		if ( ! wp_style_is( self::STYLE, 'registered' ) ) {
			wp_register_style( self::STYLE, false, array(), null ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- No source, so no version.
			wp_add_inline_style( self::STYLE, "$base .ngg-navigation, $base nav:has(> .pagination), $base .pagination { display: none; }" );
		}
		wp_enqueue_style( self::STYLE );
	}
}
