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

	/**
	 * Instance for rendering this widget as the default content of a widget area with the_widget().
	 *
	 * @return array{title: string, layout: string}
	 */
	public static function default_instance(): array {
		return array(
			'title'  => '',
			'layout' => 'inline',
		);
	}

	private function render_grid(): void {
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
	}

	private function render_inline(): void {
		echo '<ul class="menu simple">';

		foreach ( Links::all() as $link ) {
			echo '<li><a href="' . esc_url( $link['url'] ) . '" title="' . esc_attr( $link['title'] ) . '" target="' . esc_attr( $link['target'] ) . '"';
			if ( '' !== $link['rel'] ) {
				echo ' rel="' . esc_attr( $link['rel'] ) . '"';
			}
			echo '>';
			echo '<i class="' . esc_attr( $link['icon'] ) . ' fa-inverse" aria-hidden="true"></i>';
			echo '</a></li>';
		}

		echo '</ul>';
	}
	public function widget( $args, $instance ): void {
		$title = apply_filters( 'widget_title', $instance['title'] ?? __( 'Stalk Us', 'hyperpress' ), $instance, $this->id_base );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plugins audit
		echo $args['before_widget'];
		if ( ! empty( $title ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plugins audit
			echo $args['before_title'] . $title . $args['after_title'];
		}
		if ( 'inline' === ( $instance['layout'] ?? 'grid' ) ) {
			$this->render_inline();
		} else {
			$this->render_grid();
		}

		// Output widget content here.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plugins audit
		echo $args['after_widget'];
	}

	public function form( $instance ): void {
		$title  = $instance['title'] ?? __( 'Stalk Us', 'hyperpress' );
		$layout = 'inline' === ( $instance['layout'] ?? 'grid' ) ? 'inline' : 'grid';

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
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'layout' ) ); ?>">
				<?php esc_html_e( 'Layout:', 'hyperpress' ); ?>
			</label>
			<select
					class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'layout' ) ); ?>"
					name="<?php echo esc_attr( $this->get_field_name( 'layout' ) ); ?>"
			>
				<option value="grid"<?php selected( $layout, 'grid' ); ?>><?php esc_html_e( 'Grid (large icons)', 'hyperpress' ); ?></option>
				<option value="inline"<?php selected( $layout, 'inline' ); ?>><?php esc_html_e( 'Inline list (small icons)', 'hyperpress' ); ?></option>
			</select>
		</p>
		<?php
	}

	public function update( $new_instance, $old_instance ): array {
		// Process widget options to be saved.
		$title = $new_instance['title'] ?? '';

		$layout = $new_instance['layout'] ?? '';

		return array(
			'title'  => is_string( $title ) ? sanitize_text_field( $title ) : '',
			'layout' => 'inline' === $layout ? 'inline' : 'grid',
		);
	}
}
