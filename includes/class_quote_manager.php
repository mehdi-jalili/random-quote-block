<?php
namespace RandomQuoteBlock;

class QuoteManager {
    private $database_manager;
    

    public function __construct() {
        $this->database_manager = new DatabaseManager();
    }
    

    public function getRandomQuoteForDisplay() {
        $count = $this->database_manager->getQuoteCount();
        
        if ($count === 0) {
            // Try to fetch quotes if none exist
            ApiHandler::fetchAndStoreQuotes();
            // Try again after fetch
            return $this->database_manager->getRandomQuote();
        }
        
        return $this->database_manager->getRandomQuote();
    }
    

    public function getQuoteStats() {
        return [
            'total' => $this->database_manager->getQuoteCount(),
            'has_quotes' => $this->database_manager->getQuoteCount() > 0
        ];
    }
}