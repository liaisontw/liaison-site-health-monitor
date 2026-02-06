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

    public static function get_object_cache_stats() {
        // 取得命中與未命中次數
        $hits   = function_exists( 'wp_cache_get_hits' ) ? wp_cache_get_hits() : 0;
        $misses = function_exists( 'wp_cache_get_misses' ) ? wp_cache_get_misses() : 0;
        $total  = $hits + $misses;

        // 防止除以零
        if ( $total === 0 ) {
            return '<span class="shm-status-warning">No Cache Activity</span>';
        }

        // 計算命中率
        $hit_ratio = round( ( $hits / $total ) * 100, 1 );

        // 判斷表現等級
        $class = 'shm-status-good';
        if ( $hit_ratio < 70 ) $class = 'shm-status-warning';
        if ( $hit_ratio < 40 ) $class = 'shm-status-bad';

        // 檢查是否有持久性快取 (如 Redis/Memcached)
        $is_persistent = wp_using_ext_object_cache() ? ' (Persistent)' : ' (Non-persistent)';

        // 在 get_object_cache_stats 內
        $bar_html = "
        <div style='width:100px; background:#eee; height:8px; border-radius:4px; display:inline-block; margin-right:5px; vertical-align:middle;'>
            <div style='width:{$hit_ratio}%; background:linear-gradient(90deg, #4caf50, #8bc34a); height:100%; border-radius:4px;'></div>
        </div>";

        return $bar_html . sprintf('<span class="%s">%s%%</span>', $class, $hit_ratio);

        // return sprintf( 
        //     '<span class="%s"><strong>%s%%</strong></span> <small>%s</small>', 
        //     $class, 
        //     $hit_ratio,
        //     $is_persistent
        // );
    }

    public static function get_stats() {

        if ( ! function_exists( 'wp_cache_get_stats' ) ) {
            return null;
        }

        $stats = wp_cache_get_stats();

        if ( empty( $stats ) || ! is_array( $stats ) ) {
            return null;
        }

        $hits   = (int) ( $stats['hits']   ?? 0 );
        $misses = (int) ( $stats['misses'] ?? 0 );

        $total = $hits + $misses;

        $hit_rate = $total > 0
            ? round( ( $hits / $total ) * 100, 2 )
            : 0;

        return [
            'hits'     => $hits,
            'misses'  => $misses,
            'hit_rate'=> $hit_rate,
        ];
    }

}

