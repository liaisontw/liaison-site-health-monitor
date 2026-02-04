<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://github.com/liaisontw
 * @since      1.0.0
 *
 * @package    liaison_site_health_monitor
 * @subpackage liaison_site_health_monitor/admin/partials
 */
defined( 'ABSPATH' ) || exit;

?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->


<style>
    .shm-container { margin-top: 20px; }
    /* 強制限制表格佈局 */
    .shm-slow-query-table {
        table-layout: fixed;
        width: 100%;
        background: #fff;
    }
    /* 設定欄寬比例 */
    .shm-col-time   { width: 80px; }
    .shm-col-query  { width: auto; } /* 彈性調整，但會被限制長度 */
    .shm-col-req    { width: 150px; }
    .shm-col-date   { width: 140px; }
    .shm-col-index  { width: 70px; }

    /* SQL 語句美化：限制顯示長度，超出部分省略並提供提示 */
    .shm-query-wrapper {
        font-family: 'Consolas', 'Monaco', monospace;
        background: #f0f0f1;
        padding: 4px 8px;
        border-radius: 3px;
        display: block;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        cursor: help;
        font-size: 12px;
        color: #d63638; /* SQL 關鍵顏色 */
    }
    .shm-query-wrapper:hover {
        white-space: normal;
        word-break: break-all;
        background: #fff;
        border: 1px solid #c3c4c7;
        position: relative;
        z-index: 10;
    }

    /* 新增：正規化 SQL 的顯示樣式 */
    .shm-normalized-sql {
        display: block;
        font-size: 11px;
        color: #646970; /* 灰褐色，代表次要訊息 */
        margin-top: 4px;
        font-family: monospace;
        font-style: italic;
    }

    .shm-label {
        font-size: 9px;
        text-transform: uppercase;
        background: #dcdcde;
        padding: 1px 3px;
        border-radius: 2px;
        margin-right: 4px;
        vertical-align: middle;
    }
    /* 高耗時警示 */
    .shm-time-critical { color: #d63638; font-weight: bold; }
    .shm-time-warning  { color: #dba617; font-weight: bold; }
</style>

<div class="wrap shm-container">
    <h1 class="wp-heading-inline">Site Health Monitor v1</h1>
    <hr class="wp-header-end">

    <div class="welcome-panel" style="padding: 20px; margin-bottom: 20px; max-width: 800px;">
        <div class="welcome-panel-column-container">
            <div class="welcome-panel-column">
                <h3><span class="dashicons dashicons-performance"></span> System Overview</h3>
                <table class="widefat striped">
                    <tbody>
                        <tr><td><strong>PHP Memory</strong></td><td><code><?php echo esc_html($memory); ?> MB</code></td></tr>
                        <tr><td><strong>Total DB Time</strong></td><td><code><?php echo esc_html($db_time); ?> ms</code></td></tr>
                        <tr><td><strong>Active Plugins</strong></td><td><span class="update-plugins count-<?php echo (int)$plugins; ?>"><?php echo (int)$plugins; ?></span></td></tr>
                        <tr><td><strong>WP Version</strong></td><td><?php echo esc_html($wp_version); ?></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <h2>Slow DB Queries (Top 10)</h2>
    <table class="widefat striped shm-slow-query-table">
        <thead>
            <tr>
                <th class="shm-col-time">Time (ms)</th>
                <th class="shm-col-query">Query Execution & Patterns</th> 
                <th class="shm-col-req">Request URI</th>
                <th class="shm-col-date">Created At</th>
                <th class="shm-col-index">Index?</th>
            </tr>
        </thead>
        <tbody>
            <?php if ( empty( $rows ) ) : ?>
                <tr><td colspan="5">No slow queries detected yet. Keep up the good work!</td></tr>
            <?php else : ?>
                <?php foreach ( $rows as $row ) : 
                    $time_class = '';
                    if ( $row->total_time_ms > 500 ) $time_class = 'shm-time-critical';
                    elseif ( $row->total_time_ms > 100 ) $time_class = 'shm-time-warning';
                ?>
                <tr>
                    <td class="<?php echo $time_class; ?>">
                        <?php echo esc_html( number_format( $row->total_time_ms, 2 ) ); ?>
                    </td>
                    <td>
                        <span class="shm-query-wrapper" title="<?php echo esc_attr( $row->query_text ); ?>">
                            <?php echo esc_html( $row->query_text ); ?>
                        </span>
                        
                        <?php if ( ! empty( $row->normalized_text ) ) : ?>
                            <div class="shm-normalized-sql">
                                <span class="shm-label">Pattern</span>
                                <code><?php echo esc_html( $row->normalized_text ); ?></code>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td><small><?php echo esc_html( $row->request_uri ); ?></small></td>
                    <td><?php echo esc_html( $row->created_at ); ?></td>
                    <td>
                        </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>