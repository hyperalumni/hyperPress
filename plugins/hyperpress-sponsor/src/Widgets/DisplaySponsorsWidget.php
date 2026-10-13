<?php
namespace HyperPress\Sponsor\Widgets;

defined( 'ABSPATH' ) || exit;

use HyperPress\Sponsor\Plugin;
use WP_Query;
use WP_Widget;

class DisplaySponsorsWidget extends WP_Widget {

	private int $default_max_count = 5;
	private int $max_count_limit   = 100;

	public function __construct() {
		parent::__construct(
			'hyperpress_display_sponsors_widget',
			__( 'hyperPress Current Sponsors', 'hyperpress' ),
			array( 'description' => __( 'Display sponsors for the current season', 'hyperpress' ) )
		);
	}

	public function widget( $args, $instance ): void {
		$title     = apply_filters( 'widget_title', $instance['title'] ?? '' );
		$max_count = $this->max_count( $instance['maxCount'] ?? null );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- sidebar wrappers are trusted markup from register_sidebar(), printed unescaped by core widgets too
		echo $args['before_widget'];
		if ( ! empty( $title ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- sidebar wrappers are trusted markup from register_sidebar(), printed unescaped by core widgets too
			echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
		}
		$current_season_slug = class_exists( '\HyperPress\Season\Settings' ) ? \HyperPress\Season\Settings::current() : '';
		if ( ! empty( $current_season_slug ) ) {
			$tier_terms = get_terms(
				array(
					'taxonomy'   => Plugin::TIER,
					'orderby'    => 'slug',
					'order'      => 'DESC',
					'hide_empty' => true,
				)
			);
			$tiers      = is_wp_error( $tier_terms ) ? array() : wp_list_pluck( $tier_terms, 'slug' );
			// If there are tiers, query them.
			if ( ! empty( $tiers ) ) {
				// Create a query that is empty to merge the results into.
				$the_query = new WP_Query( array( 'post__in' => array( 0 ) ) );

				while ( $the_query->post_count < $max_count && ! empty( $tiers ) ) {
					$tier       = array_shift( $tiers );
					$temp_query = new WP_Query(
						array(
							'post_type'      => Plugin::POST_TYPE,
							// Only display Sponsors in the selected season.
							// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- small, bounded sponsor set; no cheaper way to filter by tier and thumbnail
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
							// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- small, bounded sponsor set; no cheaper way to filter by tier and thumbnail
							'meta_query'     => array( array( 'key' => '_thumbnail_id' ) ),
							// Only fetch what still fits, so the merged result never exceeds the maximum.
							'posts_per_page' => $max_count - $the_query->post_count,
							'orderby'        => 'menu_order title',
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
							echo '<a href="' . esc_url( get_the_permalink() ) . '" title="' . esc_attr( get_the_title() ) . '">';
							the_post_thumbnail( '', array( 'class' => 'full-width' ) );
							echo '</a>';
							echo '</div>';
						}
					}
					echo '</div></div>';
					wp_reset_postdata();
				}

				$seasons_url = $this->all_sponsors_url( (string) $current_season_slug );
				if ( '' !== $seasons_url ) {
					echo '<a class="button neutral expanded small" href="' . esc_url( $seasons_url ) . '">See This Season\'s Sponsors</a>';
				}

				if ( ! empty( $instance['becomeASponsorPage'] ) ) {
					echo '<a class="button primary expanded small" href="' . esc_url( get_page_link( $instance['becomeASponsorPage'] ) ) . '">Become A Sponsor</a>';
				}
			}
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- sidebar wrappers are trusted markup from register_sidebar(), printed unescaped by core widgets too
		echo $args['after_widget'];
	}

	/**
	 * The "see all sponsors" URL for a season, or an empty string when the season or its link is unavailable.
	 *
	 * @param string $season_slug Slug of the current season term.
	 *
	 * @return string
	 */
	private function all_sponsors_url( string $season_slug ): string {
		$season = get_term_by( 'slug', $season_slug, 'hyper_season' );
		if ( ! $season ) {
			return '';
		}
		$term_link = get_term_link( $season );
		if ( is_wp_error( $term_link ) ) {
			return '';
		}
		if ( get_option( 'permalink_structure' ) ) {
			$archive = Plugin::definition()['archive'];

			return trailingslashit( $term_link ) . $archive;
		}

		return add_query_arg( 'post_type', Plugin::POST_TYPE, $term_link );
	}

	private function max_count( $value ): int {
		if ( ! is_numeric( $value ) ) {
			return $this->default_max_count;
		}

		return min( $this->max_count_limit, max( 1, (int) $value ) );
	}

	public function form( $instance ): void {
		$title                 = ! empty( $instance['title'] ) ? $instance['title'] : __( 'Our Sponsors', 'hyperpress' );
		$max_count             = ! empty( $instance['maxCount'] ) ? $instance['maxCount'] : $this->default_max_count;
		$become_a_sponsor_page = ! empty( $instance['becomeASponsorPage'] ) ? $instance['becomeASponsorPage'] : '';

		// Widget admin form.
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
				<?php esc_html_e( 'Title:', 'hyperpress' ); ?>
			</label>
			<input
					class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
					name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
					type="text"
					value="<?php echo esc_attr( $title ); ?>"
			/>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'maxCount' ) ); ?>">
				<?php esc_html_e( 'Max Count:', 'hyperpress' ); ?>
			</label>
			<input
					class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'maxCount' ) ); ?>"
					name="<?php echo esc_attr( $this->get_field_name( 'maxCount' ) ); ?>"
					type="number"
					min="1"
					value="<?php echo esc_attr( $max_count ); ?>"
			/>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'becomeASponsorPage' ) ); ?>">
				<?php esc_html_e( 'Become a Sponsor Page:', 'hyperpress' ); ?>
			</label>
			<?php
			wp_dropdown_pages(
				array(
					'name'             => esc_attr( $this->get_field_name( 'becomeASponsorPage' ) ),
					'id'               => esc_attr( $this->get_field_id( 'becomeASponsorPage' ) ),
					'selected'         => absint( $become_a_sponsor_page ),
					'class'            => 'widefat',
					'show_option_none' => esc_html__( 'Select a page', 'hyperpress' ),
					'required'         => true,
				)
			)
			?>
		</p>
		<?php
	}

	public function update( $new_instance, $old_instance ): array {
		// Process widget options to be saved.
		$instance                       = array();
		$instance['title']              = ( ! empty( $new_instance['title'] ) ) ? wp_strip_all_tags( $new_instance['title'] ) : '';
		$instance['maxCount']           = $this->max_count( $new_instance['maxCount'] ?? null );
		$become_a_sponsor_page          = $new_instance['becomeASponsorPage'] ?? '';
		$instance['becomeASponsorPage'] = is_numeric( $become_a_sponsor_page ) ? $become_a_sponsor_page : '';

		return $instance;
	}
}
