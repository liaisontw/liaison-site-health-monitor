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

<h2>Slow DB Queries</h2>

<table class="widefat">
    <thead>
        <tr>
            <th>Time (ms)</th>
            <th>Query</th>
            <th>Request</th>
            <th>Date</th>
            <th>Normalized SQL</th>
            <th>Index</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ( $rows as $row ) : ?>
        <tr>
            <td><?php echo esc_html( round( $row->total_time_ms, 2 ) ); ?></td>
            <td><code><?php echo esc_html( $row->query_text ); ?></code></td>
            <td><?php echo esc_html( $row->request_uri ); ?></td>
            <td><?php echo esc_html( $row->created_at ); ?></td>
            <td><?php echo esc_html( $row->normalized ); ?></td>
            <td><?php echo esc_html( $row->has_index ); ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>



<div class="wrap">
    <h1>Site Health Monitor v1</h1>

    <table class="widefat striped" style="max-width:600px;">
        <tbody>
            <tr>
                <td>PHP Memory</td>
                <td><?php echo esc_html($memory); ?> MB</td>
            </tr>
            <tr>
                <td>DB Query Time</td>
                <td><?php echo esc_html($db_time); ?> ms</td>
            </tr>
            <tr>
                <td>REST API</td>
                <td><?php echo esc_html($rest_time); ?> ms</td>
            </tr>
            <tr>
                <td>Plugins</td>
                <td><?php echo esc_html($plugins); ?> active</td>
            </tr>
            <tr>
                <td>WordPress</td>
                <td><?php echo esc_html($wp_version); ?></td>
            </tr>
        </tbody>
    </table>
</div>
