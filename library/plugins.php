<?php
/**
 * This file represents an example of the code that themes would use to register
 * the required plugins.
 *
 * It is expected that theme authors would copy and paste this code into their
 * functions.php file, and amend to suit.
 *
 * @see http://tgmpluginactivation.com/configuration/ for detailed documentation.
 *
 * @package    TGM-Plugin-Activation
 * @subpackage Example
 * @version    2.6.1 for parent theme hyperPress
 * @author     Thomas Griffin, Gary Jones, Juliette Reinders Folmer
 * @copyright  Copyright (c) 2011, Thomas Griffin
 * @license    http://opensource.org/licenses/gpl-2.0.php GPL v2 or later
 * @link       https://github.com/TGMPA/TGM-Plugin-Activation
 */

/**
 * Include the TGM_Plugin_Activation class.
 *
 * Depending on your implementation, you may want to change the include call:
 *
 * Parent Theme:
 * require_once get_template_directory() . '/path/to/class-tgm-plugin-activation.php';
 *
 * Child Theme:
 * require_once get_stylesheet_directory() . '/path/to/class-tgm-plugin-activation.php';
 *
 * Plugin:
 * require_once dirname( __FILE__ ) . '/path/to/class-tgm-plugin-activation.php';
 */
require_once get_template_directory() . '/library/class-tgm-plugin-activation.php';

add_action( 'tgmpa_register', 'hyper_register_required_plugins' );

/**
 * Register the required plugins for this theme.
 *
 * In this example, we register five plugins:
 * - one included with the TGMPA library
 * - two from an external source, one from an arbitrary source, one from a GitHub repository
 * - two from the .org repo, where one demonstrates the use of the `is_callable` argument
 *
 * The variables passed to the `tgmpa()` function should be:
 * - an array of plugin arrays;
 * - optionally a configuration array.
 * If you are not changing anything in the configuration array, you can remove the array and remove the
 * variable from the function call: `tgmpa( $plugins );`.
 * In that case, the TGMPA default settings will be used.
 *
 * This function is hooked into `tgmpa_register`, which is fired on the WP `init` action on priority 10.
 */
function hyper_register_required_plugins() {
	/*
	 * Array of plugin arrays. Required keys are name and slug.
	 * If the source is NOT from the .org repo, then source is also required.
	 */
	$plugins = array(
		array(
			'name'     => 'Meta Box AIO',
			'slug'     => 'meta-box-aio',
			'source'   => 'https://metabox.io/?action=download&product=meta-box-aio&api_key=ccf94cf80c2441f839c7528ea9dea71e',
			'required' => true
		),
		array(
			'name'     => 'Embed Optimizer',
			'slug'     => 'embed-optimizer',
			'required' => true
		),
		array(
			'name'     => 'Enhanced Responsive Images',
			'slug'     => 'auto-sizes',
			'required' => true
		),
		array(
			'name'     => 'Envato Market',
			'slug'     => 'envato-market',
			'source'   => 'https://envato.github.io/wp-envato-market/dist/envato-market.zip',
			'required' => false
		),
		array(
			'name'     => 'Health Check & Troubleshooting',
			'slug'     => 'health-check',
			'required' => true
		),
		array(
			'name'     => 'Heartbeat Control',
			'slug'     => 'heartbeat-control',
			'required' => false
		),
		array(
			'name'     => 'hyperPress Countdown',
			'slug'     => 'hyperPress-countdown',
			'source'   => '#',
			'required' => true
		),
		array(
			'name'     => 'hyperPress Socials',
			'slug'     => 'hyperPress-socials',
			'source'   => '#',
			'required' => true
		),
		array(
			'name'     => 'hyperPress Newsletter',
			'slug'     => 'hyperPress-newsletter',
			'source'   => '#',
			'required' => false
		),
		array(
			'name'     => 'Image Placeholders',
			'slug'     => 'dominant-color-images',
			'required' => true
		),
		array(
			'name'     => 'Image Prioritizer',
			'slug'     => 'image-prioritizer',
			'required' => true
		),
		array(
			'name'     => 'Instant Back/Forward',
			'slug'     => 'nocache-bfcache',
			'required' => true
		),
		array(
			'name'     => 'Jetpack',
			'slug'     => 'jetpack',
			'required' => false
		),
		array(
			'name'     => 'Modern Image Formats',
			'slug'     => 'webp-uploads',
			'required' => true
		),
		array(
			'name'     => 'NextGEN Gallery',
			'slug'     => 'nextgen-gallery',
			'required' => false
		),
		array(
			'name'     => 'Nginx Helper',
			'slug'     => 'nginx-helper',
			'required' => false
		),
		array(
			'name'     => 'Ninja Forms',
			'slug'     => 'ninja-forms',
			'required' => true
		),
		array(
			'name'     => 'Optimization Detective',
			'slug'     => 'optimization-detective',
			'required' => true
		),
		array(
			'name'     => 'Performance Lab',
			'slug'     => 'performance-lab',
			'required' => true
		),
		array(
			'name'     => 'Post SMTP',
			'slug'     => 'post-smtp',
			'required' => true
		),
		array(
			'name'     => 'PublishPress Capabilities',
			'slug'     => 'capability-manager-enhanced',
			'required' => false
		),
		array(
			'name'     => 'PublishPress Revisions',
			'slug'     => 'revisionary',
			'required' => false
		),
		array(
			'name'     => 'Redirection',
			'slug'     => 'redirection',
			'required' => true
		),
		array(
			'name'     => 'RunCloud Hub',
			'slug'     => 'runcloud-hub',
			'source'   => 'https://runcloud-hub.s3.us-west-1.amazonaws.com/production/runcloud-hub-1.4.8.zip',
			'required' => true
		),
		array(
			'name'     => 'Simple CAPTCHA Alternative with Cloudflare Turnstile',
			'slug'     => 'simple-cloudflare-turnstile',
			'required' => true
		),
		array(
			'name'     => 'Slider Revolution',
			'slug'     => 'revslider',
			'source'   => 'https://sliderrevolution.com',
			'required' => true
		),
		array(
			'name'     => 'Web Worker Offloading',
			'slug'     => 'web-worker-offloading',
			'required' => true
		),
		array(
			'name'     => 'Wordfence Security',
			'slug'     => 'wordfence',
			'required' => true
		),
		array(
			'name'     => 'WP-Optimize',
			'slug'     => 'wp-optimize',
			'required' => true
		)
	);

	/*
	 * Array of configuration settings. Amend each line as needed.
	 *
	 * TGMPA will start providing localized text strings soon. If you already have translations of our standard
	 * strings available, please help us make TGMPA even better by giving us access to these translations or by
	 * sending in a pull-request with .po file(s) with the translations.
	 *
	 * Only uncomment the strings in the config array if you want to customize the strings.
	 */
	$config = array(
		'id'           => 'hyper',
		// Unique ID for hashing notices for multiple instances of TGMPA.
		'default_path' => '',
		// Default absolute path to bundled plugins.
		'menu'         => 'tgmpa-install-plugins',
		// Menu slug.
		'parent_slug'  => 'themes.php',
		// Parent menu slug.
		'capability'   => 'edit_theme_options',
		// Capability needed to view plugin install page, should be a capability associated with the parent menu used.
		'has_notices'  => true,
		// Show admin notices or not.
		'dismissable'  => true,
		// If false, a user cannot dismiss the nag message.
		'dismiss_msg'  => '',
		// If 'dismissable' is false, this message will be output at top of nag.
		'is_automatic' => false,
		// Automatically activate plugins after installation or not.
		'message'      => '',
		// Message to output right before the plugins table.

		/*
		'strings'      => array(
			'page_title'                      => __( 'Install Required Plugins', 'hyper' ),
			'menu_title'                      => __( 'Install Plugins', 'hyper' ),
			/* translators: %s: plugin name. * /
			'installing'                      => __( 'Installing Plugin: %s', 'hyper' ),
			/* translators: %s: plugin name. * /
			'updating'                        => __( 'Updating Plugin: %s', 'hyper' ),
			'oops'                            => __( 'Something went wrong with the plugin API.', 'hyper' ),
			'notice_can_install_required'     => _n_noop(
				/* translators: 1: plugin name(s). * /
				'This theme requires the following plugin: %1$s.',
				'This theme requires the following plugins: %1$s.',
				'hyper'
			),
			'notice_can_install_recommended'  => _n_noop(
				/* translators: 1: plugin name(s). * /
				'This theme recommends the following plugin: %1$s.',
				'This theme recommends the following plugins: %1$s.',
				'hyper'
			),
			'notice_ask_to_update'            => _n_noop(
				/* translators: 1: plugin name(s). * /
				'The following plugin needs to be updated to its latest version to ensure maximum compatibility with this theme: %1$s.',
				'The following plugins need to be updated to their latest version to ensure maximum compatibility with this theme: %1$s.',
				'hyper'
			),
			'notice_ask_to_update_maybe'      => _n_noop(
				/* translators: 1: plugin name(s). * /
				'There is an update available for: %1$s.',
				'There are updates available for the following plugins: %1$s.',
				'hyper'
			),
			'notice_can_activate_required'    => _n_noop(
				/* translators: 1: plugin name(s). * /
				'The following required plugin is currently inactive: %1$s.',
				'The following required plugins are currently inactive: %1$s.',
				'hyper'
			),
			'notice_can_activate_recommended' => _n_noop(
				/* translators: 1: plugin name(s). * /
				'The following recommended plugin is currently inactive: %1$s.',
				'The following recommended plugins are currently inactive: %1$s.',
				'hyper'
			),
			'install_link'                    => _n_noop(
				'Begin installing plugin',
				'Begin installing plugins',
				'hyper'
			),
			'update_link' 					  => _n_noop(
				'Begin updating plugin',
				'Begin updating plugins',
				'hyper'
			),
			'activate_link'                   => _n_noop(
				'Begin activating plugin',
				'Begin activating plugins',
				'hyper'
			),
			'return'                          => __( 'Return to Required Plugins Installer', 'hyper' ),
			'plugin_activated'                => __( 'Plugin activated successfully.', 'hyper' ),
			'activated_successfully'          => __( 'The following plugin was activated successfully:', 'hyper' ),
			/* translators: 1: plugin name. * /
			'plugin_already_active'           => __( 'No action taken. Plugin %1$s was already active.', 'hyper' ),
			/* translators: 1: plugin name. * /
			'plugin_needs_higher_version'     => __( 'Plugin not activated. A higher version of %s is needed for this theme. Please update the plugin.', 'hyper' ),
			/* translators: 1: dashboard link. * /
			'complete'                        => __( 'All plugins installed and activated successfully. %1$s', 'hyper' ),
			'dismiss'                         => __( 'Dismiss this notice', 'hyper' ),
			'notice_cannot_install_activate'  => __( 'There are one or more required or recommended plugins to install, update or activate.', 'hyper' ),
			'contact_admin'                   => __( 'Please contact the administrator of this site for help.', 'hyper' ),

			'nag_type'                        => '', // Determines admin notice type - can only be one of the typical WP notice classes, such as 'updated', 'update-nag', 'notice-warning', 'notice-info' or 'error'. Some of which may not work as expected in older WP versions.
		),
		*/
	);

	tgmpa( $plugins, $config );
}
