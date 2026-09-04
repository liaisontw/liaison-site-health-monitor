<?php
/**
 * Class LIAISIHM_Metrics_Test
 *
 * @package Liaison_Site_Health_Monitor
 */
class LIAISIHM_Metrics_Test extends WP_UnitTestCase {

	/**
	 * 測試記憶體峰值回傳值是否為正整數
	 */
	public function test_memory_peak() {
		$peak = LIAISIHM_metrics::memory_peak();

		$this->assertIsInt( $peak );
		$this->assertGreaterThan( 0, $peak );
	}

	/**
	 * 測試取得 WordPress 全域版本號
	 */
	public function test_wp_version() {
		global $wp_version;

		$version = LIAISIHM_metrics::wp_version();

		$this->assertNotEmpty( $version );
		$this->assertEquals( $wp_version, $version );
	}

	/**
	 * 測試記憶體使用量（MB）計算
	 */
	public function test_shm_get_memory_usage() {
		$usage = LIAISIHM_metrics::shm_get_memory_usage();

		$this->assertIsFloat( $usage );
		$this->assertGreaterThan( 0, $usage );
	}

	/**
	 * 測試當 SAVEQUERIES 未啟用或無查詢紀錄時回傳 0
	 */
	public function test_shm_get_db_query_time_empty() {
		global $wpdb;

		// 備份現有查詢紀錄並清空
		$original_queries = $wpdb->queries;
		$wpdb->queries    = [];

		$query_time = LIAISIHM_metrics::shm_get_db_query_time();

		$this->assertEquals( 0, $query_time );

		// 還原紀錄
		$wpdb->queries = $original_queries;
	}

	/**
	 * 測試資料庫查詢時間累加計算（以毫秒為單位）
	 */
	public function test_shm_get_db_query_time_with_queries() {
		global $wpdb;

		$original_queries = $wpdb->queries;

		// 模擬 WPDB 查詢紀錄格式：[0 => SQL, 1 => 執行秒數, 2 => 堆疊資訊]
		$wpdb->queries = [
			[ 'SELECT * FROM wp_options', 0.015, 'stack_1' ],
			[ 'SELECT * FROM wp_posts', 0.025, 'stack_2' ],
		];

		$query_time = LIAISIHM_metrics::shm_get_db_query_time();

		// (0.015 + 0.025) * 1000 = 40.0 ms
		$this->assertEquals( 40.0, $query_time );

		$wpdb->queries = $original_queries;
	}

	/**
	 * 測試計算已啟用的外掛數量
	 */
	public function test_shm_get_active_plugins_count() {
		$mock_plugins = [
			'plugin-one/plugin-one.php',
			'plugin-two/plugin-two.php',
		];

		update_option( 'active_plugins', $mock_plugins );

		$count = LIAISIHM_metrics::shm_get_active_plugins_count();

		$this->assertEquals( 2, $count );
	}

	/**
	 * 測試 REST API 請求回應時間量測
	 */
	public function test_shm_get_rest_response_time() {
		// 攔截 HTTP 請求避免發送實際網路連線
		add_filter(
			'pre_http_request',
			function() {
				return [
					'headers'  => [],
					'body'     => json_encode( [ 'code' => 'success' ] ),
					'response' => [
						'code'    => 200,
						'message' => 'OK',
					],
					'cookies'  => [],
					'filename' => null,
				];
			}
		);

		$response_time = LIAISIHM_metrics::shm_get_rest_response_time();

		$this->assertIsFloat( $response_time );
		$this->assertGreaterThanOrEqual( 0, $response_time );
	}
}