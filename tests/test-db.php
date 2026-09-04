<?php
/**
 * Class LIAISIHM_DB_Test
 *
 * @package Liaison_Site_Health_Monitor
 */
class LIAISIHM_DB_Test extends WP_UnitTestCase {

	/**
	 * 在每個測試執行前初始化資料表與狀態
	 */
	public function setUp(): void {
		parent::setUp();
		
		// 確保資料表已建立
		//LIAISIHM_DB::install();
		
		// 清除 Object Cache 避免快取干擾測試
		wp_cache_flush();
	}

	/**
	 * 在每個測試結束後清理資料表與選項
	 */
	public function tearDown(): void {
		global $wpdb;
		$table_name = LIAISIHM_DB::table_name();
		
		// 測試後清除資料表內容
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( "TRUNCATE TABLE {$table_name}" );
		
		// 刪除測試用的選項
		delete_option( 'liaisihm_slow_query_threshold' );
		
		parent::tearDown();
	}

	/**
	 * 測試 table_name 方法是否正確回傳加上字首的資料表名稱
	 */
	public function test_table_name() {
		global $wpdb;
		$expected = $wpdb->prefix . 'shm_query_log';
		$this->assertEquals( $expected, LIAISIHM_DB::table_name() );
	}

	/**
	 * 測試 install 方法是否成功建立資料表
	 */
	public function test_install() {
		global $wpdb;
		$table_name = LIAISIHM_DB::table_name();

		// 檢查資料表是否存在於資料庫中
		$query = $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name );
		$result = $wpdb->get_var( $query );

		LIAISIHM_DB::install();

        //$this->assertSame( '', $wpdb->last_error );
        $this->assertEquals( $table_name, $result );
	}

	/**
	 * 測試資料插入與 get_top_slow_queries 查詢功能（包含快取情境）
	 */
	public function test_insert_and_get_top_slow_queries() {
		$row = [
			'query_hash'    => md5( 'SELECT * FROM wp_posts' ),
			'query_text'    => 'SELECT * FROM wp_posts',
			'total_time_ms' => 125.50,
			'call_stack'    => 'main() -> require()',
			'request_uri'   => '/wp-admin/',
			'created_at'    => current_time( 'mysql' ),
			'normalized'    => 'SELECT * FROM wp_posts',
			'has_index'     => 1,
		];

		// 執行插入
		LIAISIHM_DB::insert( $row );

		// 取得效能查詢結果
		$slow_queries = LIAISIHM_DB::get_top_slow_queries( 10 );

		$this->assertNotEmpty( $slow_queries );
		$this->assertCount( 1, $slow_queries );
		$this->assertEquals( 'SELECT * FROM wp_posts', $slow_queries[0]->query_text );
		$this->assertEquals( 125.50, (float) $slow_queries[0]->total_time_ms );

		// 再次呼叫以測試快取機制（確保快取命中時依然能正確回傳資料）
		$cached_queries = LIAISIHM_DB::get_top_slow_queries( 10 );
		$this->assertNotEmpty( $cached_queries );
		$this->assertEquals( $slow_queries, $cached_queries );
	}

	/**
	 * 測試 cleanup 清理過期資料功能
	 */
	public function test_cleanup() {
		global $wpdb;
		$table_name = LIAISIHM_DB::table_name();

		// 插入一筆 10 天前的舊資料
		$old_date = gmdate( 'Y-m-d H:i:s', strtotime( '-10 days' ) );
		// 插入一筆今天的最新資料
		$recent_date = current_time( 'mysql' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			$table_name,
			[
				'query_hash'    => md5( 'old query' ),
				'query_text'    => 'old query',
				'total_time_ms' => 50.0,
				'created_at'    => $old_date,
			],
			[ '%s', '%s', '%f', '%s' ]
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			$table_name,
			[
				'query_hash'    => md5( 'recent query' ),
				'query_text'    => 'recent query',
				'total_time_ms' => 60.0,
				'created_at'    => $recent_date,
			],
			[ '%s', '%s', '%f', '%s' ]
		);

		// 執行保留 7 天內的清理動作
		LIAISIHM_DB::cleanup( 7 );

		// 驗證過期資料已被刪除，新資料仍保留
		$remaining_queries = $wpdb->get_results( "SELECT query_text FROM {$table_name}" );
		
		$this->assertCount( 1, $remaining_queries );
		$this->assertEquals( 'recent query', $remaining_queries[0]->query_text );
	}

	/**
	 * 測試 get_threshold 預設值與自訂選項值
	 */
	public function test_get_threshold() {
		// 1. 測試預設值（當選項尚未設定時應回傳 50）
		delete_option( 'liaisihm_slow_query_threshold' );
		$this->assertEquals( 50.0, LIAISIHM_DB::get_threshold() );

		// 2. 測試自訂值
		update_option( 'liaisihm_slow_query_threshold', 120 );
		$this->assertEquals( 120.0, LIAISIHM_DB::get_threshold() );
	}
}