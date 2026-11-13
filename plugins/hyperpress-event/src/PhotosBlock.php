<?php
namespace HyperPress\Event;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

/**
 * The markup of the photos section. NextGEN's own wrapper ids and classes are left untouched.
 */
final class PhotosBlock {

	public const LIMIT = 6;

	/**
	 * Banner plus the gallery or album, a "View more" link for one big gallery, or the check-back note.
	 *
	 * @param array<int> $gallery_ids Existing selected gallery IDs.
	 * @param bool       $over        Whether the event is over.
	 * @param string     $banner      Section banner HTML.
	 * @param string     $permalink   The event permalink.
	 * @param string     $anchor      Anchor the "View more" link returns to.
	 */
	public static function html( array $gallery_ids, bool $over, string $banner, string $permalink, string $anchor ): string {
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

		return $banner . '<div class="hyperpress-event-photos">' . $body . $more . '</div>';
	}
}
