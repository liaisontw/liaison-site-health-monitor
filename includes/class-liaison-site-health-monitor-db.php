<?php
defined( 'ABSPATH' ) || exit;

class LIAISIHM_DB {

    public static function table_name() {
        global $wpdb;

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

    public static function insert_batch( array $data ) {
        global $wpdb;

        if ( empty( $data ) ) {
            return;
        }

        $table_name = self::table_name();
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

            // 建立預處理預留位置 (s, s, d, s, s, s)
            $placeholders[] = "(%s, %s, %f, %s, %s, %s)";
        }

        // 將所有預留位置合併： "(...), (...), (...)"
        $query = "INSERT INTO $table_name 
                (query_hash, query_text, total_time_ms, call_stack, request_uri, created_at) 
                VALUES " . implode( ', ', $placeholders );

        // 透過 $wpdb->prepare 安全地執行
        return $wpdb->query( $wpdb->prepare( $query, $values ) );
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

    public static function getDB( ) {
        global $wpdb;

		$rows = $wpdb->get_results(
			"SELECT query_text, total_time_ms, request_uri, created_at
			FROM " . self::table_name() . "
			ORDER BY total_time_ms DESC
			LIMIT 10"
		);

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
    $query = $wpdb->prepare(
        "SELECT query_text, total_time_ms, request_uri, created_at 
         FROM {$table} 
         ORDER BY total_time_ms DESC 
         LIMIT %d",
        $limit
    );

    return $wpdb->get_results( $query );
}
}
