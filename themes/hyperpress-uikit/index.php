<?php
/**
 * Main template: renders views/index.twig through Timber.
 *
 * @package hyperpress-uikit
 */

declare(strict_types=1);

if ( ! class_exists( 'Timber\Timber' ) ) {
	wp_die( esc_html__( 'Timber is not installed. Run composer install in the theme.', 'hyperpress-uikit' ) );
}

$hp_uikit_context          = Timber\Timber::context();
$hp_uikit_context['posts'] = Timber\Timber::get_posts();

Timber\Timber::render( 'index.twig', $hp_uikit_context );
