<?php
/**
 * The inside of the NextGEN gallery overview: description callout, slideshow link, thumbnail grid,
 * hidden lightbox anchors and pagination. Shared by the `nggallery/` gallery templates, which
 * provide the `ngg-galleryoverview` wrapper.
 *
 * @var object $gallery    Gallery data (anchor, columns, description, title, slideshow link settings).
 * @var array  $images     Image objects (pid, imageURL, thumbnailURL, thumbcode, size, hidden, ...).
 * @var string $pagination NextGEN's pagination HTML.
 * @var bool   $captions   Optional, defaults to false. Declared for the captioned grid; this partial
 *                         does not render captions yet.
 */

// Don't load directly.
defined( 'ABSPATH' ) || exit;

use Imagely\NGG\Display\GalleryImage;
use Imagely\NGG\Display\I18N;
use Imagely\NGG\Util\Router;

$captions ??= false;

$columns = max( 0, min( 8, (int) $gallery->columns ) );
$up      = $columns > 0 ? array( min( 2, $columns ), min( 3, $columns ), $columns ) : array( 2, 3, 4 );
$extra   = 0 === $columns ? ' xxlarge-up-5' : ''; // Automatic columns gain a fifth on extra-wide screens; an explicit count wins.
$hidden  = array_values( array_filter( (array) $images, static fn( $image ) => ! empty( $image->hidden ) ) );
$visible = array_values( array_filter( (array) $images, static fn( $image ) => empty( $image->hidden ) ) );
?>

		<?php if ( ! empty( $gallery->description ) ) : ?>
			<?php hyperpress_nextgen_take_over_description( (string) $gallery->description, 'gallery-info callout' ); // NextGEN would prepend the same description as a plain paragraph. ?>
			<div class="gallery-info callout primary">
				<?php if ( ! empty( $gallery->title ) ) : ?>
					<h5><?php echo esc_html( $gallery->title ); ?></h5>
				<?php endif; ?>
				<div><?php echo wp_kses_post( $gallery->description ); ?></div>
			</div>
		<?php endif; ?>

		<?php if ( $gallery->show_slideshow ) : ?>
			<p class="text-center"><a class="button small hollow" href="<?php echo Router::esc_url( $gallery->slideshow_link ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by NextGen's Router::esc_url() ?>"><?php echo esc_html( $gallery->slideshow_link_text ); ?></a></p>
		<?php endif; ?>

		<?php if ( empty( $images ) ) : ?>
			<div class="callout secondary"><?php esc_html_e( 'There are no photos in this gallery.', 'hyperpress' ); ?></div>
		<?php elseif ( ! empty( $visible ) ) : ?>
		<div class="grid-x grid-margin-x small-up-<?php echo (int) $up[0]; ?> medium-up-<?php echo (int) $up[1]; ?> large-up-<?php echo (int) $up[2]; ?><?php echo esc_attr( $extra ); ?>">
			<?php foreach ( $visible as $image ) : ?>
				<?php
				// NextGEN's thumbcode carries its own class attribute (the lightbox hook), so a second
				// class attribute would be dropped by the browser: merge the Foundation class instead.
				$anchor = new WP_HTML_Tag_Processor(
					'<a href="' . Router::esc_url( $image->imageURL ) . '" title="' . esc_attr( $image->description ) . '" ' . $image->thumbcode . '>' // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- href escaped by NextGen's Router::esc_url(); property names are defined by NextGen
				);
				if ( $anchor->next_tag( 'a' ) ) {
					$anchor->add_class( 'thumbnail' );
				}
				?>
				<div class="cell" id="ngg-image-<?php echo (int) $image->pid; ?>">
					<?php echo $anchor->get_updated_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- href and title are escaped above; thumbcode is raw attribute HTML built by NextGen (lightbox class and data-* attributes) ?>

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
