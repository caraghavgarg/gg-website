<?php
/**
 * Plugin Name:       GG Site
 * Description:       Content types, calculators, due-date calendar, Acts & Rules hub, enquiry form and one-click setup for the GG Associates website. Use with the GG Theme.
 * Version:           2.6.0
 * Author:            Garg Garg & Associates
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * License:           GPL-2.0-or-later
 * Text Domain:       gg-site
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GGS_VER', '2.6.0' );
define( 'GGS_DIR', plugin_dir_path( __FILE__ ) );
define( 'GGS_URL', plugin_dir_url( __FILE__ ) );

require_once GGS_DIR . 'includes/content-types.php';
require_once GGS_DIR . 'includes/shortcodes.php';
require_once GGS_DIR . 'includes/enquiry.php';
require_once GGS_DIR . 'includes/settings.php';
require_once GGS_DIR . 'includes/setup.php';
require_once GGS_DIR . 'includes/hardening.php';
require_once GGS_DIR . 'includes/updater.php';
require_once GGS_DIR . 'includes/structures.php';
require_once GGS_DIR . 'includes/services-pages.php';
require_once GGS_DIR . 'includes/backup.php';

register_activation_hook( __FILE__, function () {
	ggs_register_content_types();
	flush_rewrite_rules();
} );
register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );

/** Calculators script: loaded only on pages that use a calculator. */
add_action( 'wp_enqueue_scripts', function () {
	wp_register_script( 'gg-calculators', GGS_URL . 'assets/gg-calculators.js', array(), GGS_VER, true );
} );

/** Hub data (Acts & Rules, forms, links) shipped with the plugin. */
function ggs_hub_data() {
	static $data = null;
	if ( null === $data ) {
		$raw  = file_get_contents( GGS_DIR . 'data/hub.json' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		$data = json_decode( $raw, true );
		if ( ! is_array( $data ) ) {
			$data = array( 'order' => array(), 'pages' => array() );
		}
	}
	return $data;
}
