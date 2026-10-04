<?php
namespace HyperPress\Socials\Widgets;

defined( 'ABSPATH' ) || exit;

use WP_Widget;

class DisplaySocialsWidget extends WP_Widget {

	function __construct() {
		parent::__construct(
			'hyperpress_socials',
			__( 'hyperPress Socials', 'hyperpress' ),
			array( 'description' => __( 'Display the socials defined in the theme Customizer Socials section', 'hyperpress' ) )
		);
	}

	public function widget( $args, $instance ): void {
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


		$title = apply_filters( 'widget_title', $instance['title'] );

		echo $args['before_widget'];
		if ( ! empty( $title ) ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}
		echo '<div class="grid-x grid-padding-x xsmall-up-3 small-up-4 medium-up-2 large-up-3 xlarge-up-4">';

		foreach ( $socials as $social ) {
			if ( ! empty( $social['url'] ) ) {
				$target = str_contains( $social['url'], get_site_url() ) ? '_self' : '_blank';
				echo '<div class="cell">';
				echo '<a href="' . esc_url( $social['url'] ) . '" title="' . esc_attr( $social['title'] ) . '" target="' . $target . '">';
				echo '<span class="fa-stack fa-2x">';
				// the "font-size: 2rem;" is needed or else the icons are too big, as it uses "font-size: 2em;"
//				echo '<i class="fa-solid fa-square fa-stack-2x" style="font-size: 2rem;"></i>';
				echo '<i class="fa-solid fa-square fa-stack-2x"></i>';
				echo '<i class="' . $social['icon'] . ' fa-stack-1x fa-inverse"></i>';
				echo '</span>';
				echo '</a>';
				echo '</div>';
			}
		}

		echo '</div>';

		// Output widget content here
		echo $args['after_widget'];
	}

	public function form( $instance ): void {
		$title = $instance['title'] ?? __( 'Stalk Us', 'hyperpress' );

		// Widget admin form
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>">
				<?php _e( 'Title:', 'hyperpress' ); ?>
			</label>
			<input
					class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>"
					name="<?php echo $this->get_field_name( 'title' ); ?>"
					type="text"
					value="<?php echo esc_attr( $title ); ?>"
			/>
		</p>
		<?php
	}

	public function update( $new_instance, $old_instance ): array {
		// Process widget options to be saved
		$instance          = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';

		return $instance;
	}
}
