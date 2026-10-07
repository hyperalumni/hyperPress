<?php
/**
 * Label list markup. Receives `$labels` from Renderer::render().
 *
 * @package HyperPress\Label
 */

// Don't load directly.
defined( 'ABSPATH' ) || exit;

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
