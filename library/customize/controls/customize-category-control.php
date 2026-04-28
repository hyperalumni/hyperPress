<?php

class HYPERpress_Dropdown_Category_Control extends WP_Customize_Control {
	public $type = 'dropdown-category';

	protected array $dropdown_args = array();

	protected function render_content(): void {
		?><label><?php

		if ( ! empty( $this->label ) ) :
			?><span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span><?php
		endif;

		if ( ! empty( $this->description ) ) :
			?><span class="description customize-control-description"><?php echo $this->description; ?></span><?php
		endif;

		?></label><?php

		$dropdown_args = wp_parse_args( $this->dropdown_args, array(
			'taxonomy'          => 'category',
			'show_option_none'  => 'Uncategorized',
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
		) );


		$dropdown = wp_dropdown_categories( $dropdown_args );
		$dropdown = str_replace( '<select', '<select' . $this->get_link(), $dropdown );
		// TODO: build a multiselect drop down by extending Walker_Category_Checklist?
		$multiDropdown = str_replace( '<select', '<select multiple size="10"', $dropdown );

		echo $multiDropdown;
	}
}
