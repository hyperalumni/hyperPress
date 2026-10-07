<?php
/**
 * Multi-select Customizer control listing the registered labels.
 *
 * @package HyperPress\Label
 */

namespace HyperPress\Label\Controls;

use HyperPress\Label\Registry;
use WP_Customize_Control;

defined( 'ABSPATH' ) || exit;

/**
 * Multi-select of registry labels; option text is "{group}: {title}".
 */
class LabelDropdownControl extends WP_Customize_Control {

	/**
	 * Control type.
	 *
	 * @var string
	 */
	public $type = 'dropdown-labels';

	/**
	 * Render the control.
	 */
	protected function render_content() {
		$labels = Registry::instance()->all();
		?>
		<label for="<?php echo esc_attr( $this->id ); ?>">
		<?php if ( ! empty( $this->label ) ) : ?>
			<span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
		<?php endif; ?>
		<?php if ( ! empty( $this->description ) ) : ?>
			<span class="description customize-control-description"><?php echo wp_kses_post( $this->description ); ?></span>
		<?php endif; ?>
		</label>
		<?php
		if ( empty( $labels ) ) {
			return;
		}

		$values = array_map( 'strval', array_filter( (array) $this->value(), 'is_scalar' ) );
		?>
		<select <?php $this->link(); ?> name="<?php echo esc_attr( $this->id ); ?>"
										id="<?php echo esc_attr( $this->id ); ?>"
										multiple size="<?php echo count( $labels ); ?>">
			<?php
			foreach ( $labels as $id => $label ) {
				printf(
					'<option value="%s" %s>%s</option>',
					esc_attr( (string) $id ),
					selected( in_array( (string) $id, $values, true ), true, false ),
					esc_html( $label['group'] . ': ' . $label['title'] )
				);
			}
			?>
		</select>
		<?php
	}
}
