<?php
/**
 * Template part for the mobile title bar of the drilldown layout: logo on the left, hamburger on the right
 *
 * @package FoundationPress
 * @since FoundationPress 1.0.0
 */

// Don't load directly.
defined( 'ABSPATH' ) || exit;
?>

<div class="title-bar-left">
	<span class="site-mobile-logo">
		<?php
		if ( has_custom_logo() ) {
			the_custom_logo();
		} else {
			?>
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a>
			<?php
		}
		?>
	</span>
</div>
<div class="title-bar-right">
	<?php get_template_part( 'template-parts/menu-toggle' ); ?>
</div>
