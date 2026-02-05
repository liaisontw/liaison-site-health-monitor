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

// 處理表單提交
if ( isset( $_POST['liaisihm_save_settings'] ) && check_admin_referer( 'liaisihm_settings_action', 'liaisihm_nonce' ) ) {
    $threshold = isset( $_POST['threshold'] ) ? floatval( $_POST['threshold'] ) : 50;
    update_option( 'liaisihm_slow_query_threshold', $threshold );
    echo '<div class="updated"><p>Settings saved!</p></div>';
}

$current_threshold = LIAISIHM_DB::get_threshold();

?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->

<div class="wrap shm-container">
    
    </div>
    




<div class="wrap shm-container">
    <!-- <div> 
        <h1 class="wp-heading-inline">Site Health Monitor Settings</h1>
    
        <div class="postbox" style="margin-top: 20px; max-width: 400px;">
        <div class="postbox-header"><h2 class="hndle">Threshold Configuration</h2></div>
        <div class="inside">
            <form method="post" action="">
                <?php wp_nonce_field( 'liaisihm_settings_action', 'liaisihm_nonce' ); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="threshold">Slow Query Threshold</label></th>
                        <td>
                            <input name="threshold" type="number" id="threshold" value="<?php echo esc_attr( $current_threshold ); ?>" class="small-text"> ms
                            <p class="description">Queries taking longer than this will be logged.</p>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" name="liaisihm_save_settings" id="submit" class="button button-primary" value="Save Settings">
                </p>
            </form>
        </div>
    </div> 

    <div> 
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
    </div>  -->
    <div class="wrap">
    <h1 class="wp-heading-inline">Site Health Dashboard</h1>
    <hr class="wp-header-end">

    <div class="shm-dashboard-flex">
        
        <div class="shm-flex-item">
            <h2>Settings</h2>
            <div class="postbox">
                <div class="postbox-header">
                    <h2 class="hndle">Threshold Configuration</h2>
                </div>
                <div class="inside">
                    <form method="post" action="">
                        <?php wp_nonce_field( 'liaisihm_settings_action', 'liaisihm_nonce' ); ?>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><label for="threshold">Slow Query Threshold</label></th>
                                <td>
                                    <input name="threshold" type="number" id="threshold" value="<?php echo esc_attr( $current_threshold ); ?>" class="small-text"> ms
                                </td>
                            </tr>
                        </table>
                        <p class="submit">
                            <input type="submit" name="liaisihm_save_settings" id="submit" class="button button-primary" value="Save Settings">
                        </p>
                    </form>
                </div>
            </div>
        </div>

        <div class="shm-flex-item">
            <div class="shm-flex-item">
                <h2>System Overview</h2>
                <div class="welcome-panel" style="padding: 10px; margin: 0;">
                    <table class="widefat striped shm-metrics-table">
                        <tbody>
                            <tr>
                                <td class="shm-label-col">PHP Memory</td>
                                <td><code><?php echo esc_html($memory); ?> MB</code></td>
                            </tr>
                            <tr>
                                <td class="shm-label-col">Total DB Time</td>
                                <td><code><?php echo esc_html($db_time); ?> ms</code></td>
                            </tr>
                            <tr>
                                <td class="shm-label-col">Active Plugins</td>
                                <td><code><?php echo (int)$plugins; ?> active</code></td>
                            </tr>
                            <tr>
                                <td class="shm-label-col">WP Version</td>
                                <td><code><?php echo esc_html($wp_version); ?></code></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
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