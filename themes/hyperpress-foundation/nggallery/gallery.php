<?php
/**
 * Template for the NextGEN gallery overview (Foundation 6 grid).
 *
 * The outer wrapper keeps NextGEN's `ngg-galleryoverview` class and the anchor id:
 * NextGEN's AJAX pagination script finds and replaces the gallery through them.
 *
 * @var object $gallery    Gallery data (anchor, columns, slideshow link settings).
 * @var array  $images     Image objects (pid, imageURL, thumbnailURL, thumbcode, size, hidden, ...).
 * @var string $pagination NextGEN's pagination HTML.
 */

// Don't load directly.
defined( 'ABSPATH' ) || exit;

use Imagely\NGG\Display\GalleryImage;
use Imagely\NGG\Display\I18N;
use Imagely\NGG\Util\Router;

?>

<?php if ( ! empty( $gallery ) ) : ?>
	<?php
	$columns = max( 0, min( 8, (int) $gallery->columns ) );
	$up      = $columns > 0 ? array( min( 2, $columns ), min( 3, $columns ), $columns ) : array( 2, 3, 4 );
	$hidden  = array();
	?>

	<div class="ngg-galleryoverview" id="<?php echo esc_attr( $gallery->anchor ); ?>">

		<?php if ( ! empty( $gallery->description ) ) : ?>
			<div class="gallery-info callout primary">
				<?php if ( ! empty( $gallery->title ) ) : ?>
					<h5><?php echo esc_html( $gallery->title ); ?></h5>
				<?php endif; ?>
				<p><?php echo wp_kses_post( $gallery->description ); ?></p>
			</div>
		<?php endif; ?>

		<?php if ( $gallery->show_slideshow ) : ?>
			<p class="text-center"><a class="button small hollow" href="<?php echo Router::esc_url( $gallery->slideshow_link ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by NextGen's Router::esc_url() ?>"><?php echo esc_html( $gallery->slideshow_link_text ); ?></a></p>
		<?php endif; ?>

		<?php if ( empty( $images ) ) : ?>
			<div class="callout secondary"><?php esc_html_e( 'There are no photos in this gallery.', 'hyperpress' ); ?></div>
		<?php else : ?>
		<div class="grid-x grid-margin-x small-up-<?php echo (int) $up[0]; ?> medium-up-<?php echo (int) $up[1]; ?> large-up-<?php echo (int) $up[2]; ?>">
			<?php foreach ( $images as $image ) : ?>
				<?php
				if ( $image->hidden ) {
					$hidden[] = $image;
					continue;
				}
				?>
				<div class="cell" id="ngg-image-<?php echo (int) $image->pid; ?>">
					<a class="thumbnail" href="<?php echo Router::esc_url( $image->imageURL ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped,WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- escaped by NextGen's Router::esc_url(); property names are defined by NextGen ?>"
						title="<?php echo esc_attr( $image->description ); ?>"
						<?php echo $image->thumbcode; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- lightbox attributes built by NextGen ?>>
						<img<?php echo GalleryImage::attributes( array( 'loading' => 'lazy' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- attributes() escapes each attribute with esc_attr() ?>
							src="<?php echo Router::esc_url( $image->thumbnailURL ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped,WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- escaped by NextGen's Router::esc_url(); property names are defined by NextGen ?>"
							alt="<?php echo esc_attr( I18N::ngg_plain_text_alt_title_attributes( $image->alttext ) ); ?>"
							<?php echo $image->size; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- width/height attributes built by NextGen ?>>
					</a>
				</div>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>

		<?php if ( ! empty( $hidden ) ) : ?>
			<div hidden>
				<?php foreach ( $hidden as $image ) : ?>
					<a id="ngg-image-<?php echo (int) $image->pid; ?>"
						href="<?php echo Router::esc_url( $image->imageURL ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped,WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- escaped by NextGen's Router::esc_url(); property names are defined by NextGen ?>"
						title="<?php echo esc_attr( $image->description ); ?>"
						<?php echo $image->thumbcode; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- lightbox attributes built by NextGen ?>></a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<?php echo wp_kses_post( hyperpress_nextgen_pagination( (string) $pagination ) ); ?>

	</div>

<?php endif; ?>
