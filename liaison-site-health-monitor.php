<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://github.com/liaisontw
 * @since             1.0.0
 * @package           liaison_site_health_monitor
 *
 * @wordpress-plugin
 * Plugin Name:       liaison site health monitor
 * Plugin URI:        https://github.com/liaisontw/liaison-site-health-monitor
 * Description:       This is a description of the plugin.
 * Version:           1.0.0
 * Author:            liason
 * Author URI:        https://github.com/liaisontw/
 * License: 		  GPLv3 or later  
 * License URI: 	  https://www.gnu.org/licenses/gpl-3.0.html  
 * Text Domain:       liaison-site-health-monitor
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'liaison_site_health_monitor_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-liaison-site-health-monitor-activator.php
 */
function liaisihm_activate_liaison_site_health_monitor() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-liaison-site-health-monitor-activator.php';
	LIAISIHM_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-liaison-site-health-monitor-deactivator.php
 */
function liaisihm_deactivate_liaison_site_health_monitor() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-liaison-site-health-monitor-deactivator.php';
	LIAISIHM_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'liaisihm_activate_liaison_site_health_monitor' );
register_deactivation_hook( __FILE__, 'liaisihm_deactivate_liaison_site_health_monitor' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-liaison-site-health-monitor.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function liaisihm_run_liaison_site_health_monitor() {

	$plugin = new LIAISIHM();
	$plugin->run();

}
liaisihm_run_liaison_site_health_monitor();
