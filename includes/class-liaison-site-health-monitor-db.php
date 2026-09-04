<?php
defined( 'ABSPATH' ) || exit;

class LIAISIHM_DB {

    /**
     * Returns the plugin-owned database table name.
     *
     * The returned value is constructed from $wpdb->prefix and a fixed suffix.
     * It never contains user-controlled input.
     */
    public static function table_name() {
        global $wpdb;

        return $wpdb->prefix . 'shm_query_log';
    }

    public static function install() {
        global $wpdb;

        $table = self::table_name();
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL auto_increment,
            query_hash char(32) NOT NULL,
            query_text longtext NOT NULL,
            total_time_ms float NOT NULL,
            call_stack text NULL,
            request_uri text NULL,
            created_at datetime NOT NULL,
            normalized text NULL,
            has_index tinyint(1) NULL,
            PRIMARY KEY  (id),
            KEY query_hash (query_hash),
            KEY created_at (created_at)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /*
    public static function install() {
        global $wpdb;

        $table = self::table_name();
        $charset = $wpdb->get_charset_collate();

        $sql = "
        CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL auto_increment,
            query_hash char(32) NOT NULL,
            query_text longtext NOT NULL,
            total_time_ms float NOT NULL,
            call_stack text NULL,
            request_uri text NULL,
            created_at datetime NOT NULL,
            normalized text NULL,
            has_index tinyint(1) NULL,
            PRIMARY KEY  (id),
            KEY query_hash (query_hash),
            KEY created_at (created_at)
        ) {$charset};
        ";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }
    */

    public static function insert( array $row ) {
        global $wpdb;

        $table_name = esc_sql( self::table_name() );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $wpdb->insert(
            $table_name,
            $row,
            [
                '%s', '%s', '%f', '%s', '%s', '%s'
            ]
        );
    }

    public static function insert_batch( array $data ) {
        global $wpdb;

        if ( empty( $data ) ) {
            return;
        }

        //$table_name = self::table_name();
        $table_name = esc_sql( self::table_name() );
        $values     = [];
        $placeholders = [];

        foreach ( $data as $row ) {
            // 依照欄位順序推入資料
            $values[] = $row['query_hash'];
            $values[] = $row['query_text'];
            $values[] = $row['total_time_ms'];
            $values[] = $row['call_stack'];
            $values[] = $row['request_uri'];
            $values[] = $row['created_at'];
            $values[] = $row['normalized'];
            $values[] = $row['has_index'];

            // 建立預處理預留位置
            $placeholders[] = "(%s, %s, %f, %s, %s, %s, %s, %s)";
        }

        // 將所有預留位置合併： "(...), (...), (...)"
        $query = "INSERT INTO $table_name 
                (query_hash, 
                 query_text, 
                 total_time_ms, 
                 call_stack, 
                 request_uri, 
                 created_at,
                 normalized,
                 has_index) 
                VALUES " . implode( ', ', $placeholders );

        // 透過 $wpdb->prepare 安全地執行
        //$sql = $wpdb->prepare( $query, ...$values );

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query prepared above.
        //return $wpdb->query( $sql );
    }

    public static function cleanup( $days = 7 ) {
        global $wpdb;

        $table_name = self::table_name(); 

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Deleting old historical records from plugin custom table.
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM " . esc_sql( $table_name ) . " WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
                $days
            )
        );

    }

    public static function getDB( ) {
        global $wpdb;

		$table_name = self::table_name(); 

        $liaisihm_cache_key  = 'getDB_' . $table_name ;
        $liaisihm_cache_group = 'liaison_site_health_group';
        $rows = wp_cache_get( $liaisihm_cache_key, $liaisihm_cache_group );

        if ( false === $rows ) {
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPreparedUse
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            $rows = $wpdb->get_results(
                "SELECT query_text, 
                        total_time_ms, 
                        request_uri, 
                        created_at,
                        normalized,
                        has_index
                FROM " . esc_sql( $table_name ) . "
                ORDER BY total_time_ms DESC
                LIMIT 10"
            );
            
            // 快取 12 小時（43200 秒）或依需求調整時間
            wp_cache_set( $liaisihm_cache_key, $rows, $liaisihm_cache_group, 12 * HOUR_IN_SECONDS );
        }

        return $rows;
    }

    /**
     * 獲取資料庫效能紀錄
     * * @return array|object 查詢結果
     */
    public static function get_top_slow_queries( $limit = 10 ) {
        global $wpdb;

        // 1. 使用 wpdb 方法獲取表名（假設你在類中定義了 table_name 方法）
        $table = self::table_name();

        // 2. 雖然此查詢無變數，但習慣上使用 prepare 增加一致性
        // 或是將 LIMIT 設為參數，增加方法的靈活性
        $liaisihm_cache_key  = 'result_' . $table ;
        $liaisihm_cache_group = 'liaison_site_health_group';
        $result = wp_cache_get( $liaisihm_cache_key, $liaisihm_cache_group );

        if ( false === $result ) {
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPreparedUse
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            $result = $wpdb->get_results( 
                $wpdb->prepare(
                    "SELECT query_text, 
                            total_time_ms, 
                            request_uri, 
                            created_at,
                            normalized,
                            has_index,
                            call_stack
                    FROM " . esc_sql( $table ) . "
                    ORDER BY total_time_ms DESC 
                    LIMIT %d",
                    $limit
                )
            );
            
            // 快取 12 小時（43200 秒）或依需求調整時間
            wp_cache_set( $liaisihm_cache_key, $result, $liaisihm_cache_group, 12 * HOUR_IN_SECONDS );
        }
        
        return $result;
    }

    public static function get_threshold() {
        // 從資料庫取得設定，預設為 50
        return (float) get_option( 'liaisihm_slow_query_threshold', 50 );
    }
}
