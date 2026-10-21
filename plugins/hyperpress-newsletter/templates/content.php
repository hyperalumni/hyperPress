<?php
/**
 * Newsletter content template.
 */

// Don't load directly.
defined( 'ABSPATH' ) || exit;

$hyper_season = 'hyper_season';
$seasons      = wp_get_object_terms(
	get_the_ID(),
	$hyper_season,
	array(
		'orderby' => 'name',
		'order'   => 'DESC',
	)
);
$season       = ! is_wp_error( $seasons ) && ! empty( $seasons ) ? $seasons[0] : null;
?>

<header>
	<?php if ( ! is_singular( 'hyper_newsletter' ) ) : ?>
		<h2 class="entry-title">
			<a href="<?php echo esc_url( get_permalink() ); ?>" rel="bookmark">
				<?php if ( ! \HyperPress\Utils\RequestTerm::get( $hyper_season ) instanceof \WP_Term ) : ?>
					<?php echo ! empty( $season ) ? esc_html( $season->name ) . ': ' : ''; ?>
				<?php endif; ?>

				<?php the_title(); ?>
			</a>
		</h2>

	<?php endif; ?>
	<?php do_action( 'hyperpress_labels' ); ?>
</header>
<div class="entry-content">
	<?php
	// Embed only https URLs on canva.com or one of its subdomains; anything else renders nothing.
	$canva_url = get_post_custom_values( 'hyperpress_newsletter_canva_url', get_the_ID() )[0] ?? '';
	if ( ! \HyperPress\Newsletter\MetaBoxes::is_canva_url( $canva_url ) ) {
		$canva_url = '';
	}
	?>
	<?php if ( ! empty( $canva_url ) ) : ?>
		<div style="position: relative; width: 100%; height: 0; padding-top: 129.4118%; padding-bottom: 0; box-shadow: 0 2px 8px 0 rgba(63,69,81,0.16); margin-bottom: 0.9em; overflow: hidden; border-radius: 8px; will-change: transform;">
			<iframe loading="lazy"
					style="position: absolute; width: 100%; height: 100%; top: 0; left: 0; border: none; padding: 0;margin: 0;"
					src="<?php echo esc_url( $canva_url . '?embed' ); ?>" allowfullscreen="allowfullscreen"
					allow="fullscreen"></iframe>
		</div>
		<?php
		$canva_target = \HyperPress\Utils\Link::target( $canva_url );
		$canva_rel    = \HyperPress\Utils\Link::rel( $canva_target );
		?>
		<a href="<?php echo esc_url( $canva_url ); ?>" target="<?php echo esc_attr( $canva_target ); ?>"<?php echo '' !== $canva_rel ? ' rel="' . esc_attr( $canva_rel ) . '"' : ''; ?>>View on Canva</a>
	<?php endif; ?>
</div>
