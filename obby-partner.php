<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://example.com
 * @since             1.0.0
 * @package           Obby_Partner
 *
 * @wordpress-plugin
 * Plugin Name:       Obby Partner
 * Plugin URI:        https://obby.co.uk/partners
 * Description:       Sync your courses automatically with Obby!
 * Version:           1.0.5
 * Author:            Obby
 * Author URI:        https://obby.co.uk
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       obby-partner
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-obby-partner-activator.php
 */
function activate_obby_partner() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-obby-partner-activator.php';
	Obby_Partner_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-obby-partner-deactivator.php
 */
function deactivate_obby_partner() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-obby-partner-deactivator.php';
	Obby_Partner_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_obby_partner' );
register_deactivation_hook( __FILE__, 'deactivate_obby_partner' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-obby-partner.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_obby_partner() {

	$plugin = new Obby_Partner();
	$plugin->run();

}
run_obby_partner();
