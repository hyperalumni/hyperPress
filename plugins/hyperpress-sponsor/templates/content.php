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
		<div class="margin-bottom-2"><?php the_post_thumbnail(); ?></div>
	<?php endif; ?>

	<?php
	$sponsor_description = get_post_custom_values( 'hyperpress_sponsor_description', get_the_ID() )[0] ?? '';
	$sponsor_has_more    = false;
	if ( '' !== trim( $sponsor_description ) ) :
		$sponsor_parts    = get_extended( $sponsor_description );
		$sponsor_text     = is_singular( 'hyper_sponsor' ) ? trim( $sponsor_parts['main'] . "\n\n" . $sponsor_parts['extended'] ) : $sponsor_parts['main'];
		$sponsor_has_more = ! is_singular( 'hyper_sponsor' ) && '' !== trim( $sponsor_parts['extended'] );
		?>
		<div class="sponsor-description">
			<?php echo wp_kses_post( wpautop( $sponsor_text ) ); ?>
		</div>
	<?php endif; ?>

	<?php
	// A Meta Box link array (url, title); sponsors saved before the field became a link hold a plain URL string.
	$sponsor_link  = get_post_meta( get_the_ID(), 'hyperpress_sponsor_website', true );
	$sponsor_link  = is_array( $sponsor_link ) ? $sponsor_link : array( 'url' => $sponsor_link );
	$sponsor_raw   = is_string( $sponsor_link['url'] ?? null ) ? trim( $sponsor_link['url'] ) : '';
	$sponsor_url   = esc_url_raw( $sponsor_raw );
	$sponsor_label = is_string( $sponsor_link['title'] ?? null ) ? trim( $sponsor_link['title'] ) : '';
	// Meta Box fills the title with the URL when none is typed.
	if ( 0 === strcasecmp( $sponsor_label, $sponsor_raw ) || 0 === strcasecmp( $sponsor_label, $sponsor_url ) ) {
		$sponsor_label = '';
	}
	?>

	<?php if ( $sponsor_has_more || '' !== $sponsor_url ) : ?>
		<p class="sponsor-links">
			<?php if ( $sponsor_has_more ) : ?>
				<a class="button neutral small-down-expanded text-capitalize" href="<?php echo esc_url( get_permalink() ); ?>"><?php echo esc_html__( 'Learn more about our sponsor', 'hyperpress' ); ?></a>
			<?php endif; ?>
			<?php if ( '' !== $sponsor_url ) : ?>
				<?php
				$sponsor_target = \HyperPress\Utils\Link::target( $sponsor_url );
				$sponsor_rel    = \HyperPress\Utils\Link::rel( $sponsor_target );
				?>
				<a class="button small-down-expanded text-capitalize" href="<?php echo esc_url( $sponsor_url ); ?>" target="<?php echo esc_attr( $sponsor_target ); ?>"<?php echo '' !== $sponsor_rel ? ' rel="' . esc_attr( $sponsor_rel ) . '"' : ''; ?>><?php echo '' !== $sponsor_label ? esc_html( $sponsor_label ) : esc_html__( 'Visit our sponsor', 'hyperpress' ); ?></a>
			<?php endif; ?>
		</p>
	<?php endif; ?>
</div>
