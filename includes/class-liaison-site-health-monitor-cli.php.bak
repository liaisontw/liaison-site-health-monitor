<?php
/**
 * Site Health Audit CLI Command
 */
class Site_Health_Audit_CLI {

    /**
     * 執行效能審計並輸出報告
     *
     * ## OPTIONS
     *
     * [--format=<format>]
     * : 輸出格式，支援 table, json, csv, yaml。預設為 table。
     *
     * [--threshold=<threshold>]
     * : 慢查詢的閾值 (ms)。預設為 50。
     *
     * ## EXAMPLES
     *
     *     wp site-health audit --threshold=100
     *     wp site-health audit --format=json > report.json
     *
     * @when after_wp_load
     */

    public function audit( $args, $assoc_args ) {
        // 清理 Cache Stats 中的 HTML 標籤
        $cache_val = trim( strip_tags( LIAISIHM_Audit::get_object_cache_stats() ) );

        // 1. 系統指標 (這部分較短，維持表格或改用簡單 line)
        WP_CLI::line( WP_CLI::colorize( "%B--- System Health Metrics ---%n" ) );
        WP_CLI::line( "  PHP Memory Limit: " . ini_get('memory_limit') );
        WP_CLI::line( "  Current Usage:    " .  size_format( memory_get_usage() ) ); // 新增
        WP_CLI::line( "  Autoload Size:    " .  size_format( LIAISIHM_Audit::get_autoload_size() ) );
        WP_CLI::line( "  Cache Hit Ratio:  " .  $cache_val . '%' );
        WP_CLI::line( "  Object Cache:     " . (wp_using_ext_object_cache() ? 'External' : 'Internal') );
        WP_CLI::line( str_repeat('-', 40) );

        // 2. 慢查詢 (改用條列式)
        $slow_queries = LIAISIHM_DB::get_top_slow_queries();
        
        if ( empty( $slow_queries ) ) {
            WP_CLI::success( "No slow queries found." );
        } else {
            WP_CLI::line( WP_CLI::colorize( "%R--- Top Slow Queries (Detailed List) ---%n" ) );
            
            foreach ( $slow_queries as $index => $q ) {
                $q = (array) $q;
                $num = $index + 1;

                // 標題行：編號 + 時間 + Index 狀態
                $index_label = $q['has_index'] ? "%G[Indexed]%n" : "%R[No Index]%n";
                WP_CLI::line( WP_CLI::colorize( "%W{$num}.%n %Y[{$q['total_time_ms']} ms]%n {$index_label}" ) );

                // SQL 語句 (保持縮排，稍微清理換行符號讓它在 CLI 更好看)
                $clean_query = preg_replace( '/\s+/', ' ', trim( $q['query_text'] ) );
                WP_CLI::line( WP_CLI::colorize( "   %BQuery:%n " . substr( $clean_query, 0, 150 ) . ( strlen( $clean_query ) > 150 ? '...' : '' ) ) );

                // Endpoint
                WP_CLI::line( "   %BURL:%n   " . $q['request_uri'] );
                
                // 分隔線
                WP_CLI::line( str_repeat(' ', 3) . str_repeat('-', 20) );
            }

            WP_CLI::log( WP_CLI::colorize( "\n%I* Tip: Use '--format=json' for full normalized patterns and stack traces.%n" ) );
        }
    }

    // 這裡放入你原有的數據抓取邏輯 (如 get_autoload_size, get_slow_queries 等)
}

