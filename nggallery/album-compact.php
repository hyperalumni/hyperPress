<?php
/**
 * Template for Compact Album displays.
 *
 * @var array $album
 * @var array $galleries
 * @var string $pagination
 *
 * Follow variables are usable:
 * $album : Contain information about the first album : var_dump($album);
 * $albums : Contain information about all albums : var_dump($albums);
 * $galleries : Contain all galleries inside this album : var_dump($galleries);
 * $pagination : Contain the pagination content : var_dump($pagination);
 **/

// Don't load directly.
defined( 'ABSPATH' ) || exit;

use Imagely\NGG\Util\Router;

?>

<?php if ( ! empty( $album ) ) : ?>
	<div class="album-info callout primary">
		<?php $album_name = $album->name; ?>
		<?php if ( ! empty( $album_name ) ) : ?>
			<h5><?php echo esc_html( $album_name ); ?></h5>
		<?php endif; ?>
		<?php $album_desc = $album->albumdesc; ?>
		<?php if ( ! empty( $album_desc ) ) : ?>
			<p><?php echo wp_kses_post( $album_desc ); ?></p>
		<?php endif; ?>
	</div>
	<?php if ( ! empty( $galleries ) ) : ?>
		<div class="gallery-grid grid-x grid-margin-x grid-margin-y align-stretch">
			<?php foreach ( $galleries as $gallery ) : ?>
				<div class="cell card grid-y align-bottom small-12 medium-6 large-4 xlarge-3">
					<div class="cell card-image">
						<?php if ( ! empty( $gallery->previewurl ) ) : ?>
							<a href="<?php echo Router::esc_url( $gallery->pagelink ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by NextGen's Router::esc_url() ?>">
								<img src="<?php echo Router::esc_url( $gallery->previewurl ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by NextGen's Router::esc_url() ?>">
							</a>
						<?php else : ?>
							<?php $album_settings = $album->display_type_settings['photocrati-nextgen_basic_compact_album'] ?? array(); ?>
							<div
								style="aspect-ratio:<?php echo esc_attr( (string) hyperpress_nextgen_ratio( $album_settings['thumbnail_width'] ?? null, $album_settings['thumbnail_height'] ?? null ) ); ?>"></div>
						<?php endif; ?>
					</div>
					<div class="cell card-section flex-child-auto">
						<?php if ( ! empty( $gallery->title ) ) : ?>
							<h4><a href="<?php echo Router::esc_url( $gallery->pagelink ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by NextGen's Router::esc_url() ?>"><?php echo esc_html( $gallery->title ); ?></a></h4>
						<?php endif; ?>
						<p>
							<?php if ( ! empty( $gallery->counter ) ) : ?>
								<span
									class="label primary"><strong><?php echo (int) $gallery->counter; // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch -- 'nggallery' is NextGen's own text domain, reused on purpose ?></strong>&nbsp;<?php esc_html_e( 'Photos', 'nggallery' ); ?></span>
							<?php endif; ?>
							<?php if ( ! empty( $gallery->author ) ) : ?>
								<?php $author = get_userdata( $gallery->author ); ?>
								<?php if ( ! empty( $author ) ) : ?>
									<?php $author_displayname = $author->display_name; ?>
									<?php if ( ! empty( $author_displayname ) ) : ?>
										<span class="label warning"><?php echo esc_html( $author_displayname ); ?></span>
									<?php endif; ?>
								<?php endif; ?>
							<?php endif; ?>
						</p>
						<?php if ( ! empty( $gallery->galdesc ) ) : ?>
							<p><?php echo wp_kses_post( $gallery->galdesc ); ?></p>
						<?php endif; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
<?php endif; ?>
