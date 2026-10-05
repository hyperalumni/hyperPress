<?php
namespace HyperPress\Socials\Widgets;

defined( 'ABSPATH' ) || exit;

use HyperPress\Socials\Links;
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
		$title = apply_filters( 'widget_title', $instance['title'] ?? __( 'Stalk Us', 'hyperpress' ), $instance, $this->id_base );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plugins audit
		echo $args['before_widget'];
		if ( ! empty( $title ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plugins audit
			echo $args['before_title'] . $title . $args['after_title'];
		}
		echo '<div class="grid-x grid-padding-x xsmall-up-3 small-up-4 medium-up-2 large-up-3 xlarge-up-4">';

		foreach ( Links::all() as $link ) {
			echo '<div class="cell">';
			echo '<a href="' . esc_url( $link['url'] ) . '" title="' . esc_attr( $link['title'] ) . '" target="' . esc_attr( $link['target'] ) . '"';
			if ( '' !== $link['rel'] ) {
				echo ' rel="' . esc_attr( $link['rel'] ) . '"';
			}
			echo '>';
			echo '<span class="fa-stack fa-2x">';
			// echo '<i class="fa-solid fa-square fa-stack-2x" style="font-size: 2rem;"></i>';
			// The "font-size: 2rem;" is needed or else the icons are too big, as it uses "font-size: 2em;".
			echo '<i class="fa-solid fa-square fa-stack-2x"></i>';
			echo '<i class="' . esc_attr( $link['icon'] ) . ' fa-stack-1x fa-inverse"></i>';
			echo '</span>';
			echo '</a>';
			echo '</div>';
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
		$title = $new_instance['title'] ?? '';

		return array( 'title' => is_string( $title ) ? sanitize_text_field( $title ) : '' );
	}
}
