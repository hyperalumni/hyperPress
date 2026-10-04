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
						<h6><?php echo $copyright_name; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- theme audit ?></h6>
					<?php endif; ?>
				</section>
				<section class="cell shrink">
					<?php
					$socials = array(
						array(
							'icon'  => 'fa-solid fa-robot',
							'url'   => get_theme_mod( 'hyperpress_socials_firstinspires' ),
							'title' => 'FIRST Inspires',
						),
						array(
							'icon'  => 'fa-regular fa-lightbulb',
							'url'   => get_theme_mod( 'hyperpress_socials_thebluealliance' ),
							'title' => 'The Blue Alliance',
						),
						array(
							'icon'  => 'fa-brands fa-github',
							'url'   => get_theme_mod( 'hyperpress_socials_github' ),
							'title' => 'GitHub',
						),
						array(
							'icon'  => 'fa-brands fa-facebook-f',
							'url'   => get_theme_mod( 'hyperpress_socials_facebook' ),
							'title' => 'Facebook',
						),
						array(
							'icon'  => 'fa-brands fa-instagram',
							'url'   => get_theme_mod( 'hyperpress_socials_instagram' ),
							'title' => 'Instagram',
						),
						array(
							'icon'  => 'fa-brands fa-twitter',
							'url'   => get_theme_mod( 'hyperpress_socials_twitter' ),
							'title' => 'Twitter',
						),
						array(
							'icon'  => 'fa-brands fa-youtube',
							'url'   => get_theme_mod( 'hyperpress_socials_youtube' ),
							'title' => 'YouTube',
						),
						array(
							'icon'  => 'fa-brands fa-snapchat',
							'url'   => get_theme_mod( 'hyperpress_socials_snapchat' ),
							'title' => 'Snapchat',
						),
						array(
							'icon'  => 'fa-brands fa-tiktok',
							'url'   => get_theme_mod( 'hyperpress_socials_tiktok' ),
							'title' => 'TikTok',
						),
						array(
							'icon'  => 'fa-brands fa-discord',
							'url'   => get_theme_mod( 'hyperpress_socials_discord' ),
							'title' => 'Discord Server',
						),
						array(
							'icon'  => 'fa-regular fa-envelope',
							'url'   => ! empty( get_theme_mod( 'hyperpress_socials_contact' ) ) ? get_page_link( get_theme_mod( 'hyperpress_socials_contact' ) ) : '',
							'title' => 'Contact Us',
						),
						array(
							'icon'  => 'fa-regular fa-calendar',
							'url'   => get_theme_mod( 'hyperpress_socials_add_calendar' ),
							'title' => 'Add Our Calendar',
						),
					);
					?>
					<ul class="menu simple">
						<?php foreach ( $socials as $social ) : ?>
							<?php if ( ! empty( $social['url'] ) ) : ?>
								<?php $target = str_contains( $social['url'], get_site_url() ) ? '_self' : '_blank'; ?>
								<li>
									<a href="<?php echo esc_url( $social['url'] ); ?>" title="<?php echo esc_attr( $social['title'] ); ?>"
										target="<?php echo $target; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- theme audit ?>">
										<i class="<?php echo $social['icon']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- theme audit ?> fa-inverse" aria-hidden="true"></i>
									</a>
								</li>
							<?php endif; ?>
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
