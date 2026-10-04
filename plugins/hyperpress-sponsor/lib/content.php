<?php
namespace HYPER_Press_Sponsor;

// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
    die( '-1' );
}
?>
<header>
    <?php if ( ! is_singular( 'sponsor' ) ) : ?>
        <h2 class="entry-title">
            <a href="<?php echo esc_url( get_permalink() ) ?>" rel="bookmark">
                <?php the_title(); ?>
            </a>
        </h2>
    <?php endif; ?>
    <?php get_template_part( 'template-parts/labels' ); ?>
</header>
<div class="entry-content">
    <?php if ( has_post_thumbnail() ) : ?>
        <?php the_post_thumbnail( '', array( 'class' => 'thumbnail' ) ); ?>
    <?php endif; ?>

    <?php $sponsor_website = get_post_custom_values( 'sponsor_website', get_the_ID() )[0]; ?>

    <?php if ( ! empty( $sponsor_website ) ) : ?>
        <p><strong>Website:</strong> <a href="<?php echo esc_url( $sponsor_website ); ?>" target="_blank"
                                        rel="noopener"><?php echo esc_url( $sponsor_website ); ?></a></p>
    <?php endif; ?>
</div>