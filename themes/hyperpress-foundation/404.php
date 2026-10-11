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
					<h1 class="entry-title"><?php esc_html_e( 'File Not Found', 'hyperpress' ); ?></h1>
				</header>
				<div class="entry-content">
					<div class="error">
						<p class="bottom"><?php esc_html_e( 'The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.', 'hyperpress' ); ?></p>
					</div>
					<p><?php esc_html_e( 'Please try the following:', 'hyperpress' ); ?></p>
					<ul>
						<li>
							<?php esc_html_e( 'Check your spelling', 'hyperpress' ); ?>
						</li>
						<li>
							<?php
								echo wp_kses(
									sprintf(
										/* translators: %s: home page url */
										__( 'Return to the <a href="%s">home page</a>', 'hyperpress' ),
										esc_url( home_url() )
									),
									array( 'a' => array( 'href' => array() ) )
								);
							?>
						</li>
						<li>
							<?php
								// The string is theme-controlled, so the javascript: protocol is allowed for this one link (kses would strip it).
								echo wp_kses( __( 'Click the <a href="javascript:history.back()">Back</a> button', 'hyperpress' ), array( 'a' => array( 'href' => array() ) ), array( 'http', 'https', 'mailto', 'javascript' ) );
							?>
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
