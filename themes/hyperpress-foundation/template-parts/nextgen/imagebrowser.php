<?php
/**
 * The NextGEN image browser: title, image, previous/next buttons, counter and description.
 * Logic follows NextGEN 4.5.5's src/Legacy/view/imagebrowser.php (identical to
 * imagebrowser-caption.php), shared by both `nggallery/` templates.
 *
 * NextGEN's static/ImageBrowser/imagebrowser.js starts on `div.ngg-imagebrowser` (its `id` and
 * `data-nextgen-gallery-id`); GalleryDisplay/common.js then handles clicks on
 * `a.ngg-browser-prev, a.ngg-browser-next` inside that container and swaps in the page it fetches
 * from their `href`. Those classes, ids and attributes must stay.
 *
 * NextGEN's image browser wraps around (the last image's next is the first), so the previous and
 * next links are practically always present; the disabled placeholders are only a safety net.
 *
 * @var object $image             Image data (anchor, previous_pid, previous_image_link, next_pid,
 *                                next_image_link, number, total, alttext, href_link, description).
 * @var object $displayed_gallery Provides id().
 */

// Don't load directly.
defined( 'ABSPATH' ) || exit;

use Imagely\NGG\Display\I18N;
use Imagely\NGG\Util\Router;

?>

<?php if ( ! empty( $image ) ) : ?>
	<?php
	// href_link is the link NextGEN built (its lightbox hooks live in its own class attribute),
	// so merge the Foundation class into it instead of adding a second class attribute.
	$image_link = new WP_HTML_Tag_Processor( (string) $image->href_link );
	if ( $image_link->next_tag( 'a' ) ) {
		$image_link->add_class( 'thumbnail' );
	}
	$has_previous = ! empty( $image->previous_image_link );
	$has_next     = ! empty( $image->next_image_link );
	?>
	<div class="ngg-imagebrowser" id="<?php echo esc_attr( $image->anchor ); ?>" data-nextgen-gallery-id="<?php echo esc_attr( $displayed_gallery->id() ); ?>">
		<h3><?php echo wp_kses_post( I18N::ngg_decode_sanitized_html_content( $image->alttext ) ); // The decode can restore real tags, so kses runs after it. ?></h3>

		<div class="text-center">
			<?php // kses may strip unusual attributes a custom lightbox adds to href_link (class, data-*, rel and href survive); this is accepted. ?>
			<?php echo wp_kses_post( $image_link->get_updated_html() ); ?>
		</div>

		<div class="button-group expanded">
			<?php if ( $has_previous ) : ?>
				<a class="button hollow ngg-browser-prev" id="ngg-prev-<?php echo esc_attr( $image->previous_pid ); ?>" href="<?php echo Router::esc_url( $image->previous_image_link ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by NextGen's Router::esc_url() ?>">&#9668; <?php esc_html_e( 'Previous', 'hyperpress' ); ?></a>
			<?php else : ?>
				<span class="button hollow disabled" aria-disabled="true">&#9668; <?php esc_html_e( 'Previous', 'hyperpress' ); ?></span>
			<?php endif; ?>
			<?php if ( $has_next ) : ?>
				<a class="button hollow ngg-browser-next" id="ngg-next-<?php echo esc_attr( $image->next_pid ); ?>" href="<?php echo Router::esc_url( $image->next_image_link ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by NextGen's Router::esc_url() ?>"><?php esc_html_e( 'Next', 'hyperpress' ); ?> &#9658;</a>
			<?php else : ?>
				<span class="button hollow disabled" aria-disabled="true"><?php esc_html_e( 'Next', 'hyperpress' ); ?> &#9658;</span>
			<?php endif; ?>
		</div>

		<p class="text-center">
			<?php
			/* translators: 1: number of the current image, 2: total number of images. */
			echo esc_html( sprintf( __( 'Image %1$d of %2$d', 'hyperpress' ), (int) $image->number, (int) $image->total ) );
			?>
		</p>

		<?php if ( ! empty( $image->description ) ) : ?>
			<div class="callout secondary">
				<?php echo wp_kses_post( I18N::ngg_decode_sanitized_html_content( $image->description ) ); ?>
			</div>
		<?php endif; ?>
	</div>
<?php endif; ?>
