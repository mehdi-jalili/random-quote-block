<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

$table_name = $wpdb->prefix . 'random_quotes';
$wpdb->query("DROP TABLE IF EXISTS $table_name");

delete_option('rqb_api_url');
delete_option('rqb_api_limit');
delete_option('rqb_api_skip');

delete_transient('rqb_api_settings');

wp_clear_scheduled_hook('rqb_daily_fetch_quotes');

wp_cache_flush();