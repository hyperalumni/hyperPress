<?php
/**
 * Sponsor content template.
 */

// Don't load directly.
defined( 'ABSPATH' ) || exit;

// The grouped archive loop sets $hyperpress_title_tag to nest the entry title under its group headings.
$hyperpress_entry_tag = ( isset( $hyperpress_title_tag ) && in_array( $hyperpress_title_tag, array( 'h2', 'h3', 'h4', 'h5', 'h6' ), true ) ) ? $hyperpress_title_tag : 'h2';
?>
<header>
	<?php if ( ! is_singular( 'hyper_sponsor' ) ) : ?>
		<<?php echo esc_attr( $hyperpress_entry_tag ); ?> class="entry-title">
			<a href="<?php echo esc_url( get_permalink() ); ?>" rel="bookmark">
				<?php the_title(); ?>
			</a>
		</<?php echo esc_attr( $hyperpress_entry_tag ); ?>>
	<?php endif; ?>
	<?php do_action( 'hyperpress_labels' ); ?>
</header>
<div class="entry-content">
	<?php if ( has_post_thumbnail() ) : ?>
		<?php the_post_thumbnail( '', array( 'class' => 'thumbnail' ) ); ?>
	<?php endif; ?>

	<?php $sponsor_website = get_post_custom_values( 'hyperpress_sponsor_website', get_the_ID() )[0] ?? ''; ?>

	<?php if ( ! empty( $sponsor_website ) ) : ?>
		<?php
		$sponsor_target = \HyperPress\Utils\Link::target( $sponsor_website );
		$sponsor_rel    = \HyperPress\Utils\Link::rel( $sponsor_target );
		?>
		<p><strong>Website:</strong> <a href="<?php echo esc_url( $sponsor_website ); ?>" target="<?php echo esc_attr( $sponsor_target ); ?>"<?php echo '' !== $sponsor_rel ? ' rel="' . esc_attr( $sponsor_rel ) . '"' : ''; ?>><?php echo esc_url( $sponsor_website ); ?></a></p>
	<?php endif; ?>
</div>
