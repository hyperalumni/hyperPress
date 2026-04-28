<?php
/**
 * The template for displaying the footer
 *
 * Contains the closing of the "off-canvas-wrap" div and all content after.
 *
 * @package FoundationPress
 * @since FoundationPress 1.0.0
 */
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
					<?php $copyrightName = get_theme_mod( 'hyperpress_site_copyright_name' ); ?>
					<?php if ( ! empty( $copyrightName ) ) : ?>
						<h6><?php echo $copyrightName; ?></h6>
					<?php endif; ?>
				</section>
				<section class="cell shrink">
					<?php
					$socials = [
						[
							'icon'  => 'fa-solid fa-robot',
							'url'   => get_theme_mod( 'hyperpress_socials_firstinspires' ),
							'title' => 'FIRST Inspires'
						],
						[
							'icon'  => 'fa-regular fa-lightbulb',
							'url'   => get_theme_mod( 'hyperpress_socials_thebluealliance' ),
							'title' => 'The Blue Alliance'
						],
						[
							'icon'  => 'fa-brands fa-github',
							'url'   => get_theme_mod( 'hyperpress_socials_github' ),
							'title' => 'GitHub'
						],
						[
							'icon'  => 'fa-brands fa-facebook-f',
							'url'   => get_theme_mod( 'hyperpress_socials_facebook' ),
							'title' => 'Facebook'
						],
						[
							'icon'  => 'fa-brands fa-instagram',
							'url'   => get_theme_mod( 'hyperpress_socials_instagram' ),
							'title' => 'Instagram'
						],
						[
							'icon'  => 'fa-brands fa-twitter',
							'url'   => get_theme_mod( 'hyperpress_socials_twitter' ),
							'title' => 'Twitter'
						],
						[
							'icon'  => 'fa-brands fa-youtube',
							'url'   => get_theme_mod( 'hyperpress_socials_youtube' ),
							'title' => 'YouTube'
						],
						[
							'icon'  => 'fa-brands fa-snapchat',
							'url'   => get_theme_mod( 'hyperpress_socials_snapchat' ),
							'title' => 'Snapchat'
						],
						[
							'icon'  => 'fa-brands fa-tiktok',
							'url'   => get_theme_mod( 'hyperpress_socials_tiktok' ),
							'title' => 'TikTok'
						],
						[
							'icon'  => 'fa-brands fa-discord',
							'url'   => get_theme_mod( 'hyperpress_socials_discord' ),
							'title' => 'Discord Server'
						],
						[
							'icon'  => 'fa-regular fa-envelope',
							'url'   => ! empty( get_theme_mod( 'hyperpress_socials_contact' ) ) ? get_page_link( get_theme_mod( 'hyperpress_socials_contact' ) ) : '',
							'title' => 'Contact Us'
						],
						[
							'icon'  => 'fa-regular fa-calendar',
							'url'   => get_theme_mod( 'hyperpress_socials_add_calendar' ),
							'title' => 'Add Our Calendar'
						]
					];
					?>
					<ul class="menu simple">
						<?php foreach ( $socials as $social ) : ?>
							<?php if ( ! empty( $social['url'] ) ) : ?>
								<?php $target = str_contains( $social['url'], get_site_url() ) ? '_self' : '_blank'; ?>
								<li>
									<a href="<?php echo esc_url( $social['url'] ); ?>" title="<?php echo esc_attr( $social['title'] ) ?>"
										 target="<?php echo $target; ?>">
										<i class="<?php echo $social['icon']; ?> fa-inverse" aria-hidden="true"></i>
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
