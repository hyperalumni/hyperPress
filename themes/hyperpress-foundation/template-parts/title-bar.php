<?php
/**
 * Template part for the mobile title bar: hamburger and site name on the left
 *
 * @package FoundationPress
 * @since FoundationPress 1.0.0
 */

// Don't load directly.
defined( 'ABSPATH' ) || exit;
?>

<div class="title-bar-left">
	<?php get_template_part( 'template-parts/menu-toggle' ); ?>
	<span class="site-mobile-title title-bar-title">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a>
	</span>
</div>
