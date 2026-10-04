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
 *
 **/

// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

use Imagely\NGG\Util\Router as Router;

?>

<?php if ( ! empty ( $album ) ) : ?>
	<div class="album-info callout primary">
		<?php $albumName = $album->name ?>
		<?php if ( ! empty ( $albumName ) ) : ?>
			<h5><?php echo $albumName ?></h5>
		<?php endif; ?>
		<?php $albumDesc = $album->albumdesc ?>
		<?php if ( ! empty ( $albumDesc ) ) : ?>
			<p><?php echo $albumDesc ?></p>
		<?php endif; ?>
	</div>
	<?php if ( ! empty ( $galleries ) ) : ?>
		<div class="gallery-grid grid-x grid-margin-x grid-margin-y align-stretch">
			<?php foreach ( $galleries as $gallery ) : ?>
				<div class="cell card grid-y align-bottom small-12 medium-6 large-4 xlarge-3">
					<div class="cell card-image">
						<?php if ( ! empty( $gallery->previewurl ) ) : ?>
							<a href="<?php echo Router::esc_url( $gallery->pagelink ) ?>">
								<img src="<?php echo Router::esc_url( $gallery->previewurl ); ?>">
							</a>
						<?php else : ?>
							<?php $albumSettings = $album->display_type_settings["photocrati-nextgen_basic_compact_album"]; ?>
							<div
								style="aspect-ratio:<?php echo $albumSettings["thumbnail_width"] / $albumSettings["thumbnail_height"] ?>"></div>
						<?php endif; ?>
					</div>
					<div class="cell card-section flex-child-auto">
						<?php if ( ! empty( $gallery->title ) ) : ?>
							<h4><a href="<?php echo Router::esc_url( $gallery->pagelink ) ?>"><?php echo $gallery->title ?></a></h4>
						<?php endif; ?>
						<p>
							<?php if ( ! empty( $gallery->counter ) ) : ?>
								<span
									class="label primary"><strong><?php echo $gallery->counter; ?></strong>&nbsp;<?php _e( 'Photos', 'nggallery' ); ?></span>
							<?php endif; ?>
							<?php if ( ! empty( $gallery->author ) ) : ?>
								<?php $author = get_userdata( $gallery->author ); ?>
								<?php if ( ! empty( $author ) ) : ?>
									<?php $author_displayname = $author->display_name; ?>
									<?php if ( ! empty( $author_displayname ) ) : ?>
										<span class="label warning"><?php echo $author_displayname; ?></span>
									<?php endif; ?>
								<?php endif; ?>
							<?php endif; ?>
						</p>
						<?php if ( ! empty( $gallery->galdesc ) ) : ?>
							<p><?php echo $gallery->galdesc ?></p>
						<?php endif; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
<?php endif; ?>

