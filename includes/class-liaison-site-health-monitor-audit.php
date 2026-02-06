<?php
defined( 'ABSPATH' ) || exit;


class LIAISIHM_Audit {


    /**
     * 檢查 OPCache 狀態
     */
    public static function get_opcache_status() {
        if ( ! function_exists( 'opcache_get_status' ) ) {
            return '<span class="shm-status-bad">Disabled (Extension missing)</span>';
        }

        $status = opcache_get_status( false );
        if ( $status && $status['opcache_enabled'] ) {
            return '<span class="shm-status-good">Enabled</span>';
        }

        return '<span class="shm-status-bad">Disabled</span>';
    }

    /**
     * 獲取 MySQL 引擎與版本
     */
    public static function get_mysql_info() {
        global $wpdb;
        $version = $wpdb->db_version();
        
        // 檢查核心資料表是否使用 InnoDB
        $table_status = $wpdb->get_row( $wpdb->prepare( "SHOW TABLE STATUS LIKE %s", $wpdb->prefix . 'posts' ) );
        $engine = isset( $table_status->Engine ) ? $table_status->Engine : 'Unknown';

        $class = ( $engine === 'InnoDB' ) ? 'shm-status-good' : 'shm-status-warning';
        return "<code>$version</code> / <span class='$class'>$engine</span>";
    }

    /**
     * 計算 Autoload Options 大小
     */
    public static function get_autoload_size() {
        global $wpdb;
        $size_bytes = $wpdb->get_var( 
            "SELECT SUM(LENGTH(option_value)) 
             FROM $wpdb->options WHERE autoload = 'yes'" );
        $size_kb = round( $size_bytes / 1024, 2 );
        
        $class = ( $size_kb > 1024 ) ? 'shm-status-bad' : 'shm-status-good';
        $warning = ( $size_kb > 1024 ) ? ' <span class="dashicons dashicons-warning" title="Heavy autoload impact!"></span>' : '';
        
        return "<span class='$class'>{$size_kb} KB</span>{$warning}";
    }

    public static function get_autoload_stats( $limit = 10 ) {
        global $wpdb;

        $table = $wpdb->options;

        $rows = $wpdb->get_results(
            "
            SELECT 
                option_name,
                LENGTH(option_value) AS size_bytes
            FROM {$table}
            WHERE autoload = 'yes'
            ORDER BY size_bytes DESC
            LIMIT {$limit}
            ",
            ARRAY_A
        );

        $total = (int) $wpdb->get_var(
            "
            SELECT SUM(LENGTH(option_value))
            FROM {$table}
            WHERE autoload = 'yes'
            "
        );

        return [
            'total_bytes' => $total,
            'largest'     => $rows,
        ];
    }

    public static function format_bytes( $bytes ) {
        if ( $bytes > 1024 * 1024 ) {
            return round( $bytes / 1024 / 1024, 2 ) . ' MB';
        }
        if ( $bytes > 1024 ) {
            return round( $bytes / 1024, 2 ) . ' KB';
        }
        return $bytes . ' B';
    }

}

