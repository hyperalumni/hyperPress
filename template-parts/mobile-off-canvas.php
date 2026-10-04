<?php
/**
 * Template part for off canvas menu
 *
 * @package FoundationPress
 * @since FoundationPress 1.0.0
 */

// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}
?>

<nav class="mobile-off-canvas-menu off-canvas position-left" id="<?php hyperpress_mobile_menu_id(); ?>" data-off-canvas data-auto-focus="false" role="navigation">
	<?php foundationpress_mobile_nav(); ?>
</nav>

<div class="off-canvas-content" data-off-canvas-content>
