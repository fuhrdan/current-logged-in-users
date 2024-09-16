<?php
/*
Plugin Name: Current Logged In Users
Description: Displays currently logged in users on the WordPress dashboard.
Version: 1.3.2
Author: Dan Fuhr
Requires at least: 5.5
Requires PHP: 7.4
License: MIT
License URI: https://spdx.org/licenses/MIT.html
*/

// Hook into user login to update last activity time
function aulh_update_last_activity($user_login) {
    $user_id = get_user_by('login', $user_login)->ID;
    update_user_meta($user_id, 'last_activity', current_time('timestamp'));
}
add_action('wp_login', 'aulh_update_last_activity');

// Track activity for authenticated users
function aulh_track_user_activity() {
    if (is_user_logged_in()) {
        $user_id = get_current_user_id();
        update_user_meta($user_id, 'last_activity', current_time('timestamp'));
    }
}
add_action('init', 'aulh_track_user_activity');

// Add a dashboard widget
function aulh_add_dashboard_widget() {
    wp_add_dashboard_widget(
        'active_users_dashboard',
        'Active Users Dashboard',
        'aulh_display_active_users_dashboard'
    );
}
add_action('wp_dashboard_setup', 'aulh_add_dashboard_widget');

// Display active users in the dashboard widget
function aulh_display_active_users_dashboard() {
    global $wpdb;
    $one_hour_ago = current_time('timestamp') - 3600;
    $twenty_four_hours_ago = current_time('timestamp') - 86400;

    // Query users with activity within the last hour
    $active_users_last_hour = $wpdb->get_results($wpdb->prepare(
        "
        SELECT u.ID, u.user_login, um.meta_value AS last_activity
        FROM {$wpdb->users} u
        INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
        WHERE um.meta_key = %s
        AND CAST(um.meta_value AS UNSIGNED) > %d
        ",
        'last_activity',
        $one_hour_ago
    ));

    // Query users with activity within the last 24 hours
    $active_users_last_24_hours = $wpdb->get_results($wpdb->prepare(
        "
        SELECT u.ID, u.user_login, um.meta_value AS last_activity
        FROM {$wpdb->users} u
        INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
        WHERE um.meta_key = %s
        AND CAST(um.meta_value AS UNSIGNED) > %d
        AND CAST(um.meta_value AS UNSIGNED) <= %d
        ",
        'last_activity',
        $twenty_four_hours_ago,
        $one_hour_ago
    ));

    ?>
    <div class="active-users-dashboard">
        <h2>Active Users Last Hour</h2>
        <?php if ($active_users_last_hour): ?>
            <table class="wp-list-table widefat fixed">
                <thead>
                    <tr>
                        <th scope="col">User ID</th>
                        <th scope="col">Username</th>
                        <th scope="col">Last Activity</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($active_users_last_hour as $user): ?>
                        <tr>
                            <td><?php echo esc_html($user->ID); ?></td>
                            <td><?php echo esc_html($user->user_login); ?></td>
                            <td><?php echo esc_html(gmdate('Y-m-d H:i:s', $user->last_activity)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No active users in the last hour.</p>
        <?php endif; ?>

        <h2>Active Users Last 24 Hours</h2>
        <?php if ($active_users_last_24_hours): ?>
            <table class="wp-list-table widefat fixed">
                <thead>
                    <tr>
                        <th scope="col">User ID</th>
                        <th scope="col">Username</th>
                        <th scope="col">Last Activity</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($active_users_last_24_hours as $user): ?>
                        <tr>
                            <td><?php echo esc_html($user->ID); ?></td>
                            <td><?php echo esc_html($user->user_login); ?></td>
                            <td><?php echo esc_html(gmdate('Y-m-d H:i:s', $user->last_activity)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No active users in the last 24 hours.</p>
        <?php endif; ?>
    </div>
    <?php
}