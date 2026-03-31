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
            ApiHandler::fetchAndStoreQuotes();
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