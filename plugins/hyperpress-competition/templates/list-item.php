<?php
/**
 * One competition in the competition list shortcode.
 *
 * Expects $hyperpress_post_id, the competition's post ID, in scope.
 */

// Don't load directly.
defined( 'ABSPATH' ) || exit;

$hyperpress_details   = \HyperPress\Competition\Details::for_post( $hyperpress_post_id );
$hyperpress_blurb     = \HyperPress\Competition\Details::blurb( $hyperpress_post_id );
$hyperpress_days      = \HyperPress\Competition\Schedule::days( $hyperpress_post_id );
$hyperpress_weeks     = get_the_terms( $hyperpress_post_id, \HyperPress\Competition\Plugin::WEEK );
$hyperpress_when      = implode(
	': ',
	array_filter(
		array(
			is_array( $hyperpress_weeks ) && array() !== $hyperpress_weeks ? $hyperpress_weeks[0]->name : '',
			array() === $hyperpress_days ? '' : \HyperPress\Competition\DateRange::format( $hyperpress_days[0]['start'], $hyperpress_days[ count( $hyperpress_days ) - 1 ]['start'] ),
		),
		static fn( string $part ): bool => '' !== $part
	)
);
$hyperpress_countdown = \HyperPress\Competition\Countdown::render( $hyperpress_post_id, false, false, false );
?>
<article class="competition-listing">
	<h2 class="entry-title"><a href="<?php echo esc_url( get_permalink( $hyperpress_post_id ) ); ?>" rel="bookmark"><?php echo get_the_title( $hyperpress_post_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Filtered post title, printed as WordPress core does. ?></a></h2>
	<?php if ( '' !== $hyperpress_when ) : ?>
		<h3 class="competition-when"><?php echo esc_html( $hyperpress_when ); ?></h3>
	<?php endif; ?>
	<?php if ( '' !== $hyperpress_details['venue'] ) : ?>
		<p class="competition-venue"><?php echo esc_html( $hyperpress_details['venue'] ); ?></p>
	<?php endif; ?>
	<?php if ( '' !== $hyperpress_details['address'] ) : ?>
		<p class="competition-address"><?php echo esc_html( $hyperpress_details['address'] ); ?></p>
	<?php endif; ?>
	<?php if ( '' !== $hyperpress_blurb ) : ?>
		<div class="competition-blurb"><?php echo $hyperpress_blurb; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Details::blurb() returns kses-filtered HTML. ?></div>
	<?php endif; ?>
	<?php echo $hyperpress_countdown; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Countdown::render() returns pre-escaped markup. ?>
	<?php require __DIR__ . '/links.php'; ?>
</article>
