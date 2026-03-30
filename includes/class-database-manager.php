<?php
namespace RandomQuoteBlock;

class DatabaseManager {
    private $wpdb;
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $this->wpdb->prefix . RQB_TABLE_NAME;
    }
    
    /**
     * Create the quotes table
     */
    public function createTable() {
        $charset_collate = $this->wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            quote_id bigint(20) NOT NULL,
            quote_text longtext NOT NULL,
            author varchar(255) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY quote_id (quote_id),
            KEY author (author)
        ) $charset_collate;";
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
        
        return $this->tableExists();
    }
    
    /**
     * Drop the quotes table
     */
    public function dropTable() {
        $this->wpdb->query("DROP TABLE IF EXISTS {$this->table_name}");
    }
    
    /**
     * Check if table exists
     */
    public function tableExists() {
        $result = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SHOW TABLES LIKE %s",
                $this->table_name
            )
        );
        
        return $result === $this->table_name;
    }
    
    /**
     * Save quotes to database
     */
    public function saveQuotes($quotes) {
        if (empty($quotes)) {
            return 0;
        }
        
        $saved_count = 0;
        
        foreach ($quotes as $quote) {
            $result = $this->wpdb->replace(
                $this->table_name,
                [
                    'quote_id' => (int) $quote['id'],
                    'quote_text' => sanitize_text_field($quote['quote']),
                    'author' => sanitize_text_field($quote['author'])
                ],
                ['%d', '%s', '%s']
            );
            
            if ($result !== false) {
                $saved_count++;
            }
        }
        
        // Clear cache after saving
        $this->clearCache();
        
        return $saved_count;
    }
    
    /**
     * Get random quote from database
     */
    public function getRandomQuote() {
        // Try cache first
        $cache_key = 'random_quote';
        $cached = wp_cache_get($cache_key, 'random_quote_block');
        
        if ($cached !== false) {
            return $cached;
        }
        
        $quote = $this->wpdb->get_row(
            "SELECT * FROM {$this->table_name} ORDER BY RAND() LIMIT 1"
        );
        
        if ($quote) {
            wp_cache_set($cache_key, $quote, 'random_quote_block', HOUR_IN_SECONDS);
        }
        
        return $quote;
    }
    
    /**
     * Get all quotes
     */
    public function getAllQuotes($limit = 100, $offset = 0) {
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->table_name} ORDER BY created_at DESC LIMIT %d OFFSET %d",
                $limit,
                $offset
            )
        );
    }
    
    /**
     * Get total quote count
     */
    public function getQuoteCount() {
        $cache_key = 'quote_count';
        $cached = wp_cache_get($cache_key, 'random_quote_block');
        
        if ($cached !== false) {
            return (int) $cached;
        }
        
        $count = (int) $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_name}"
        );
        
        wp_cache_set($cache_key, $count, 'random_quote_block', HOUR_IN_SECONDS);
        
        return $count;
    }
    
    /**
     * Clear database cache
     */
    private function clearCache() {
        wp_cache_delete('random_quote', 'random_quote_block');
        wp_cache_delete('quote_count', 'random_quote_block');
    }
}