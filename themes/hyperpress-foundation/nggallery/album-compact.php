<?php
/**
 * Template for Compact Album displays (Foundation 6 cards).
 * Logic follows NextGEN 4.5.5's src/Legacy/view/album-compact.php.
 *
 * @var object|null $album                    The album (name, albumdesc); not set for every source.
 * @var array       $galleries                The galleries inside the album.
 * @var string      $pagination               NextGEN's pagination HTML.
 * @var array       $image_gen_params         Expected preview size (width, height).
 * @var int|bool    $open_gallery_in_lightbox Display setting: open a gallery's preview in the lightbox.
 * @var int|bool    $enable_descriptions      Display setting: show descriptions (the album header); may be unset.
 * @var array       $original_settings        The album's own display settings in drill-down views; may be unset.
 */

// Don't load directly.
defined( 'ABSPATH' ) || exit;

use Imagely\NGG\Util\Router;

// NextGEN's check: the display setting, or the album's own settings kept under original_settings in drill-down views.
$descriptions_enabled = ! empty( $enable_descriptions ) || ! empty( $original_settings['enable_descriptions'] );

?>

<?php if ( ! empty( $album ) && $descriptions_enabled ) : ?>
	<?php hyperpress_nextgen_take_over_description( (string) ( $album->albumdesc ?? '' ), 'album-info callout' ); // NextGEN would prepend the same description as a plain paragraph. ?>
	<div class="album-info callout primary">
		<?php if ( ! empty( $album->name ) ) : ?>
			<h5><?php echo esc_html( $album->name ); ?></h5>
		<?php endif; ?>
		<?php if ( ! empty( $album->albumdesc ) ) : ?>
			<div><?php echo wp_kses_post( $album->albumdesc ); ?></div>
		<?php endif; ?>
	</div>
<?php endif; ?>

<?php if ( ! empty( $galleries ) ) : ?>
	<div class="gallery-grid grid-x grid-margin-x small-up-1 medium-up-2 large-up-3 xlarge-up-4">
		<?php foreach ( $galleries as $gallery ) : ?>
			<?php
			$author_name = '';
			if ( ! empty( $gallery->author ) ) {
				$author      = get_userdata( $gallery->author );
				$author_name = $author ? (string) $author->display_name : '';
			}
			$use_lightbox = ! empty( $open_gallery_in_lightbox )
				&& 'gallery' === ( $gallery->entity_type ?? '' )
				&& ! empty( $gallery->previewpic_fullsized_url )
				&& ! empty( $gallery->displayed_gallery );
			?>
			<div class="cell grid-y">
				<div class="card flex-child-auto">
					<?php if ( ! empty( $gallery->previewurl ) ) : ?>
						<?php if ( $use_lightbox ) : ?>
							<a <?php echo $gallery->displayed_gallery->effect_code ?? ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- lightbox attributes built by NextGen ?>
								href="<?php echo esc_attr( $gallery->previewpic_fullsized_url ); ?>"
								data-fullsize="<?php echo esc_attr( $gallery->previewpic_fullsized_display_url ?? '' ); ?>"
								data-src="<?php echo esc_attr( $gallery->previewpic_fullsized_display_url ?? '' ); ?>"
								data-thumbnail="<?php echo esc_attr( $gallery->previewurl ); ?>"
								data-title="<?php echo esc_attr( $gallery->previewpic_image->alttext ?? '' ); ?>"
								data-description="<?php echo esc_attr( stripslashes( $gallery->previewpic_image->description ?? '' ) ); ?>"
								data-image-id="<?php echo esc_attr( $gallery->previewpic ?? '' ); ?>">
						<?php else : ?>
							<a href="<?php echo Router::esc_url( $gallery->pagelink ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by NextGen's Router::esc_url() ?>">
						<?php endif; ?>
							<img src="<?php echo Router::esc_url( $gallery->previewurl ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by NextGen's Router::esc_url() ?>" alt="<?php echo esc_attr( $gallery->title ?? '' ); ?>" loading="lazy">
						</a>
					<?php else : ?>
						<div style="aspect-ratio:<?php echo esc_attr( (string) hyperpress_nextgen_ratio( $image_gen_params['width'] ?? null, $image_gen_params['height'] ?? null ) ); ?>"></div>
					<?php endif; ?>
					<div class="card-section flex-child-auto">
						<?php if ( ! empty( $gallery->title ) ) : ?>
							<h4><a href="<?php echo Router::esc_url( $gallery->pagelink ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by NextGen's Router::esc_url() ?>"><?php echo esc_html( $gallery->title ); ?></a></h4>
						<?php endif; ?>
						<p>
							<?php if ( ! empty( $gallery->counter ) ) : ?>
								<span class="label primary"><strong><?php echo (int) $gallery->counter; ?></strong>&nbsp;<?php esc_html_e( 'Photos', 'nggallery' ); // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch -- 'nggallery' is NextGen's own text domain, reused on purpose ?></span>
							<?php endif; ?>
							<?php if ( '' !== $author_name ) : ?>
								<span class="label warning"><?php echo esc_html( $author_name ); ?></span>
							<?php endif; ?>
						</p>
						<?php if ( ! empty( $gallery->galdesc ) ) : ?>
							<div><?php echo wp_kses_post( $gallery->galdesc ); ?></div>
						<?php endif; ?>
					</div>
				</div>
			</div>
		<?php endforeach; ?>
	</div>

	<?php echo wp_kses_post( hyperpress_nextgen_pagination( (string) ( $pagination ?? '' ) ) ); ?>
<?php elseif ( ! empty( $album ) ) : ?>
	<div class="callout secondary"><?php esc_html_e( 'There are no galleries in this album.', 'hyperpress' ); ?></div>
<?php endif; ?>
