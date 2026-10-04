<?php
/**
 * The sidebar containing the main widget area
 *
 * @package FoundationPress
 * @since FoundationPress 1.0.0
 */

// Don't load directly.
defined( 'ABSPATH' ) || exit;
?>
<aside class="sidebar">
	<?php dynamic_sidebar( 'sidebar-widgets' ); ?>
</aside>
