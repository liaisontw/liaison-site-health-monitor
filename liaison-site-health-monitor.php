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


function liaisihm_define_SAVEQUERIES() {
    if (   current_user_can('manage_options') 
		//&& isset($_GET['debug_performance']) 
	) {
    	// 1. 定義常數（為了讓後續程式碼邏輯統一）
        if ( ! defined( 'SAVEQUERIES' ) ) {
            define( 'SAVEQUERIES', true );
        }

        // 2. 關鍵：直接介入 $wpdb 物件，強制開啟監聽
        global $wpdb;
        $wpdb->save_queries = true;
	}
}

function liaisihm_early_savequeries_trigger() {
    // 注意：此時 current_user_can 尚未完全穩定
    // 建議改用檢查 Cookie 或特定 Debug 參數
    if ( ! defined( 'SAVEQUERIES' ) ) {
            define( 'SAVEQUERIES', true );
        }

        // 2. 關鍵：直接介入 $wpdb 物件，強制開啟監聽
        global $wpdb;
        $wpdb->save_queries = true;
	
	// if ( is_user_logged_in() && current_user_can('manage_options') ) {
    //     global $wpdb;
    //     $wpdb->save_queries = true;
    // }
}

// 不要包在任何 function 裡，直接在檔案載入時就掛上 hooks
add_action( 'plugins_loaded', 'liaisihm_force_enable_profiling', 1 );

function liaisihm_force_enable_profiling() {
    // 技巧：因為 current_user_can 可能太晚，
    // 在 plugins_loaded 階段，我們通常檢查 cookie 或是否在後台
    if ( is_admin() && is_user_logged_in() ) {
        global $wpdb;
        $wpdb->save_queries = true;
        
        // 既然啟動了記錄，現在就立刻初始化 Profiler，不要等 admin_menu
        LIAISIHM_Query_Profiler::init();
    }
}
function liaisihm_activate_liaison_site_health_monitor() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-liaison-site-health-monitor-activator.php';
	LIAISIHM_Activator::activate();

	//add_action( 'init', 'liaisihm_define_SAVEQUERIES' );
	// 使用 plugins_loaded 會比 init 早很多
	//add_action( 'plugins_loaded', 'liaisihm_early_savequeries_trigger' );
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
