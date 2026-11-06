<?php
/**
 * Template for NextGEN's random/recent image widget (compact Foundation 6 grid).
 *
 * NextGEN's widget asks for `Widget/Display/Gallery` and finds this file in the theme's `nggallery/`
 * folder before its own `templates/Widget/Display/Gallery.php`.
 *
 * @var object $gallery Gallery data; `displayed_gallery` holds the widget's settings.
 * @var array  $images  Image objects (pid, description, alttext, meta_data, hidden, ...).
 */

// Don't load directly.
defined( 'ABSPATH' ) || exit;

use Imagely\NGG\DataStorage\Manager;
use Imagely\NGG\Display\GalleryImage;
use Imagely\NGG\Display\I18N;
use Imagely\NGG\DisplayType\Controller;

$visible = array_values( array_filter( (array) $images, static fn( $image ) => empty( $image->hidden ) ) );

// No images: print nothing, not even the widget's wrapper and title.
if ( empty( $visible ) ) {
	return;
}

$storage     = Manager::get_instance();
$effect_code = ( new Controller() )->get_effect_code( $gallery->displayed_gallery );
$settings    = $gallery->displayed_gallery->get_entity()->display_settings;
$data_keys   = array(
	'data-tiktok-play-url'  => 'imagely_tiktok_play_url',
	'data-tiktok-share-url' => 'imagely_tiktok_share_url',
	'data-tiktok-embed-url' => 'imagely_tiktok_embed_link',
	'data-video-url'        => 'video_link',
);

echo wp_kses_post( $settings['widget_setting_before_widget'] ?? '' )
	. wp_kses_post( $settings['widget_setting_before_title'] ?? '' )
	. wp_kses_post( $settings['widget_setting_title'] ?? '' )
	. wp_kses_post( $settings['widget_setting_after_title'] ?? '' );
?>
<?php // Columns: change both numbers to tune the grid. Foundation recomputes a cell's width per breakpoint only for the breakpoints that have a -up- class, and the gutter grows at medium, so keep medium-up-N equal to small-up-N or the cells overflow and stack. ?>
<div class="grid-x grid-margin-x small-up-2 medium-up-2">
	<?php foreach ( $visible as $image ) : ?>
		<?php
		$meta        = (array) ( $image->meta_data ?? array() );
		$anchor_html = '<a href="' . esc_url( $storage->get_image_url( $image, 'full', true ) ) . '"'
			. ' title="' . esc_attr( $image->description ) . '"'
			. ' data-image-id="' . esc_attr( $image->pid ) . '"';
		foreach ( $data_keys as $attribute => $meta_key ) {
			if ( ! empty( $meta[ $meta_key ] ) ) {
				$anchor_html .= ' ' . $attribute . '="' . esc_attr( $meta[ $meta_key ] ) . '"';
			}
		}
		// The effect code carries its own class attribute (the lightbox hook), so a second class
		// attribute would be dropped by the browser: merge the Foundation class instead.
		$anchor = new WP_HTML_Tag_Processor( $anchor_html . ' ' . $effect_code . '>' );
		if ( $anchor->next_tag( 'a' ) ) {
			$anchor->add_class( 'thumbnail' );
		}
		$alt = I18N::ngg_plain_text_alt_title_attributes( $image->alttext );
		?>
		<div class="cell">
			<?php echo $anchor->get_updated_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- href, title and data-* are escaped above; the effect code is raw attribute HTML built by NextGen (lightbox class and attributes) ?>

				<img<?php echo GalleryImage::attributes( array( 'loading' => 'lazy' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- attributes() escapes each attribute with esc_attr() ?>
					title="<?php echo esc_attr( $alt ); ?>"
					alt="<?php echo esc_attr( $alt ); ?>"
					src="<?php echo esc_url( $storage->get_cache_busted_image_url( $image, $settings['image_type'] ) ); ?>"
					width="<?php echo esc_attr( $settings['image_width'] ); ?>"
					height="<?php echo esc_attr( $settings['image_height'] ); ?>">
			</a>
		</div>
	<?php endforeach; ?>
</div>

<?php echo wp_kses_post( $settings['widget_setting_after_widget'] ?? '' ); ?>
