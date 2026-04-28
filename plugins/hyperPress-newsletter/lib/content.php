<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$category = get_the_terms( get_the_ID(), 'newsletter_season' );
?>

<header>
    <?php if ( is_single() ) : ?>
        <h1 class="entry-title">
            <?php echo ! empty( $category ) ? $category[0]->name . ': ' : ''; ?>
            <?php the_title(); ?>
        </h1>
    <?php else : ?>
        <h2 class="entry-title">
            <a href="<?php echo esc_url( get_permalink() ) ?>" rel="bookmark">
                <?php echo ! empty( $category ) ? $category[0]->name . ': ' : ''; ?>
                <?php the_title(); ?>
            </a>
        </h2>
    <?php endif; ?>
    <?php foundationpress_entry_meta(); ?>
</header>
<div class="entry-content">
    <?php $canva_url = get_post_custom_values( 'canva_newsletter_url', get_the_ID() )[0]; ?>
    <?php if ( ! empty( $canva_url ) ) : ?>
        <div style="position: relative; width: 100%; height: 0; padding-top: 129.4118%; padding-bottom: 0; box-shadow: 0 2px 8px 0 rgba(63,69,81,0.16); margin-top: 1.6em; margin-bottom: 0.9em; overflow: hidden; border-radius: 8px; will-change: transform;">
            <iframe loading="lazy"
                    style="position: absolute; width: 100%; height: 100%; top: 0; left: 0; border: none; padding: 0;margin: 0;"
                    src="<?php echo $canva_url; ?>?embed" allowfullscreen="allowfullscreen"
                    allow="fullscreen"></iframe>
        </div>
        <a href="<?php echo $canva_url; ?>" target="_blank" rel="noopener">View on Canva</a>
    <?php endif; ?>
</div>
