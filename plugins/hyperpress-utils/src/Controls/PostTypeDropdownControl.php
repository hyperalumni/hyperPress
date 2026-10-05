<?php
namespace HyperPress\Utils\Controls;

defined( 'ABSPATH' ) || exit;

use WP_Customize_Control;

class PostTypeDropdownControl extends WP_Customize_Control {
	public $type = 'dropdown-post-types';

	/**
	 * Declared (was a dynamic property, deprecated in PHP 8.2).
	 *
	 * @var array<string,\WP_Post_Type>
	 */
	protected array $post_types = array();

	public function __construct( $manager, $id, $args = array(), $options = array() ) {
		$post_args        = wp_parse_args( $options, array( 'public' => true ) );
		$this->post_types = get_post_types( $post_args, 'object' );
		parent::__construct( $manager, $id, $args );
	}

	protected function render_content() {
		?>
		<label for="<?php echo esc_attr( $this->id ); ?>">
		<?php
		if ( ! empty( $this->label ) ) :
			?>
			<span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
			<?php
		endif;

		if ( ! empty( $this->description ) ) :
			?>
			<span class="description customize-control-description"><?php echo $this->description; // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
			<?php
		endif;
		?>
		</label>
		<?php
		if ( ! empty( $this->post_types ) ) :
			$values = (array) $this->value();
			?>
			<select <?php $this->link(); ?> name="<?php echo esc_attr( $this->id ); ?>"
											id="<?php echo esc_attr( $this->id ); ?>"
											multiple size="<?php echo count( $this->post_types ); ?>">
				<?php
				foreach ( $this->post_types as $post_type ) {
					printf(
						'<option value="%s" %s>%s</option>',
						esc_attr( $post_type->name ),
						selected( in_array( $post_type->name, $values, true ), true, false ),
						esc_html( $post_type->labels->name )
					);
				}
				?>
			</select>
			<?php
		endif;
	}
}
