<?php
namespace RandomQuoteBlock;

class PluginDeactivator {
    public static function deactivate() {
        // Clear scheduled cron jobs
        wp_clear_scheduled_hook('rqb_daily_fetch_quotes');
        
        // Clear transients
        delete_transient('rqb_api_settings');
        
        // Clear cache
        wp_cache_flush();
    }
}