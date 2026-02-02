<?php
defined( 'ABSPATH' ) || exit;

class LIAISIHM_Query_Profiler {

    const SLOW_QUERY_THRESHOLD_MS = 50;
    const RETENTION_DAYS = 7;

    public static function init() {
        // 展現全局思維：除了檢查常數，還應檢查當前用戶權限或環境
        // 避免在 Production 對所有用戶開啟，除非是特定除錯需求
        if ( ! self::should_profile() ) {
            return;
        }

        // 這裡只負責掛上收集器的 hook
        // 使用 999 確保在所有事情都做完後才收集
        add_action( 'shutdown', [ __CLASS__, 'collect_and_store_queries' ], 999 );
    }

    private static function should_profile() {
        error_log( 'SAVEQUERIES=' . ( defined('SAVEQUERIES') ? ( SAVEQUERIES ? 'true' : 'false' ) : 'undefined' ) );

        return defined( 'SAVEQUERIES' ) && SAVEQUERIES;
    }

    public static function collect_and_store_queries() {
        global $wpdb;

        if ( empty( $wpdb->queries ) ) {
            return;
        }
        //error_log( 'SAVEQUERIES=' . ( defined('SAVEQUERIES') ? ( SAVEQUERIES ? 'true' : 'false' ) : 'undefined' ) );
        error_log( 'queries count=' . ( is_array( $wpdb->queries ) ? count( $wpdb->queries ) : 'not array' ) );

        $slow_queries = [];
        $current_time = current_time( 'mysql' );
        $request_uri  = $_SERVER['REQUEST_URI'] ?? '';

        foreach ( $wpdb->queries as $query ) {
            // 安全解構：WP 核心格式有時會包含多個元素（如執行起始時間）
            $sql    = $query[0] ?? '';
            $time   = $query[1] ?? 0;
            $stack  = $query[2] ?? '';

            $time_ms = $time * 1000;

            if ( $time_ms < self::SLOW_QUERY_THRESHOLD_MS ) {
                continue;
            }

            $slow_queries[] = [
                'query_hash'    => md5( $sql ),
                'query_text'    => $sql,
                'total_time_ms' => $time_ms,
                'call_stack'    => is_string( $stack ) ? $stack : '',
                'request_uri'   => $request_uri,
                'created_at'    => $current_time,
            ];
        }

        if ( ! empty( $slow_queries ) ) {
            // 工程品質：使用一次性寫入，減少 DB 連接開銷
            self::save_batch( $slow_queries );
        }

        // 展現維運思維：清理工作不應每次頁面加載都執行
        // 建議使用 WP-Cron，或至少隨機觸發（Lottery approach）
        if ( rand( 1, 100 ) === 1 ) {
            LIAISIHM_DB::cleanup( self::RETENTION_DAYS );
        }
    }

    private static function save_batch( array $data ) {
        // 在這裡呼叫 LIAISIHM_DB::insert_batch
        // 展現「防禦性開發」：暫時暫停 Profiling，防止記錄自己的寫入動作
        remove_action( 'shutdown', [ __CLASS__, 'collect_and_store_queries' ], 999 );
        
        LIAISIHM_DB::insert_batch( $data );
    }
}
