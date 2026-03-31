<?php
namespace RandomQuoteBlock;

class PluginActivator {
    public static function activate() {
        // Create database table
        $db_manager = new DatabaseManager();
        $db_manager->createTable();
        
        // Set default options
        if (get_option('rqb_api_url') === false) {
            add_option('rqb_api_url', 'https://dummyjson.com/quotes');
        }
        if (get_option('rqb_api_limit') === false) {
            add_option('rqb_api_limit', 10);
        }
        if (get_option('rqb_api_skip') === false) {
            add_option('rqb_api_skip', 0);
        }
        
        // Fetch initial quotes
        ApiHandler::fetchAndStoreQuotes();
        
        // Clear any existing cache
        wp_cache_flush();
    }
}