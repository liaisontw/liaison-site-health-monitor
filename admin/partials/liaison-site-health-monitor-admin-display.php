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
?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->

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
