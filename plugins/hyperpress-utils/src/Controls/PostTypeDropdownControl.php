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
		$defaults         = empty( $options['show_ui'] ) ? array( 'public' => true ) : array();
		$post_args        = wp_parse_args( $options, $defaults );
		$this->post_types = get_post_types( $post_args, 'object' );
		parent::__construct( $manager, $id, $args );
	}

	/**
	 * Sanitize callback for the post type multi-select: keep only registered, public post type keys.
	 *
	 * @param mixed $value Submitted value.
	 * @return string[]
	 */
	public static function sanitize_post_types( $value ): array {
		return self::filter_post_types( $value, 'public' );
	}

	/**
	 * Sanitize callback for a control built with the `show_ui` option: keep registered post types that have an admin UI.
	 *
	 * @param mixed $value Submitted value.
	 * @return string[]
	 */
	public static function sanitize_show_ui_post_types( $value ): array {
		return self::filter_post_types( $value, 'show_ui' );
	}

	/**
	 * Keep registered post type keys whose object has a truthy property.
	 *
	 * @param mixed  $value Submitted value.
	 * @param string $flag  Post type object property that must be truthy.
	 * @return string[]
	 */
	private static function filter_post_types( $value, string $flag ): array {
		return array_values(
			array_filter(
				is_array( $value ) ? $value : array(),
				static function ( $type ) use ( $flag ): bool {
					if ( ! is_string( $type ) || ! post_type_exists( $type ) ) {
						return false;
					}
					$object = get_post_type_object( $type );
					return null !== $object && $object->$flag;
				}
			)
		);
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
			<span class="description customize-control-description"><?php echo wp_kses_post( $this->description ); ?></span>
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
