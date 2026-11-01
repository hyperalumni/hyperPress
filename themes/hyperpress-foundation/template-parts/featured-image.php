<?php
// Don't load directly.
defined( 'ABSPATH' ) || exit;
?>

<?php
// If a featured image is set, insert into layout and use Interchange
// to select the optimal image size per named media query.
if ( get_post() && has_post_thumbnail() ) :
?>
	<?php get_template_part( 'template-parts/hero', null, array( 'attachment_id' => get_post_thumbnail_id() ) ); ?>
<?php
endif;
