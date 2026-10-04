<?php
/**
 * The template part for displaying a message that posts cannot be found
 *
 * Learn more: {@link https://codex.wordpress.org/Template_Hierarchy}
 *
 * @package FoundationPress
 * @since FoundationPress 1.0.0
 */

// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}
?>

<header class="page-header">
	<h1 class="page-title"><?php _e( 'Nothing Found', 'hyperpress' ); // phpcs:ignore WordPress.Security.EscapeOutput.UnsafePrintingFunction -- theme audit ?></h1>
</header>

<div class="page-content">
	<?php if ( is_home() && current_user_can( 'publish_posts' ) ) : ?>

	<p>
		<?php
			printf(
				/* translators: %1$s: new post url */
				__( 'Ready to publish your first post? <a href="%1$s">Get started here</a>.', 'hyperpress' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- theme audit
				admin_url( 'post-new.php' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- theme audit
			);
		?>
	</p>

	<?php elseif ( is_search() ) : ?>

	<p><?php _e( 'Sorry, but nothing matched your search terms. Please try again with some different keywords.', 'hyperpress' ); // phpcs:ignore WordPress.Security.EscapeOutput.UnsafePrintingFunction -- theme audit ?></p>
	<?php get_search_form(); ?>

	<?php else : ?>

	<p><?php _e( 'It seems we can&rsquo;t find what you&rsquo;re looking for. Perhaps searching can help.', 'hyperpress' ); // phpcs:ignore WordPress.Security.EscapeOutput.UnsafePrintingFunction -- theme audit ?></p>
	<?php get_search_form(); ?>

	<?php endif; ?>
</div>
