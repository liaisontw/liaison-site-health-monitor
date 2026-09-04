<?php
/**
 * Class LIAISIHM_DB_Test
 *
 * @package Liaison_Site_Health_Monitor
 */
class LIAISIHM_DB_Test extends WP_UnitTestCase {

	/**
	 * Set up state before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		// Prevent cached query results from leaking between tests.
		wp_cache_flush();
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		global $wpdb;

		$table_name = LIAISIHM_DB::table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( "TRUNCATE TABLE {$table_name}" );

		delete_option( 'liaisihm_slow_query_threshold' );

		parent::tearDown();
	}

	/**
	 * Test table_name() returns the plugin table name with the WordPress prefix.
	 */
	public function test_table_name() {
		global $wpdb;

		$expected = $wpdb->prefix . 'shm_query_log';

		$this->assertSame( $expected, LIAISIHM_DB::table_name() );
	}

	/**
	 * Test install() creates the plugin table.
	 */
	public function test_install() {
		global $wpdb;

		$table_name = LIAISIHM_DB::table_name();

		LIAISIHM_DB::install();

		$query  = $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name );
		$result = $wpdb->get_var( $query );

		$this->assertSame( $table_name, $result );
	}

	/**
	 * Test inserting and retrieving slow queries.
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

		LIAISIHM_DB::insert( $row );

		$slow_queries = LIAISIHM_DB::get_top_slow_queries( 10 );

		$this->assertCount( 1, $slow_queries );
		$this->assertSame( 'SELECT * FROM wp_posts', $slow_queries[0]->query_text );
		$this->assertSame( 125.50, (float) $slow_queries[0]->total_time_ms );

		$cached_queries = LIAISIHM_DB::get_top_slow_queries( 10 );

		$this->assertEquals( $slow_queries, $cached_queries );
	}

	/**
	 * Test cleanup removes expired records while retaining recent records.
	 */
	public function test_cleanup() {
		global $wpdb;

		$table_name = LIAISIHM_DB::table_name();
		$now        = current_time( 'timestamp' );
		$old_date   = wp_date( 'Y-m-d H:i:s', $now - 10 * DAY_IN_SECONDS );
		$recent_date = wp_date( 'Y-m-d H:i:s', $now );

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

		LIAISIHM_DB::cleanup( 7 );

		$remaining_queries = $wpdb->get_results(
			"SELECT query_text FROM {$table_name}"
		);

		$this->assertCount( 1, $remaining_queries );
		$this->assertSame( 'recent query', $remaining_queries[0]->query_text );
	}

	/**
	 * Test the default and custom slow query threshold.
	 */
	public function test_get_threshold() {
		delete_option( 'liaisihm_slow_query_threshold' );

		$this->assertSame( 50.0, LIAISIHM_DB::get_threshold() );

		update_option( 'liaisihm_slow_query_threshold', 120 );

		$this->assertSame( 120.0, LIAISIHM_DB::get_threshold() );
	}
}