<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// Drop table
$table_name = $wpdb->prefix . 'random_quotes';
$wpdb->query("DROP TABLE IF EXISTS $table_name");

// Delete options
delete_option('rqb_api_url');
delete_option('rqb_api_limit');
delete_option('rqb_api_skip');

// Delete transients
delete_transient('rqb_api_settings');

// Clear cron
wp_clear_scheduled_hook('rqb_daily_fetch_quotes');

// Clear cache
wp_cache_flush();