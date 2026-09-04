<?php
/**
 * Class LIAISIHM_Query_Profiler_Test
 *
 * @package Liaison_Site_Health_Monitor
 */
class LIAISIHM_Query_Profiler_Test extends WP_UnitTestCase {

	public function setUp(): void {
		parent::setUp();
		wp_cache_flush();
	}

	public function tearDown(): void {
		// 重置私有 static 屬性 patterns
		$reflection = new ReflectionClass( 'LIAISIHM_Query_Profiler' );
		$property   = $reflection->getProperty( 'patterns' );
		$property->setAccessible( true );
		$property->setValue( null, [] );

		delete_option( 'liaisihm_slow_query_threshold' );
		parent::tearDown();
	}

	/**
	 * 測試當 SAVEQUERIES 未定義或為 false 時不註冊 shutdown hook
	 */
	public function test_init_when_savequeries_disabled() {
		// 確保清理可能已存在的 Hook
		remove_action( 'shutdown', [ 'LIAISIHM_Query_Profiler', 'collect_and_store_queries' ], 999 );

		// 如果未定義 SAVEQUERIES，init 不應掛載 hook
		if ( ! defined( 'SAVEQUERIES' ) || ! SAVEQUERIES ) {
			LIAISIHM_Query_Profiler::init();
			$this->assertFalse( has_action( 'shutdown', [ 'LIAISIHM_Query_Profiler', 'collect_and_store_queries' ] ) );
		} else {
			$this->markTestSkipped( 'SAVEQUERIES is enabled in current environment.' );
		}
	}

	/**
	 * 測試 SQL 標準化邏輯 (字串與數字參數替換為 ?)
	 */
	public function test_normalize_sql() {
		$reflection = new ReflectionClass( 'LIAISIHM_Query_Profiler' );
		$method     = $reflection->getMethod( 'normalize_sql' );
		$method->setAccessible( true );

		$raw_sql  = "SELECT * FROM wp_posts WHERE ID = 10 AND post_type = 'post'";
		$expected = "SELECT * FROM wp_posts WHERE ID = ? AND post_type = ?";

		$result = $method->invoke( null, $raw_sql );
		$this->assertEquals( $expected, $result );
	}

	/**
	 * 測試 has_index 方法對於非 SELECT 語句回傳 null
	 */
	public function test_has_index_non_select() {
		$reflection = new ReflectionClass( 'LIAISIHM_Query_Profiler' );
		$method     = $reflection->getMethod( 'has_index' );
		$method->setAccessible( true );

		$insert_sql = "INSERT INTO wp_options (option_name, option_value) VALUES ('test', '123')";
		$result     = $method->invoke( null, $insert_sql );

		$this->assertNull( $result );
	}

	/**
	 * 測試 EXPLAIN 檢查索引狀態 (以核心 wp_posts 表為例)
	 */
	public function test_has_index_with_valid_query() {
		$reflection = new ReflectionClass( 'LIAISIHM_Query_Profiler' );
		$method     = $reflection->getMethod( 'has_index' );
		$method->setAccessible( true );

		global $wpdb;
		// 針對有主鍵或索引的查詢
		$indexed_sql   = "SELECT * FROM {$wpdb->posts} WHERE ID = 1";
		$has_index_res = $method->invoke( null, $indexed_sql );

		$this->assertTrue( $has_index_res );
	}

	/**
	 * 測試 collect_and_store_queries 過濾未達門檻的快查詢
	 */
	public function test_collect_queries_ignores_fast_queries() {
		global $wpdb;

		// 設定門檻為 50ms
		update_option( 'liaisihm_slow_query_threshold', 50 );

		$original_queries = $wpdb->queries;

		// 模擬一條僅耗時 5ms (0.005s) 的查詢
		$wpdb->queries = [
			[ "SELECT * FROM {$wpdb->options} WHERE option_id = 1", 0.005, 'stack_trace' ],
		];

		LIAISIHM_Query_Profiler::collect_and_store_queries();

		// 讀取私有靜態 patterns 變數，確認快查詢未被紀錄至 patterns
		$reflection = new ReflectionClass( 'LIAISIHM_Query_Profiler' );
		$property   = $reflection->getProperty( 'patterns' );
		$property->setAccessible( true );
		$patterns = $property->getValue();

		$this->assertEmpty( $patterns );

		$wpdb->queries = $original_queries;
	}

	/**
	 * 測試 collect_and_store_queries 捕捉超過門檻的慢查詢並記錄 N+1 pattern
	 */
	public function test_collect_queries_captures_slow_query() {
		global $wpdb;

		// 設定門檻為 10ms
		update_option( 'liaisihm_slow_query_threshold', 10 );

		$original_queries = $wpdb->queries;

		// 模擬一條耗時 20ms (0.020s) 的慢查詢
		$wpdb->queries = [
			[ "SELECT * FROM {$wpdb->posts} WHERE ID = 100", 0.020, 'stack_trace' ],
		];

		LIAISIHM_Query_Profiler::collect_and_store_queries();

		$reflection = new ReflectionClass( 'LIAISIHM_Query_Profiler' );
		$property   = $reflection->getProperty( 'patterns' );
		$property->setAccessible( true );
		$patterns = $property->getValue();

		$normalized_key = "SELECT * FROM {$wpdb->posts} WHERE ID = ?";

		$this->assertArrayHasKey( $normalized_key, $patterns );
		$this->assertEquals( 1, $patterns[ $normalized_key ]['count'] );
		$this->assertEquals( 0.020, $patterns[ $normalized_key ]['time'] );

		$wpdb->queries = $original_queries;
	}

	/**
	 * 測試簡化堆疊資訊 (get_simplified_backtrace) 是否正確排除 wp-db 相關檔案
	 */
	public function test_get_simplified_backtrace() {
		$reflection = new ReflectionClass( 'LIAISIHM_Query_Profiler' );
		$method     = $reflection->getMethod( 'get_simplified_backtrace' );
		$method->setAccessible( true );

		$backtrace = $method->invoke( null );

		$this->assertIsString( $backtrace );
		$this->assertStringNotContainsString( 'wp-db.php', $backtrace );
		$this->assertStringNotContainsString( 'class-shm-wpdb.php', $backtrace );
	}
}