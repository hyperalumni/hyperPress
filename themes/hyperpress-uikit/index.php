<?php
/**
 * Main template: renders views/index.twig through Timber.
 *
 * @package hyperpress-uikit
 */

declare(strict_types=1);

if ( ! class_exists( 'Timber\Timber' ) ) {
	// Degrade gracefully: a missing vendor/ must not turn the site into an error page.
	status_header( 200 );
	?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
	<p><?php echo esc_html__( 'The theme vendor/ folder (Timber) is missing. Run nx build @hyper/press-uikit.', 'hyperpress-uikit' ); ?></p>
	<?php wp_footer(); ?>
</body>
</html>
	<?php
	return;
}

$hp_uikit_context          = Timber\Timber::context();
$hp_uikit_context['posts'] = Timber\Timber::get_posts();

Timber\Timber::render( 'index.twig', $hp_uikit_context );
