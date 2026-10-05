<?php
/**
 * The template for displaying the footer
 *
 * Contains the closing of the "off-canvas-wrap" div and all content after.
 *
 * @package FoundationPress
 * @since FoundationPress 1.0.0
 */

// Don't load directly.
defined( 'ABSPATH' ) || exit;
?>
<footer class="footer-container">
	<div class="footer-grid-container">
		<div class="footer-grid">
			<?php dynamic_sidebar( 'footer-widgets' ); ?>
		</div>
	</div>

	<div class="sub-footer">
		<div class="grid-container">
			<div class="grid-x align-justify">
				<section class="cell shrink">
					<?php $copyright_name = get_theme_mod( 'hyperpress_site_copyright_name' ); ?>
					<?php if ( ! empty( $copyright_name ) ) : ?>
						<h6><?php echo esc_html( $copyright_name ); ?></h6>
					<?php endif; ?>
				</section>
				<section class="cell shrink">
					<ul class="menu simple">
						<?php foreach ( hyperpress_social_links() as $social_link ) : ?>
							<li>
								<a href="<?php echo esc_url( $social_link['url'] ); ?>" title="<?php echo esc_attr( $social_link['title'] ); ?>"
									target="<?php echo esc_attr( $social_link['target'] ); ?>"<?php echo ! empty( $social_link['rel'] ) ? ' rel="' . esc_attr( $social_link['rel'] ) . '"' : ''; ?>>
									<i class="<?php echo esc_attr( $social_link['icon'] ); ?> fa-inverse" aria-hidden="true"></i>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				</section>
			</div>
		</div>
	</div>
</footer>
<?php if ( get_theme_mod( 'wpt_mobile_menu_layout' ) === 'offcanvas' ) : ?>
	</div><!-- Close off-canvas content -->
<?php endif; ?>

<?php wp_footer(); ?>

</section>
</body>
</html>
