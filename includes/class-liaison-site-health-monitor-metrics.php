<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://github.com/liaisontw
 * @since      1.0.0
 *
 * @package    liaison_site_health_monitor
 * @subpackage liaison_site_health_monitor/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    liaison_site_health_monitor
 * @subpackage liaison_site_health_monitor/includes
 * @author     liason <liaison.tw@gmail.com>
 */
class LIAISIHM_metrics {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      liaison_site_health_monitor_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		;
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - liaison_site_health_monitor_Loader. Orchestrates the hooks of the plugin.
	 * - liaison_site_health_monitor_i18n. Defines internationalization functionality.
	 * - liaison_site_health_monitor_Admin. Defines all hooks for the admin area.
	 * - liaison_site_health_monitor_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	
	public static function memory_peak() {
        return memory_get_peak_usage( true );
    }

    public static function wp_version() {
        global $wp_version;
        return $wp_version;
    }
	
	/**
	 * Memory usage
	 */
	public function shm_get_memory_usage() {
		return round( memory_get_usage(true) / 1024 / 1024, 2 );
	}

	/**
	 * DB query time
	 */
	public function shm_get_db_query_time() {
		global $wpdb;

		if ( empty( $wpdb->queries ) ) {
			return 0;
		}

		$total = 0;

		foreach ( $wpdb->queries as $query ) {
			$total += $query[1]; // execution time
		}

		return round( $total * 1000, 2 ); // ms
	}

	/**
	 * Active plugin count
	 */
	public function shm_get_active_plugins_count() {
		$plugins = get_option( 'active_plugins', [] );
		return count( $plugins );
	}

	/**
	 * REST response test
	 */
	public function shm_get_rest_response_time() {

		$start = microtime(true);

		wp_remote_get( rest_url() );

		$end = microtime(true);

		return round( ($end - $start) * 1000, 2 );
	}

}
