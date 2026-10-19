<?php
/**
 * Competition content template: full view on its own page, summary elsewhere.
 */

// Don't load directly.
defined( 'ABSPATH' ) || exit;

$hyperpress_full    = \HyperPress\Competition\Content::is_full_view();
$hyperpress_details = \HyperPress\Competition\Details::for_post( (int) get_the_ID() );
$hyperpress_when    = $hyperpress_details['when'];
$hyperpress_when_s  = $hyperpress_when instanceof \DateTimeImmutable
	? wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $hyperpress_when->getTimestamp() )
	: '';
?>

<header>
	<?php if ( ! $hyperpress_full ) : ?>
		<h2 class="entry-title">
			<a href="<?php echo esc_url( get_permalink() ); ?>" rel="bookmark"><?php the_title(); ?></a>
		</h2>
	<?php endif; ?>
	<?php do_action( 'hyperpress_labels' ); ?>
</header>
<div class="entry-content">
	<?php the_post_thumbnail( 'full', array( 'class' => 'thumbnail' ) ); ?>

	<?php if ( $hyperpress_full ) : ?>
		<?php echo \HyperPress\Competition\Countdown::render( (int) get_the_ID() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Countdown::render() returns pre-escaped markup. ?>
	<?php endif; ?>

	<?php if ( '' !== $hyperpress_when_s ) : ?>
		<p class="competition-date"><?php echo esc_html( $hyperpress_when_s ); ?></p>
	<?php endif; ?>
	<?php if ( '' !== $hyperpress_details['venue'] ) : ?>
		<p class="competition-venue"><?php echo esc_html( $hyperpress_details['venue'] ); ?></p>
	<?php endif; ?>
	<?php if ( '' !== $hyperpress_details['address'] ) : ?>
		<p class="competition-address"><?php echo esc_html( $hyperpress_details['address'] ); ?></p>
	<?php endif; ?>

	<?php if ( $hyperpress_full ) : ?>
		<?php if ( '' !== $hyperpress_details['host'] ) : ?>
			<p class="competition-host"><?php echo esc_html( $hyperpress_details['host'] ); ?></p>
		<?php endif; ?>
		<?php if ( ! empty( $hyperpress_details['links'] ) ) : ?>
			<ul class="competition-links">
				<?php foreach ( $hyperpress_details['links'] as $hyperpress_link ) : ?>
					<li><a href="<?php echo esc_url( $hyperpress_link['url'] ); ?>" target="<?php echo esc_attr( $hyperpress_link['target'] ); ?>" rel="noopener noreferrer"><?php echo esc_html( $hyperpress_link['label'] ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	<?php endif; ?>
</div>
