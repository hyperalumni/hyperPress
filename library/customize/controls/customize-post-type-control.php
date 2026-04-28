<?php

class HYPERpress_Dropdown_Post_Type_Control extends WP_Customize_Control {
	public $type = 'dropdown-post-types';

	public function __construct( $manager, $id, $args = array(), $options = array() ) {
		$postargs        = wp_parse_args( $options, array( 'public' => true ) );
		$this->postTypes = get_post_types( $postargs, 'object' );
		parent::__construct( $manager, $id, $args );
	}

	protected function render_content() {
		?>
	<label for="<?php esc_attr( $this->id ) ?>">
		<?php

		if ( ! empty( $this->label ) ) :
			?><span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span><?php
		endif;

		if ( ! empty( $this->description ) ) :
			?>
			<span class="description customize-control-description"><?php echo $this->description; ?></span>
		<?php
		endif;

		?></label><?php
		if ( ! empty( $this->postTypes ) ) : ?>

			<select <?php $this->link(); ?> name="<?php esc_attr( $this->id ) ?>" id="<?php esc_attr( $this->id ) ?>"
			                                multiple size="<?php echo count( $this->postTypes ); ?>">
				<?php
				foreach ( $this->postTypes as $post_type ) {
					printf(
						'<option value="%s" %s>%s</option>',
						esc_attr( $post_type->name ),
						selected( $this->value(), $post_type->name, false ),
						esc_html( $post_type->labels->name )
					);
				}
				?>

			</select>
		<?php endif;
	}
}
