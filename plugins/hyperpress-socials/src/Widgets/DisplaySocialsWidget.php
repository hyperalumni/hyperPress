<?php
namespace HyperPress\Socials\Widgets;

defined( 'ABSPATH' ) || exit;

use WP_Widget;

class DisplaySocialsWidget extends WP_Widget {

	public function __construct() {
		parent::__construct(
			'hyperpress_socials',
			__( 'hyperPress Socials', 'hyperpress' ),
			array( 'description' => __( 'Display the socials defined in the theme Customizer Socials section', 'hyperpress' ) )
		);
	}

	public function widget( $args, $instance ): void {
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

		$title = apply_filters( 'widget_title', $instance['title'] );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plugins audit
		echo $args['before_widget'];
		if ( ! empty( $title ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plugins audit
			echo $args['before_title'] . $title . $args['after_title'];
		}
		echo '<div class="grid-x grid-padding-x xsmall-up-3 small-up-4 medium-up-2 large-up-3 xlarge-up-4">';

		foreach ( $socials as $social ) {
			if ( ! empty( $social['url'] ) ) {
				$target = self::is_internal( $social['url'] ) ? '_self' : '_blank';
				$rel    = '_blank' === $target ? ' rel="noopener noreferrer"' : '';
				echo '<div class="cell">';
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plugins audit
				echo '<a href="' . esc_url( $social['url'] ) . '" title="' . esc_attr( $social['title'] ) . '" target="' . $target . '"' . $rel . '>';
				echo '<span class="fa-stack fa-2x">';
				// echo '<i class="fa-solid fa-square fa-stack-2x" style="font-size: 2rem;"></i>';
				// The "font-size: 2rem;" is needed or else the icons are too big, as it uses "font-size: 2em;".
				echo '<i class="fa-solid fa-square fa-stack-2x"></i>';
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plugins audit
				echo '<i class="' . $social['icon'] . ' fa-stack-1x fa-inverse"></i>';
				echo '</span>';
				echo '</a>';
				echo '</div>';
			}
		}

		echo '</div>';

		// Output widget content here.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plugins audit
		echo $args['after_widget'];
	}

	public function form( $instance ): void {
		$title = $instance['title'] ?? __( 'Stalk Us', 'hyperpress' );

		// Widget admin form.
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plugins audit */ ?>">
				<?php /* phpcs:ignore WordPress.Security.EscapeOutput.UnsafePrintingFunction -- plugins audit */ _e( 'Title:', 'hyperpress' ); ?>
			</label>
			<input
					class="widefat" id="<?php echo $this->get_field_id( 'title' ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plugins audit */ ?>"
					name="<?php echo $this->get_field_name( 'title' ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plugins audit */ ?>"
					type="text"
					value="<?php echo esc_attr( $title ); ?>"
			/>
		</p>
		<?php
	}

	public function update( $new_instance, $old_instance ): array {
		// Process widget options to be saved.
		$instance = array();
		// phpcs:ignore WordPress.WP.AlternativeFunctions.strip_tags_strip_tags -- plugins audit
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';

		return $instance;
	}

	/**
	 * True when the URL points at this site: a relative URL, or one whose host equals the site's host.
	 * Comparing hosts (not substrings) stops `https://other.example/?r=<site url>` passing as internal.
	 *
	 * @param string $url Link URL.
	 */
	private static function is_internal( string $url ): bool {
		$host = wp_parse_url( $url, PHP_URL_HOST );

		if ( null === $host || false === $host ) {
			// No host: a relative URL (or an unparseable one, which esc_url() will reject).
			return 0 === strpos( $url, '/' ) && 0 !== strpos( $url, '//' );
		}

		return strtolower( $host ) === strtolower( (string) wp_parse_url( get_site_url(), PHP_URL_HOST ) );
	}
}
