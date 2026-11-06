<?php
/**
 * Template for the NextGEN gallery carousel (Foundation 6): the current image large, previous and
 * next image buttons, and a thumbnail strip. Navigation is ordinary page loads through each image's
 * `pidlink`, as in NextGEN 4.5.5's src/Legacy/view/gallery-carousel.php.
 *
 * Deliberate differences from the stock template:
 * - `$prev` and `$next` are not used. They link to thumbnail pages, not images, and NextGEN fills
 *   them wrongly (`$prev` is always false, and on pages after the first `$next` is the previous
 *   page). The buttons are computed from the order of `$images`, the visible images of the current
 *   page only (24 by default), so they stop at the page edges; the pagination moves between pages.
 * - The wrapper has no `id` and my buttons do not use the `prev`, `next` or `page-numbers` classes.
 *   NextGEN's AJAX pagination script finds the gallery by its id, and without one it throws before
 *   preventDefault, so the browser loads the page normally. That is the intended behaviour here.
 * - The pagination, which the stock template does not print, follows the strip.
 *
 * @var object      $gallery    Gallery data (only checked for being set).
 * @var array       $images     Image objects (pid, pidlink, description, alttext, thumbnailURL, size, hidden).
 * @var object|null $current    The selected image (pid, alttext, url).
 * @var string      $pagination NextGEN's pagination HTML.
 */

// Don't load directly.
defined( 'ABSPATH' ) || exit;

use Imagely\NGG\Display\GalleryImage;
use Imagely\NGG\Display\I18N;
use Imagely\NGG\Util\Router;

$current = $current ?? null;
$visible = array_values( array_filter( (array) ( $images ?? array() ), static fn( $image ) => empty( $image->hidden ) ) );

// Neighbours of the current image among the visible images of this page, by pid.
$position = false;
if ( $current ) {
	foreach ( $visible as $index => $image ) {
		if ( (string) $image->pid === (string) $current->pid ) {
			$position = $index;
			break;
		}
	}
}
$previous  = false !== $position ? ( $visible[ $position - 1 ] ?? null ) : null;
$following = false !== $position ? ( $visible[ $position + 1 ] ?? null ) : null;

$current_image = null;
if ( $current ) {
	// attributes() carries its own class attribute, so merge the Foundation class instead of adding a second one.
	$current_alt   = I18N::ngg_plain_text_alt_title_attributes( $current->alttext ?? '' );
	$current_image = new WP_HTML_Tag_Processor(
		'<img' . GalleryImage::attributes() . ' src="' . Router::esc_url( $current->url ) . '" alt="' . esc_attr( $current_alt ) . '" title="' . esc_attr( $current_alt ) . '">' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- attributes() escapes each attribute with esc_attr(); src escaped by NextGen's Router::esc_url()
	);
	if ( $current_image->next_tag( 'img' ) ) {
		$current_image->add_class( 'thumbnail' );
	}
}
?>

<?php if ( ! empty( $gallery ) && ( $current_image || ! empty( $visible ) ) ) : ?>
	<div class="ngg-galleryoverview">
		<?php if ( $current_image ) : ?>
			<div class="text-center">
				<?php echo $current_image->get_updated_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- every attribute is escaped above ?>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $visible ) ) : ?>
			<div class="button-group expanded">
				<?php if ( $previous ) : ?>
					<a class="button hollow" href="<?php echo Router::esc_url( $previous->pidlink ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by NextGen's Router::esc_url() ?>">&#9668; <?php esc_html_e( 'Previous image', 'hyperpress' ); ?></a>
				<?php else : ?>
					<span class="button hollow disabled" aria-disabled="true">&#9668; <?php esc_html_e( 'Previous image', 'hyperpress' ); ?></span>
				<?php endif; ?>
				<?php if ( $following ) : ?>
					<a class="button hollow" href="<?php echo Router::esc_url( $following->pidlink ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by NextGen's Router::esc_url() ?>"><?php esc_html_e( 'Next image', 'hyperpress' ); ?> &#9658;</a>
				<?php else : ?>
					<span class="button hollow disabled" aria-disabled="true"><?php esc_html_e( 'Next image', 'hyperpress' ); ?> &#9658;</span>
				<?php endif; ?>
			</div>

			<?php // Both breakpoints carry a column count: Foundation recomputes a cell's width only for those, and the gutter grows at medium. ?>
			<div class="grid-x grid-margin-x small-up-4 medium-up-6">
				<?php foreach ( $visible as $index => $image ) : ?>
					<?php $is_current = $index === $position; ?>
					<div class="cell">
						<a class="thumbnail<?php echo $is_current ? ' is-current' : ''; ?>" href="<?php echo Router::esc_url( $image->pidlink ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by NextGen's Router::esc_url() ?>" title="<?php echo esc_attr( $image->description ); ?>"<?php echo $is_current ? ' aria-current="true"' : ''; ?>>
							<img<?php echo GalleryImage::attributes( array( 'loading' => 'lazy' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- attributes() escapes each attribute with esc_attr() ?>
								src="<?php echo Router::esc_url( $image->thumbnailURL ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped,WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- escaped by NextGen's Router::esc_url(); property names are defined by NextGen ?>"
								alt="<?php echo esc_attr( I18N::ngg_plain_text_alt_title_attributes( $image->alttext ) ); ?>"
								<?php echo $image->size; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- width/height attributes built by NextGen ?>>
						</a>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<?php echo wp_kses_post( hyperpress_nextgen_pagination( (string) ( $pagination ?? '' ) ) ); ?>
	</div>
<?php endif; ?>
