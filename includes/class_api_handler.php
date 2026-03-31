<?php
namespace RandomQuoteBlock;

class ApiHandler {
    private $database_manager;
    
    public function __construct() {
        $this->database_manager = new DatabaseManager();
    }


    public static function checkAndFetchIfNeeded() {
        $db_manager = new DatabaseManager();
        $count = $db_manager->getQuoteCount();
        
        if ($count === 0) {
            return self::fetchAndStoreQuotes();
        }
        
        return $count;
    }
    
    
    public static function fetchAndStoreQuotes() {
        $instance = new self();
        $quotes = $instance->fetchQuotesFromApi();
        
        if (!empty($quotes)) {
            return $instance->database_manager->saveQuotes($quotes);
        }
        
        return 0;
    }
    

    public function fetchQuotesFromApi() {
        $settings = $this->getApiSettings();
        
        $url = add_query_arg([
            'limit' => $settings['limit'],
            'skip' => $settings['skip']
        ], $settings['api_url']);
        
        $response = wp_remote_get($url, [
            'timeout' => 30,
            'headers' => [
                'Accept' => 'application/json'
            ]
        ]);
        
        if (is_wp_error($response)) {
            error_log('Random Quote Block API Error: ' . $response->get_error_message());
            return [];
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            error_log("Random Quote Block API Error: HTTP $status_code");
            return [];
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('Random Quote Block JSON Error: ' . json_last_error_msg());
            return [];
        }
        
        return $data['quotes'] ?? [];
    }
    

    private function getApiSettings() {
        $settings = get_transient('rqb_api_settings');
        
        if (false === $settings) {
            $settings = [
                'api_url' => get_option('rqb_api_url', 'https://dummyjson.com/quotes'),
                'limit' => (int) get_option('rqb_api_limit', 10),
                'skip' => (int) get_option('rqb_api_skip', 0)
            ];
            
            set_transient('rqb_api_settings', $settings, HOUR_IN_SECONDS);
        }
        
        return $settings;
    }
    

    public static function clearSettingsCache() {
        delete_transient('rqb_api_settings');
    }
}