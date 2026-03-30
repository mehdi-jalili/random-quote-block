<?php
namespace RandomQuoteBlock;

class QuoteManager {
    private $database_manager;
    
    public function __construct() {
        $this->database_manager = new DatabaseManager();
    }
    
    /**
     * Get random quote for display with caching
     */
    public function getRandomQuoteForDisplay() {
        // Check if there are any quotes
        $count = $this->database_manager->getQuoteCount();
        
        if ($count === 0) {
            // Try to fetch quotes if none exist
            ApiHandler::fetchAndStoreQuotes();
            // Try again after fetch
            return $this->database_manager->getRandomQuote();
        }
        
        return $this->database_manager->getRandomQuote();
    }
    
    /**
     * Get quote statistics
     */
    public function getQuoteStats() {
        return [
            'total' => $this->database_manager->getQuoteCount(),
            'has_quotes' => $this->database_manager->getQuoteCount() > 0
        ];
    }
}