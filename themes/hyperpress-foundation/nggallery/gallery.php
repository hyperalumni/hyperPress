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

?>

<?php if ( ! empty( $gallery ) ) : ?>
	<?php $captions = false; ?>

	<div class="ngg-galleryoverview" id="<?php echo esc_attr( $gallery->anchor ); ?>">
		<?php require __DIR__ . '/../template-parts/nextgen/gallery-grid.php'; ?>
	</div>

<?php endif; ?>
