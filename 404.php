<?php
/**
 * The template for displaying 404 pages (not found)
 *
 * @package FoundationPress
 * @since FoundationPress 1.0.0
 */

// Don't load directly.
defined( 'ABSPATH' ) || exit;
get_header(); ?>
<?php get_template_part( 'template-parts/banner' ); ?>
<div class="main-container">
	<div class="main-grid">
		<main class="main-content">
			<article>
				<header>
					<h1 class="entry-title"><?php _e( 'File Not Found', 'hyperpress' ); // phpcs:ignore WordPress.Security.EscapeOutput.UnsafePrintingFunction -- theme audit ?></h1>
				</header>
				<div class="entry-content">
					<div class="error">
						<p class="bottom"><?php _e( 'The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.', 'hyperpress' ); // phpcs:ignore WordPress.Security.EscapeOutput.UnsafePrintingFunction -- theme audit ?></p>
					</div>
					<p><?php _e( 'Please try the following:', 'hyperpress' ); // phpcs:ignore WordPress.Security.EscapeOutput.UnsafePrintingFunction -- theme audit ?></p>
					<ul>
						<li>
							<?php _e( 'Check your spelling', 'hyperpress' ); // phpcs:ignore WordPress.Security.EscapeOutput.UnsafePrintingFunction -- theme audit ?>
						</li>
						<li>
							<?php
								printf(
									/* translators: %s: home page url */
									__( 'Return to the <a href="%s">home page</a>', 'hyperpress' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- theme audit
									home_url() // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- theme audit
								);
							?>
						</li>
						<li>
							<?php _e( 'Click the <a href="javascript:history.back()">Back</a> button', 'hyperpress' ); // phpcs:ignore WordPress.Security.EscapeOutput.UnsafePrintingFunction -- theme audit ?>
						</li>
					</ul>
				</div>
			</article>
		</main>
		<?php get_sidebar(); ?>
	</div>
</div>
<?php
get_footer();
