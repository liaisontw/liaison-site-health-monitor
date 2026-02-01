<?php

/**
 * Fired during plugin activation
 *
 * @link       https://github.com/liaisontw
 * @since      1.0.0
 *
 * @package    liaison_site_health_monitor
 * @subpackage liaison_site_health_monitor/includes
 */

require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-shm-db.php';

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    liaison_site_health_monitor
 * @subpackage liaison_site_health_monitor/includes
 * @author     liason <liaison.tw@gmail.com>
 */
class LIAISIHM_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		SHM_DB::install();
		add_option( 'liaison_site_health_monitor_active', 'yes' );
	}

	/*
	protected static function _create_tables() {
		global $wpdb;
		
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table = SHM_DB::table_name();

		// set up DB name
		$table_name = $wpdb->wpsp_activity;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) DEFAULT NULL,
			action varchar(191) NOT NULL,
			object_type varchar(100) DEFAULT NULL,
			object_id bigint(20) DEFAULT NULL,
			description text DEFAULT NULL,
			ip varchar(45) DEFAULT NULL,
			user_agent text DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id)
		) $charset_collate;";

		dbDelta( $sql );
	}
		*/

}
