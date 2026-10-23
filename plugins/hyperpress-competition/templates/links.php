<?php
/**
 * Competition link buttons, grouped into event info and live streams.
 *
 * Expects $hyperpress_details, the result of Details::for_post(), in scope.
 */

// Don't load directly.
defined( 'ABSPATH' ) || exit;

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
						<a class="button<?php echo 'live' === $hyperpress_group ? ' alert' : ''; ?> small-down-expanded" href="<?php echo esc_url( $hyperpress_link['url'] ); ?>" target="<?php echo esc_attr( $hyperpress_link['target'] ); ?>"<?php echo '' !== $hyperpress_link['rel'] ? ' rel="' . esc_attr( $hyperpress_link['rel'] ) . '"' : ''; ?>><?php echo esc_html( $hyperpress_link['label'] ); ?><?php echo 'live' === $hyperpress_group ? ' ' . $hyperpress_play_icon : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static markup defined above. ?></a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		<?php endforeach; ?>
	</div>
<?php endif; ?>
