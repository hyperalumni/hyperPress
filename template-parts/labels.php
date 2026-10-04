<?php
// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

$labels = apply_filters( 'hyperpress_labels_content', [] );
if ( ! empty( $labels ) ) :
	?>
	<div class="entry-meta">
		<div class="grid-x">
			<?php foreach ( $labels as $label ) : ?>
				<div class="cell shrink label <?php echo esc_attr( implode( ' ', $label['classes'] ) ); ?>">
					<?php if ( ! empty( $label['url'] ) ) : ?>
					<a href="<?php echo esc_url( $label['url'] ); ?>" rel="<?php echo esc_attr( $label['rel'] ); ?>">
						<?php endif; ?>
						<span title="<?php echo esc_attr( $label['title'] ); ?>">
							<?php echo esc_html( $label['label'] ); ?>
						</span>
						<?php if ( ! empty( $label['url'] ) ) : ?>
					</a>
				<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
<?php endif; ?>
