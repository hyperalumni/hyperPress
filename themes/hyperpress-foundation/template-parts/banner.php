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

$banner['type'] = sanitize_text_field( $banner['type'] );
// The subtitle may carry emphasis (<span class="emphasis">, which the archive routers produce) but no other markup.
$banner['subtitle']        = wp_kses(
	(string) $banner['subtitle'],
	array(
		'span'   => array( 'class' => true ),
		'strong' => array(),
		'em'     => array(),
		'br'     => array(),
	)
);
$banner['backgroundColor'] = sanitize_hex_color( $banner['backgroundColor'] );
$banner['buttonText']      = sanitize_text_field( $banner['buttonText'] );
$banner['buttonLink']      = sanitize_url( $banner['buttonLink'] );
?>

<header role="banner" class="banner <?php echo esc_attr( $banner['type'] ?? '' ); ?>"
	<?php
    if ( ! empty( $banner['backgroundColor'] ) ) {
?>
style="background-color: <?php echo esc_attr( $banner['backgroundColor'] ); ?>;" <?php } ?>
>
	<div class="grid-container">
		<div class="grid-x align-middle">
			<div class="small-12 large-8 cell title">
				<?php if ( ! empty( $banner['title'] ) ) { ?>
					<h2 class="entry-title"><?php echo wp_kses_post( $banner['title'] ); ?></h2>
				<?php } ?>
				<?php if ( ! empty( $banner['subtitle'] ) ) { ?>
					<h4 class="subtitle"><?php echo do_shortcode( $banner['subtitle'] ); ?></h4>
				<?php } ?>
			</div>
			<?php if ( 'front' === $banner['type'] ) { ?>
				<?php if ( ! empty( $banner['buttonText'] ) && ! empty( $banner['buttonLink'] ) ) { ?>
					<div class="small-12 large-4 cell grid-x align-center">
						<a class="button dark" href="<?php echo esc_url( $banner['buttonLink'] ); ?>">
							<h4><?php echo esc_html( $banner['buttonText'] ); ?></h4></a>
					</div>
				<?php } ?>
			<?php } ?>
		</div>
	</div>
</header>
