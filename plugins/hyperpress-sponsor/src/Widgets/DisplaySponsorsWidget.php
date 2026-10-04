<?php
namespace HyperPress\Sponsor\Widgets;

defined( 'ABSPATH' ) || exit;

use HyperPress\Sponsor\Plugin;
use WP_Query;
use WP_Widget;

class DisplaySponsorsWidget extends WP_Widget {

	private string $default_title  = 'Our Sponsors';
	private int $default_max_count = 5;

	public function __construct() {
		parent::__construct(
			'hyperpress_display_sponsors_widget',
			__( 'hyperPress Current Sponsors', 'hyperpress' ),
			array( 'description' => __( 'Display sponsors for the current season', 'hyperpress' ) )
		);
	}

	public function widget( $args, $instance ): void {
		$title = apply_filters( 'widget_title', $instance['title'] );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plugins audit
		echo $args['before_widget'];
		if ( ! empty( $title ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plugins audit
			echo $args['before_title'] . $title . $args['after_title'];
		}
		$current_season_slug = get_theme_mod( 'hyperpress_season_current' );
		if ( ! empty( $current_season_slug ) ) {
			$tiers = array_reduce(
				get_terms(
					array(
						'taxonomy'   => Plugin::TIER,
						'orderby'    => 'slug',
						'order'      => 'DESC',
						'hide_empty' => true,
					)
				),
				function ( $carry, $tier ) {
					$carry[] = $tier->slug;

					return $carry;
				},
				array()
			);
			// If there are tiers, query them.
			if ( ! empty( $tiers ) ) {
				// Create a query that is empty to merge the results into.
				$the_query = new WP_Query( array( 'post__in' => array( 0 ) ) );

				while ( $the_query->post_count < $instance['maxCount'] && ! empty( $tiers ) ) {
					$tier       = array_shift( $tiers );
					$temp_query = new WP_Query(
						array(
							'post_type'      => Plugin::POST_TYPE,
							// Only display Sponsors in the selected season.
							// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- plugins audit
							'tax_query'      => array(
								'relation' => 'AND',
								array(
									'taxonomy' => 'hyper_season',
									'field'    => 'slug',
									'terms'    => $current_season_slug,
								),
								array(
									'taxonomy' => Plugin::TIER,
									'field'    => 'slug',
									'terms'    => array( $tier ),
								),
							),
							// Only display Sponsors with a featured image to display.
							// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- plugins audit
							'meta_query'     => array( array( 'key' => '_thumbnail_id' ) ),
							'posts_per_page' => $instance['maxCount'],
							'orderby'        => 'order title',
							'order'          => 'ASC',
						)
					);
					$the_query->posts      = array_merge( $the_query->posts, $temp_query->posts );
					$the_query->post_count = count( $the_query->posts );
				}

				if ( $the_query->have_posts() ) {
					echo '<div class="grid-container fluid margin-bottom-2"><div class="grid-y grid-padding-y">';
					while ( $the_query->have_posts() ) {
						$the_query->the_post();
						if ( has_post_thumbnail() ) {
							echo '<div class="cell">';
							// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plugins audit
							echo '<a href="' . get_the_permalink() . '" title="' . get_the_title() . '">';
							the_post_thumbnail( '', array( 'class' => 'full-width' ) );
							echo '</a>';
							echo '</div>';
						}
					}
					echo '</div></div>';
				}
				$seasons_url = get_term_link( get_term_by( 'slug', $current_season_slug, 'hyper_season' ) );

				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plugins audit
				echo '<a class="button neutral expanded small" href="' . $seasons_url . 'sponsors">See This Season\'s Sponsors</a>';

				if ( ! empty( $instance['becomeASponsorPage'] ) ) {
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plugins audit
					echo '<a class="button primary expanded small" href="' . get_page_link( $instance['becomeASponsorPage'] ) . '">Become A Sponsor</a>';
				}
			}
		}

		// Output widget content here.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plugins audit
		echo $args['after_widget'];
	}

	public function form( $instance ): void {
		// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- plugins audit
		$title                 = ! empty( $instance['title'] ) ? $instance['title'] : __( $this->default_title, 'hyperpress' );
		$max_count             = ! empty( $instance['maxCount'] ) ? $instance['maxCount'] : $this->default_max_count;
		$become_a_sponsor_page = ! empty( $instance['becomeASponsorPage'] ) ? $instance['becomeASponsorPage'] : '';

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
			<label for="<?php echo $this->get_field_id( 'maxCount' ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plugins audit */ ?>">
				<?php /* phpcs:ignore WordPress.Security.EscapeOutput.UnsafePrintingFunction -- plugins audit */ _e( 'Max Count:', 'hyperpress' ); ?>
			</label>
			<input
					class="widefat" id="<?php echo $this->get_field_id( 'maxCount' ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plugins audit */ ?>"
					name="<?php echo $this->get_field_name( 'maxCount' ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plugins audit */ ?>"
					type="number"
					min="1"
					value="<?php echo esc_attr( $max_count ); ?>"
			/>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'becomeASponsorPage' ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plugins audit */ ?>">
				<?php /* phpcs:ignore WordPress.Security.EscapeOutput.UnsafePrintingFunction -- plugins audit */ _e( 'Become a Sponsor Page:', 'hyperpress' ); ?>
			</label>
			<?php
			wp_dropdown_pages(
				array(
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plugins audit
					'name'             => $this->get_field_name( 'becomeASponsorPage' ),
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plugins audit
					'id'               => $this->get_field_id( 'becomeASponsorPage' ),
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plugins audit
					'selected'         => $become_a_sponsor_page,
					'class'            => 'widefat',
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plugins audit
					'show_option_none' => __( 'Select a page', 'hyperpress' ),
					'required'         => true,
				)
			)
			?>
		</p>
		<?php
	}

	public function update( $new_instance, $old_instance ): array {
		// Process widget options to be saved.
		$instance = array();
		// phpcs:ignore WordPress.WP.AlternativeFunctions.strip_tags_strip_tags -- plugins audit
		$instance['title']              = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['maxCount']           = ( is_numeric( $new_instance['maxCount'] ) ) ? $new_instance['maxCount'] : $this->default_max_count;
		$instance['selectedSeason']     = ( is_numeric( $new_instance['selectedSeason'] ) ) ? $new_instance['selectedSeason'] : '';
		$instance['seeAllSponsorsPage'] = ( is_numeric( $new_instance['seeAllSponsorsPage'] ) ) ? $new_instance['seeAllSponsorsPage'] : '';
		$instance['becomeASponsorPage'] = ( is_numeric( $new_instance['becomeASponsorPage'] ) ) ? $new_instance['becomeASponsorPage'] : '';

		return $instance;
	}
}
