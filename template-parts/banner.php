<?php
// Don't load directly.
defined( 'ABSPATH' ) || exit;

$banner = apply_filters(
    'hyperpress_banner_content',
    array(
		'type'            => '',
		'title'           => '',
		'subtitle'        => '',
		'backgroundColor' => '',
		'buttonText'      => '',
		'buttonLink'      => '',
	)
    );
// set a default value if the filter provided no value
if ( empty( $banner['type'] ) ) {
	$banner['type'] = 'unset';
}
if ( empty( $banner['title'] ) ) {
	$banner['title'] = get_the_title();
}
if ( empty( $banner['subtitle'] ) ) {
	$banner['subtitle'] = get_theme_mod( 'hyperpress_homepage_customize_banner_subtitle' );
}

$banner['type']            = sanitize_text_field( $banner['type'] );
$banner['title']           = sanitize_text_field( $banner['title'] );
$banner['subtitle']        = sanitize_text_field( $banner['subtitle'] );
$banner['backgroundColor'] = sanitize_hex_color( $banner['backgroundColor'] );
$banner['buttonText']      = sanitize_text_field( $banner['buttonText'] );
$banner['buttonLink']      = sanitize_url( $banner['buttonLink'] );
?>

<header role="banner" class="banner <?php echo $banner['type']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- theme audit ?>"
	<?php
    if ( ! empty( $banner['backgroundColor'] ) ) {
?>
style="background-color: <?php echo $banner['backgroundColor'] . ';'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- theme audit ?>" <?php } ?>
>
	<div class="grid-container">
		<div class="grid-x align-middle">
			<div class="small-12 large-8 cell title">
				<?php if ( ! empty( $banner['title'] ) ) { ?>
					<h2 class="entry-title"><?php echo $banner['title']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- theme audit ?></h2>
				<?php } ?>
				<?php if ( ! empty( $banner['subtitle'] ) ) { ?>
					<h4 class="subtitle"><?php echo do_shortcode( $banner['subtitle'] ); ?></h4>
				<?php } ?>
			</div>
			<?php if ( 'front' === $banner['type'] ) { ?>
				<?php if ( ! empty( $banner['buttonText'] ) && ! empty( $banner['buttonLink'] ) ) { ?>
					<div class="small-12 large-4 cell grid-x align-center">
						<a class="button dark" href="<?php echo $banner['buttonLink']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- theme audit ?>">
							<h4><?php echo $banner['buttonText']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- theme audit ?></h4></a>
					</div>
				<?php } ?>
			<?php } ?>
		</div>
	</div>
</header>
