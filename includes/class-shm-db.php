<?php
defined( 'ABSPATH' ) || exit;

class SHM_DB {

    public static function table_name() {
        global $wpdb;
        //$wpdb->wpsp_activity = $wpdb->prefix . 'liaison_site_prober';
        return $wpdb->prefix . 'shm_query_log';
    }

    public static function install() {
        global $wpdb;

        $table = self::table_name();
        $charset = $wpdb->get_charset_collate();

        $sql = "
        CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            query_hash CHAR(32) NOT NULL,
            query_text LONGTEXT NOT NULL,
            total_time_ms FLOAT NOT NULL,
            call_stack TEXT NULL,
            request_uri TEXT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY query_hash (query_hash),
            KEY created_at (created_at)
        ) {$charset};
        ";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    public static function insert( array $row ) {
        global $wpdb;
        $wpdb->insert(
            self::table_name(),
            $row,
            [
                '%s', '%s', '%f', '%s', '%s', '%s'
            ]
        );
    }

    public static function cleanup( $days = 7 ) {
        global $wpdb;
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM " . self::table_name() . " WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
                $days
            )
        );
    }
}
