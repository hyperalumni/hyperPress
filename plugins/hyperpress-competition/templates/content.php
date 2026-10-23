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

	<?php
	// The full view prints the date and venue inside the countdown; without a countdown (no start date, or the
	// countdown plugin is off) and in the summary they are plain paragraphs.
	$hyperpress_countdown = $hyperpress_full
		? \HyperPress\Competition\Countdown::render( (int) get_the_ID(), true, true )
		: '';
	echo $hyperpress_countdown; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Countdown::render() returns pre-escaped markup.
	?>

	<?php if ( '' === $hyperpress_countdown ) : ?>
		<?php if ( '' !== $hyperpress_when_s ) : ?>
			<p class="competition-date"><?php echo esc_html( $hyperpress_when_s ); ?></p>
		<?php endif; ?>
		<?php if ( '' !== $hyperpress_details['venue'] ) : ?>
			<p class="competition-venue"><?php echo esc_html( $hyperpress_details['venue'] ); ?></p>
		<?php endif; ?>
	<?php endif; ?>
	<?php if ( '' !== $hyperpress_details['address'] ) : ?>
		<p class="competition-address"><?php echo $hyperpress_full ? '<strong>' . esc_html__( 'Address', 'hyperpress' ) . ':</strong> ' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Label escaped by esc_html__(). ?><?php echo esc_html( $hyperpress_details['address'] ); ?></p>
	<?php endif; ?>

	<?php if ( $hyperpress_full ) : ?>
		<?php if ( '' !== $hyperpress_details['host'] ) : ?>
			<p class="competition-host"><strong><?php esc_html_e( 'Hosted by', 'hyperpress' ); ?>:</strong> <?php echo esc_html( $hyperpress_details['host'] ); ?></p>
		<?php endif; ?>
		<?php if ( '' !== $hyperpress_details['blurb'] ) : ?>
			<div class="competition-blurb"><?php echo $hyperpress_details['blurb']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Details::for_post() returns kses-filtered HTML. ?></div>
		<?php endif; ?>
		<?php if ( count( $hyperpress_details['days'] ) >= 2 ) : ?>
			<?php
			$hyperpress_day_count = count( $hyperpress_details['days'] );
			$hyperpress_columns   = \HyperPress\Competition\Schedule::columns( $hyperpress_day_count );
			$hyperpress_date_fmt  = $hyperpress_day_count >= 4 ? 'D, M j' : 'l, F j';
			?>
			<div class="competition-schedule-block">
				<p class="competition-schedule-label"><strong><?php esc_html_e( 'Schedule', 'hyperpress' ); ?>:</strong></p>
				<div class="competition-schedule grid-x grid-margin-x align-center small-up-<?php echo (int) $hyperpress_columns['small']; ?> medium-up-<?php echo (int) $hyperpress_columns['medium']; ?> large-up-<?php echo (int) $hyperpress_columns['large']; ?>">
					<?php foreach ( $hyperpress_details['days'] as $hyperpress_day ) : ?>
						<?php
						$hyperpress_card = 'current' === $hyperpress_day['state'] ? ' primary" aria-current="true"' : '"';
						if ( 'past' === $hyperpress_day['state'] ) {
							$hyperpress_card = ' faded"';
						}
						?>
						<div class="cell"><div class="callout text-center<?php echo $hyperpress_card; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed attribute strings chosen above. ?>><?php echo '' !== $hyperpress_day['label'] ? '<h6>' . esc_html( $hyperpress_day['label'] ) . '</h6>' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Label escaped by esc_html(). ?><div><strong><?php echo esc_html( wp_date( $hyperpress_date_fmt, $hyperpress_day['start']->getTimestamp(), wp_timezone() ) ); ?></strong></div><div><?php echo esc_html( wp_date( 'g:i A', $hyperpress_day['start']->getTimestamp(), wp_timezone() ) ); ?></div></div></div>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endif; ?>
		<?php include __DIR__ . '/links.php'; ?>
	<?php endif; ?>
</div>
