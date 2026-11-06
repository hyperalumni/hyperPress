<?php
/**
 * Template for Extended Album displays (Foundation 6 media objects).
 * Logic follows NextGEN 4.5.5's src/Legacy/view/album-extend.php.
 *
 * @var array  $galleries  The galleries inside the album.
 * @var string $pagination NextGEN's pagination HTML.
 */

// Don't load directly.
defined( 'ABSPATH' ) || exit;

use Imagely\NGG\Util\Router;

?>

<?php if ( ! empty( $galleries ) ) : ?>
	<div class="album-extend">
		<?php foreach ( $galleries as $gallery ) : ?>
			<div class="media-object stack-for-small">
				<?php if ( ! empty( $gallery->previewurl ) ) : ?>
					<div class="media-object-section shrink">
						<a href="<?php echo Router::esc_url( $gallery->pagelink ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by NextGen's Router::esc_url() ?>">
							<img src="<?php echo Router::esc_url( $gallery->previewurl ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by NextGen's Router::esc_url() ?>" alt="<?php echo esc_attr( $gallery->title ?? '' ); ?>" loading="lazy">
						</a>
					</div>
				<?php endif; ?>
				<div class="media-object-section main-section">
					<h4><a href="<?php echo Router::esc_url( $gallery->pagelink ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by NextGen's Router::esc_url() ?>"><?php echo esc_html( $gallery->title ?? '' ); ?></a></h4>
					<?php if ( ! empty( $gallery->galdesc ) ) : ?>
						<p><?php echo wp_kses_post( $gallery->galdesc ); ?></p>
					<?php endif; ?>
					<?php if ( ! empty( $gallery->counter ) && $gallery->counter > 0 ) : ?>
						<span class="label primary"><strong><?php echo (int) $gallery->counter; ?></strong>&nbsp;<?php esc_html_e( 'Photos', 'nggallery' ); // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch -- 'nggallery' is NextGen's own text domain, reused on purpose ?></span>
					<?php endif; ?>
				</div>
			</div>
		<?php endforeach; ?>

		<?php echo wp_kses_post( hyperpress_nextgen_pagination( (string) ( $pagination ?? '' ) ) ); ?>
	</div>
<?php endif; ?>
