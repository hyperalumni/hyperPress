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
		<?php
		$hyperpress_groups    = array(
			'info' => __( 'Event Info', 'hyperpress' ),
			'live' => __( 'Watch Live', 'hyperpress' ),
		);
		$hyperpress_play_icon = '<i class="fa-solid fa-circle-play" aria-hidden="true"></i>';
		$hyperpress_by        = array(
			'info' => array(),
			'live' => array(),
		);
		foreach ( $hyperpress_details['links'] as $hyperpress_link ) {
			$hyperpress_by[ 'live' === $hyperpress_link['group'] ? 'live' : 'info' ][] = $hyperpress_link;
		}
		?>
		<?php if ( ! empty( $hyperpress_details['links'] ) ) : ?>
			<div class="competition-links">
				<?php foreach ( $hyperpress_groups as $hyperpress_group => $hyperpress_group_label ) : ?>
					<?php if ( ! empty( $hyperpress_by[ $hyperpress_group ] ) ) : ?>
						<h6 class="subheader"><?php echo esc_html( $hyperpress_group_label ); ?></h6>
						<div class="competition-links-group competition-links-<?php echo esc_attr( $hyperpress_group ); ?>">
							<?php foreach ( $hyperpress_by[ $hyperpress_group ] as $hyperpress_link ) : ?>
								<a class="button<?php echo 'live' === $hyperpress_group ? ' alert' : ''; ?>" href="<?php echo esc_url( $hyperpress_link['url'] ); ?>" target="<?php echo esc_attr( $hyperpress_link['target'] ); ?>"<?php echo '' !== $hyperpress_link['rel'] ? ' rel="' . esc_attr( $hyperpress_link['rel'] ) . '"' : ''; ?>><?php echo esc_html( $hyperpress_link['label'] ); ?><?php echo 'live' === $hyperpress_group ? ' ' . $hyperpress_play_icon : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static markup defined above. ?></a>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	<?php endif; ?>
</div>
