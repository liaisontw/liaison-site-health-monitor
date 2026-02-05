<?php
defined( 'ABSPATH' ) || exit;

class LIAISIHM_Query_Profiler {

    const SLOW_QUERY_THRESHOLD_MS = 10;
    const RETENTION_DAYS = 7;
    private static $patterns = [];

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
        return defined( 'SAVEQUERIES' ) && SAVEQUERIES;
    }
    
    public static function collect_and_store_queries() {
        global $wpdb;

        if ( empty( $wpdb->queries ) ) {
            return;
        }

        error_log( 'queries count=' . ( is_array( $wpdb->queries ) ? count( $wpdb->queries ) : 'not array' ) );
 
        $slow_queries = [];
        $current_time = current_time( 'mysql' );
        $request_uri  = $_SERVER['REQUEST_URI'] ?? '';
        // 動態取得使用者設定的門檻
        $threshold_ms = LIAISIHM_DB::get_threshold();

        foreach ( $wpdb->queries as $query ) {
            // 安全解構：WP 核心格式有時會包含多個元素（如執行起始時間）
            $sql    = $query[0] ?? '';
            $time   = $query[1] ?? 0;

            $time_ms = $time * 1000;
            if ( $time_ms < $threshold_ms ) {
                continue;
            }

            $normalized = self::normalize_sql( $sql );
            /* * 關鍵時刻：在此處呼叫回溯
            * 因為這條查詢已經被判定為「慢」，
            * 此時花費幾毫秒來獲取堆疊資訊是完全合理的「診斷成本」。
            */
            $stack  = $query[2] ?? '';
            $stack = self::get_simplified_backtrace(); 

            $slow_queries[] = [
                'query_hash'    => md5( $sql ),
                'query_text'    => $sql,
                'total_time_ms' => $time_ms,
                'call_stack'    => is_string( $stack ) ? $stack : '',
                //'call_stack'      => $stack_trace, // 存入堆疊
                'request_uri'   => $request_uri,
                'created_at'    => $current_time,
                'normalized'    => $normalized,
                'has_index'     => self::has_index( $sql ),
            ];

            // === N+1 Pattern Tracking ===
            if ( ! isset( self::$patterns[ $normalized ] ) ) {
                self::$patterns[ $normalized ] = [
                    'count' => 0,
                    'time'  => 0,
                ];
            }

            self::$patterns[ $normalized ]['count']++;
            self::$patterns[ $normalized ]['time'] += $time;
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

    private static function get_simplified_backtrace() {
        $trace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS );
        $stack = [];

        foreach ( $trace as $node ) {
            // 排除掉我們自己的插件檔案與 WordPress 核心資料庫處理檔案，直達病灶
            if ( isset( $node['file'] ) && 
                strpos( $node['file'], 'wp-db.php' ) === false && 
                strpos( $node['file'], 'class-shm-wpdb.php' ) === false ) {
                
                $file = str_replace( ABSPATH, '', $node['file'] ); // 簡化路徑，隱藏伺服器隱私
                $stack[] = "{$file}:{$node['line']} ({$node['function']})";
            }
            
            // 只取前 5 層，避免儲存負擔過大
            if ( count( $stack ) >= 5 ) break;
        }

        return implode( "\n", $stack );
    }
    

    private static function save_batch( array $data ) {
        // 在這裡呼叫 LIAISIHM_DB::insert_batch
        // 展現「防禦性開發」：暫時暫停 Profiling，防止記錄自己的寫入動作
        remove_action( 'shutdown', [ __CLASS__, 'collect_and_store_queries' ], 999 );
        
        LIAISIHM_DB::insert_batch( $data );
    }

    private static function normalize_sql( $sql ) {
        $sql = preg_replace( '/\'[^\']*\'/', '?', $sql );
        $sql = preg_replace( '/\b\d+\b/', '?', $sql );
        return trim( $sql );
    }

    private static function has_index( $sql ) {
        global $wpdb;

        if ( stripos( $sql, 'select' ) !== 0 ) {
            return null;
        }

        $explain = $wpdb->get_results( 'EXPLAIN ' . $sql, ARRAY_A );

        foreach ( (array) $explain as $row ) {
            if ( empty( $row['key'] ) ) {
                return false;
            }
        }
        return true;
    }

    /*
    將 collect_and_store_queries 設為 static（靜態方法）主要
    有以下三個層次的考量：

    1. 生命周期與記憶體效率 (Lifecycle & Efficiency)
    這個方法是在 shutdown 鉤子觸發時執行的。
    非靜態方法：你必須先實例化一個物件
    （$profiler = new LIAISIHM_Query_Profiler();），這會佔用額外的記憶體。

    靜態方法：直接透過類別名稱呼叫
    （LIAISIHM_Query_Profiler::collect_and_store_queries）。

    監控工具的最高準則是 「低存在感 (Low Overhead)」。
    使用靜態方法可以省去物件實例化的開銷，確保監控動作對頁面效能的影響降到最低。

    2. 符合 WordPress Hook 的呼叫慣例
    WordPress 的 add_action 接收回呼函式（Callback）的方式，靜態方法非常簡潔：

    PHP
    // 靜態方法掛載方式 (簡潔、不需管理物件實例)
    add_action( 'shutdown', [ 'LIAISIHM_Query_Profiler', 'collect_and_store_queries' ] );

    // 非靜態方法掛載方式 (你必須確保該物件在 shutdown 時還活著)
    $my_instance = new LIAISIHM_Query_Profiler();
    add_action( 'shutdown', [ $my_instance, 'collect_and_store_queries' ] );
    在處理全域事件（如頁面結束時的數據收集）時，我們不需要維護「物件的狀態」
    （State），我們只需要執行「一段邏輯」。這種 「無狀態 (Stateless)」 的操作最適合用靜態方法。

    3. 避免「多重實例化」的數據污染
    如果這個類別不是靜態的，開發者可能會不小心 new 了好幾次。

    風險：如果有多個實例都在監聽 shutdown，你的慢查詢可能會被重複寫入資料庫好幾次。

    解決方案：使用靜態方法搭配 init() 模式，能確保這個邏輯在整個 WordPress 執行過程中是唯一且確定的單點進入。

    什麼時候「不該」用靜態方法？
    如果你以後想讓這個 Profiler 支援多種不同的儲存後端（例如有的存資料庫，有的存 Redis），那你就會需要用到「非靜態方法」與「介面 (Interface)」，因為那時候你需要根據不同狀況去「實例化」不同的對象。

    資深 SE 的總結： 在開發 WordPress 核心相關功能時，靜態方法（Static Methods） 常用於 Utility（工具類） 或 Global Event Handlers（全域事件處理器）。這展現了你對「程式碼意圖」的清晰定義：
    這是一個全域服務，而不是一個需要被多次複製的物件。  

    「我選擇使用靜態方法實作收集器，是為了最小化監控工具的記憶體足跡
    （Memory Footprint），並確保在 WordPress 的 shutdown 
    階段能以無狀態的方式穩定執行，避免不必要的物件生命週期管理開銷。」
    想嘗試把原本的 const 門檻移除，改由 LIAISIHM_DB::get_threshold() 
    動態帶入嗎？這樣你的 static 方法就會變得更有靈活性。
    */
}
