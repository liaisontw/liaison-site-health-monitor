<?php
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../includes/class-liaison-site-health-monitor-cli.php';

/**
 * 測試 Site_Health_Audit_CLI
 */
class Site_Health_Audit_CLI_Test extends TestCase {

    private $cli;

    public function setUp(): void {
        parent::setUp();
        $this->cli = new Site_Health_Audit_CLI();
        
        // 確保 WP_CLI 環境存在（如果在非 WP-CLI 環境執行 PHPUnit 需要定義這個）
        if ( ! class_exists( 'WP_CLI' ) ) {
            class_object_proxy('WP_CLI', ['line', 'colorize', 'success', 'log']);
        }
    }

    /**
     * 測試當沒有慢查詢時的輸出
     */
    public function test_audit_outputs_success_when_no_slow_queries() {
        // 使用 Brain/Monkey 或類似工具 Mock 靜態類別行為
        // 這裡假設你已經有對應的 mock 機制處理 LIAISIHM_DB
        // 模擬 get_top_slow_queries 回傳空陣列
        
        ob_start();
        $this->cli->audit([], []);
        $output = ob_get_clean();

        $this->assertStringContainsString('System Health Metrics', $output);
        $this->assertStringContainsString('No slow queries found', $output);
    }

    /**
     * 測試當有慢查詢時，輸出是否包含關鍵資訊（URL, Time, Query）
     */
    public function test_audit_displays_slow_queries_details() {
        // 模擬一筆慢查詢數據
        $mock_query = (object)[
            'total_time_ms' => 120.5,
            'has_index'     => false,
            'query_text'    => 'SELECT * FROM wp_posts WHERE post_content LIKE "%heavy%"',
            'request_uri'   => '/wp-admin/admin.php?page=test-plugin'
        ];

        // 這裡需要透過 Mockery 或攔截 LIAISIHM_DB 讓它回傳 [$mock_query]
        
        ob_start();
        $this->cli->audit([], []);
        $output = ob_get_clean();

        // 驗證標題與時間
        $this->assertStringContainsString('120.5 ms', $output);
        $this->assertStringContainsString('[No Index]', $output);
        
        // 驗證 URL
        $this->assertStringContainsString('/wp-admin/admin.php?page=test-plugin', $output);
        
        // 驗證 Query 內容
        $this->assertStringContainsString('SELECT * FROM wp_posts', $output);
    }

    /**
     * 測試記憶體限制與快取狀態顯示
     */
    public function test_system_metrics_display() {
        ob_start();
        $this->cli->audit([], []);
        $output = ob_get_clean();

        $this->assertStringContainsString('PHP Memory Limit', $output);
        $this->assertStringContainsString('Object Cache', $output);
    }
}