<?php
defined( 'ABSPATH' ) || exit;

class SHM_Query_Profiler {

    const SLOW_QUERY_THRESHOLD_MS = 50;

    public static function init() {
        if ( ! defined( 'SAVEQUERIES' ) || ! SAVEQUERIES ) {
            return;
        }

        add_action( 'shutdown', [ __CLASS__, 'collect_queries' ], 0 );
    }

    public static function collect_queries() {
        global $wpdb;

        error_log( 'SAVEQUERIES=' . ( defined('SAVEQUERIES') ? ( SAVEQUERIES ? 'true' : 'false' ) : 'undefined' ) );
        error_log( 'queries count=' . ( is_array( $wpdb->queries ) ? count( $wpdb->queries ) : 'not array' ) );

        if ( empty( $wpdb->queries ) ) {
            return;
        }

        foreach ( $wpdb->queries as $query ) {
            list( $sql, $time, $caller ) = $query;

            $time_ms = $time * 1000;

            if ( $time_ms < self::SLOW_QUERY_THRESHOLD_MS ) {
                continue;
            }

            error_log( 'query_text' . $sql );
            SHM_DB::insert( [
                'query_hash'   => md5( $sql ),
                'query_text'   => $sql,
                'total_time_ms'=> $time_ms,
                'call_stack'   => is_string( $caller ) ? $caller : null,
                'request_uri'  => $_SERVER['REQUEST_URI'] ?? null,
                'created_at'   => current_time( 'mysql' ),
            ] );
        }

        // optional cleanup
        SHM_DB::cleanup( 7 );
    }
}
