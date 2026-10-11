<?php
// Don't load directly.
defined( 'ABSPATH' ) || exit;

$breadcrumbs = apply_filters(
    'hyperpress_breadcrumbs_content',
    array(
		array(
			'title'   => 'Home',
			'url'     => home_url(),
			'classes' => array(
				'li'    => array( 'item-home' ),
				'bread' => array( 'bread-link bread-home' ),
			),
		),
	)
    );
?>
<ul id="breadcrumbs" class="breadcrumbs">
	<?php foreach ( $breadcrumbs as $breadcrumb ) : ?>
		<li class="item <?php echo esc_attr( implode( ' ', $breadcrumb['classes']['li'] ) ); ?>">
			<?php if ( ! empty( $breadcrumb['url'] ) ) : ?>
				<a
					class="breadcrumb <?php echo esc_attr( implode( ' ', $breadcrumb['classes']['bread'] ) ); ?>"
					title="<?php echo esc_attr( $breadcrumb['title'] ); ?>"
					href="<?php echo esc_url( $breadcrumb['url'] ); ?>"><?php echo esc_html( $breadcrumb['title'] ); ?></a>
			<?php else : ?>
				<span class="<?php echo esc_attr( implode( ' ', $breadcrumb['classes']['bread'] ) ); ?>"
						title="<?php echo esc_attr( $breadcrumb['title'] ); ?>"><?php echo esc_html( $breadcrumb['title'] ); ?></span>
			<?php endif; ?>
		</li>
	<?php endforeach; ?>
</ul>
