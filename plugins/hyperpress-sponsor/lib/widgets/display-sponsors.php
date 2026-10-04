<?php

namespace HYPER_Press_Sponsor;

use WP_Query;
use WP_Widget;

// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
    die( '-1' );
}


class HYPERpress_Display_Sponsors_Widget extends WP_Widget {

    private string $defaultTitle = 'Our Sponsors';
    private int $defaultMaxCount = 5;

    public function __construct() {
        parent::__construct(
                'hyperpress_display_sponsors_widget',
                __( 'hyperPress Current Sponsors', 'hyperpress' ),
                [ 'description' => __( 'Display sponsors for the current season', 'hyperpress' ) ]
        );
    }

    public function widget( $args, $instance ): void {
        $title = apply_filters( 'widget_title', $instance['title'] );


        echo $args['before_widget'];
        if ( ! empty( $title ) ) {
            echo $args['before_title'] . $title . $args['after_title'];
        }
        $currentSeasonSlug = get_theme_mod( 'hyperpress_season_current' );
        if ( ! empty( $currentSeasonSlug ) ) {
            $tiers = array_reduce( get_terms( [
                    'taxonomy'   => 'tier',
                    'orderby'    => 'slug',
                    'order'      => 'DESC',
                    'hide_empty' => true,
            ] ), function ( $carry, $tier ) {
                $carry[] = $tier->slug;

                return $carry;
            }, [] );
            // if there are tiers, query them
            if ( ! empty( $tiers ) ) {
                // create a query that is empty to merge the results into
                $the_query = new WP_Query( [ 'post__in' => [ 0 ] ] );

                while ( $the_query->post_count < $instance['maxCount'] && count( $tiers ) > 0 ) {
                    $tier                  = array_shift( $tiers );
                    $temp_query            = new WP_Query( [
                            'post_type'      => 'sponsor',
                        // only display Sponsors in the selected season
                            'tax_query'      => [
                                    'relation' => 'AND',
                                    [
                                            'taxonomy' => 'season',
                                            'field'    => 'slug',
                                            'terms'    => $currentSeasonSlug,
                                    ],
                                    [
                                            'taxonomy' => 'tier',
                                            'field'    => 'slug',
                                            'terms'    => [ $tier ]
                                    ]
                            ],
                        // only display Sponsors with a featured image to display
                            'meta_query'     => [ [ 'key' => '_thumbnail_id' ] ],
                            'posts_per_page' => $instance['maxCount'],
                            'orderby'        => 'order title',
                            'order'          => 'ASC',
                    ] );
                    $the_query->posts      = array_merge( $the_query->posts, $temp_query->posts );
                    $the_query->post_count = count( $the_query->posts );
                }

                if ( $the_query->have_posts() ) {
                    echo '<div class="grid-container fluid margin-bottom-2"><div class="grid-y grid-padding-y">';
                    while ( $the_query->have_posts() ) {
                        $the_query->the_post();
                        if ( has_post_thumbnail() ) {
                            echo '<div class="cell">';
                            echo '<a href="' . get_the_permalink() . '" title="' . get_the_title() . '">';
                            the_post_thumbnail( '', [ 'class' => 'full-width' ] );
                            echo '</a>';
                            echo '</div>';
                        }
                    }
                    echo '</div></div>';
                }
                $seasonsUrl = get_term_link( get_term_by( 'slug', $currentSeasonSlug, 'season' ) );

                echo '<a class="button neutral expanded small" href="' . $seasonsUrl . 'sponsors">See This Season\'s Sponsors</a>';

                if ( ! empty( $instance['becomeASponsorPage'] ) ) {
                    echo '<a class="button primary expanded small" href="' . get_page_link( $instance['becomeASponsorPage'] ) . '">Become A Sponsor</a>';
                }
            }
        }

        // Output widget content here
        echo $args['after_widget'];
    }

    public function form( $instance ): void {
        $title              = ! empty( $instance['title'] ) ? $instance['title'] : __( $this->defaultTitle, 'hyperpress' );
        $maxCount           = ! empty( $instance['maxCount'] ) ? $instance['maxCount'] : $this->defaultMaxCount;
        $becomeASponsorPage = ! empty( $instance['becomeASponsorPage'] ) ? $instance['becomeASponsorPage'] : '';


        // Widget admin form
        ?>
        <p>
            <label for="<?php echo $this->get_field_id( 'title' ); ?>">
                <?php _e( 'Title:', 'hyperpress' ); ?>
            </label>
            <input
                    class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>"
                    name="<?php echo $this->get_field_name( 'title' ); ?>"
                    type="text"
                    value="<?php echo esc_attr( $title ); ?>"
            />
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'maxCount' ); ?>">
                <?php _e( 'Max Count:', 'hyperpress' ); ?>
            </label>
            <input
                    class="widefat" id="<?php echo $this->get_field_id( 'maxCount' ); ?>"
                    name="<?php echo $this->get_field_name( 'maxCount' ); ?>"
                    type="number"
                    min="1"
                    value="<?php echo esc_attr( $maxCount ); ?>"
            />
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'becomeASponsorPage' ); ?>">
                <?php _e( 'Become a Sponsor Page:', 'hyperpress' ); ?>
            </label>
            <?php wp_dropdown_pages( [
                    'name'             => $this->get_field_name( 'becomeASponsorPage' ),
                    'id'               => $this->get_field_id( 'becomeASponsorPage' ),
                    'selected'         => $becomeASponsorPage,
                    'class'            => 'widefat',
                    'show_option_none' => __( 'Select a page', 'hyperpress' ),
                    'required'         => true
            ] ) ?>
        </p>
        <?php
    }

    public function update( $new_instance, $old_instance ): array {
        // Process widget options to be saved
        $instance                       = [];
        $instance['title']              = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
        $instance['maxCount']           = ( is_numeric( $new_instance['maxCount'] ) ) ? $new_instance['maxCount'] : $this->defaultMaxCount;
        $instance['selectedSeason']     = ( is_numeric( $new_instance['selectedSeason'] ) ) ? $new_instance['selectedSeason'] : '';
        $instance['seeAllSponsorsPage'] = ( is_numeric( $new_instance['seeAllSponsorsPage'] ) ) ? $new_instance['seeAllSponsorsPage'] : '';
        $instance['becomeASponsorPage'] = ( is_numeric( $new_instance['becomeASponsorPage'] ) ) ? $new_instance['becomeASponsorPage'] : '';


        return $instance;
    }
}