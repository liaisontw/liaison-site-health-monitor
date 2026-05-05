<?php
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../includes/class-liaison-site-health-monitor-cli.php';

/**
 * Class Site_Health_Audit_CLI_Test
 */
class Site_Health_Audit_CLI_Test extends WP_UnitTestCase {

    private $cli;

    public function set_up() {
        parent::set_up();
        
        // 如果 WP_CLI 類別不存在（例如在單純的 PHPUnit 環境），手動定義一個 Mock
        if ( ! class_exists( 'WP_CLI' ) ) {
            class WP_CLI {
                public static function line( $text ) { echo $text . "\n"; }
                public static function colorize( $text ) { return $text; } // 測試環境不處理顏色代碼
                public static function success( $text ) { echo "Success: " . $text . "\n"; }
                public static function log( $text ) { echo $text . "\n"; }
            }
        }

        $this->cli = new Site_Health_Audit_CLI();
    }

    /**
     * 測試：當沒有慢查詢時的輸出
     */
    public function test_audit_outputs_success_when_no_slow_queries() {
        // 確保資料庫是空的，所以不會有慢查詢
        // 我們模擬呼叫 audit
        ob_start();
        $this->cli->audit([], []);
        $output = ob_get_clean();

        $this->assertStringContainsString('System Health Metrics', $output);
        // 因為是新安裝的測試環境，資料庫應該沒有慢查詢紀錄
        $this->assertStringContainsString('No slow queries found', $output);
    }

    /**
     * 測試：系統指標顯示
     */
    public function test_system_metrics_display() {
        ob_start();
        $this->cli->audit([], []);
        $output = ob_get_clean();

        $this->assertStringContainsString('PHP Memory Limit', $output);
        $this->assertStringContainsString('Object Cache', $output);
        $this->assertStringContainsString('Autoload Size', $output);
    }
}