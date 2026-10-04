<?php
/**
 * The sidebar containing the main widget area
 *
 * @package FoundationPress
 * @since FoundationPress 1.0.0
 */

// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}
?>
<aside class="sidebar">
	<?php dynamic_sidebar( 'sidebar-widgets' ); ?>
</aside>
