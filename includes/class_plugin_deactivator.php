<?php
namespace RandomQuoteBlock;

class PluginDeactivator {
    public static function deactivate() {
        wp_clear_scheduled_hook('rqb_daily_fetch_quotes');
        
        delete_transient('rqb_api_settings');
        
        wp_cache_flush();
    }
}