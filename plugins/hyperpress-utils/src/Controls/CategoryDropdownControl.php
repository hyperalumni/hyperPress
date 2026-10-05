<?php
namespace HyperPress\Utils\Controls;

defined( 'ABSPATH' ) || exit;

use WP_Customize_Control;

class CategoryDropdownControl extends WP_Customize_Control {
	public $type = 'dropdown-category';

	protected array $dropdown_args = array();

	protected function render_content(): void {
		?>
		<label for="_customize-input-<?php echo esc_attr( $this->id ); ?>">
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

		$dropdown_args = wp_parse_args(
			$this->dropdown_args,
			array(
				'taxonomy'          => 'category',
				'show_option_none'  => __( 'Uncategorized', 'hyperpress' ),
				'show_option_all'   => '',
				'orderby'           => 'id',
				'order'             => 'ASC',
				'show_count'        => 1,
				'hide_empty'        => 1,
				'child_of'          => 0,
				'exclude'           => '',
				'hierarchical'      => 1,
				'depth'             => 0,
				'tab_index'         => 0,
				'hide_if_empty'     => false,
				'option_none_value' => 0,
				'value_field'       => 'term_id',
				'echo'              => false,
				'selected'          => -1,
				'name'              => $this->id,
				'id'                => '_customize-input-' . $this->id,
			)
		);

		$selected = array_map( 'strval', (array) $this->value() );
		$dropdown = wp_dropdown_categories( $dropdown_args );
		$dropdown = str_replace( '<select', '<select multiple size="10" ' . $this->get_link(), $dropdown );
		// The core walker preselects a single term only; mark every stored value for this multi-select.
		$dropdown = preg_replace_callback(
			'/<option [^>]*?value=([\'"])(\d+)\1/',
			static fn( array $found ): string => in_array( $found[2], $selected, true )
				? $found[0] . ' selected="selected"'
				: $found[0],
			$dropdown
		);

		echo $dropdown; // phpcs:ignore WordPress.Security.EscapeOutput -- wp_dropdown_categories() escapes its output
	}
}
