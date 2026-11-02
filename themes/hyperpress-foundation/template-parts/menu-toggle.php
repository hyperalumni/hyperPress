<?php
/**
 * Template part for the mobile menu toggle (hamburger) button
 *
 * @package FoundationPress
 * @since FoundationPress 1.0.0
 */

// Don't load directly.
defined( 'ABSPATH' ) || exit;
?>

<button aria-label="<?php esc_attr_e( 'Main Menu', 'hyperpress' ); ?>" class="menu-icon" type="button" <?php hyperpress_mobile_toggle_attributes(); ?>
				data-toggle="<?php hyperpress_mobile_menu_id(); ?>"></button>
