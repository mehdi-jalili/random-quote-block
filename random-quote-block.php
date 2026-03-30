<?php
/**
 * Plugin Name: Random Quote Block
 * Plugin URI: https://github.com/mehdi-jalili/random-quote-block
 * Description: A Gutenberg block that displays random quotes from DummyJSON API
 * Version: 1.0.0
 * Author: Mehdi Jalili
 * License: GPL v2 or later
 * Text Domain: random-quote-block
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define constants
define('RQB_VERSION', '1.0.0');
define('RQB_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('RQB_PLUGIN_URL', plugin_dir_url(__FILE__));
define('RQB_TABLE_NAME', 'random_quotes');

// Load required files
require_once RQB_PLUGIN_PATH . 'includes/class-database-manager.php';
require_once RQB_PLUGIN_PATH . 'includes/class-api-handler.php';
require_once RQB_PLUGIN_PATH . 'includes/class-quote-manager.php';
require_once RQB_PLUGIN_PATH . 'includes/class-admin-settings.php';
require_once RQB_PLUGIN_PATH . 'includes/class-plugin-activator.php';
require_once RQB_PLUGIN_PATH . 'includes/class-plugin-deactivator.php';

// Activation & Deactivation Hooks
register_activation_hook(__FILE__, ['RandomQuoteBlock\PluginActivator', 'activate']);
register_deactivation_hook(__FILE__, ['RandomQuoteBlock\PluginDeactivator', 'deactivate']);

// Initialize plugin
add_action('plugins_loaded', function() {
    // Initialize admin settings (only in admin)
    if (is_admin()) {
        new RandomQuoteBlock\AdminSettings();
    }
    
    // Initialize quote manager
    new RandomQuoteBlock\QuoteManager();
    
    // Schedule daily quote fetch
    if (!wp_next_scheduled('rqb_daily_fetch_quotes')) {
        wp_schedule_event(time(), 'daily', 'rqb_daily_fetch_quotes');
    }
});

// Cron hook for fetching quotes
add_action('rqb_daily_fetch_quotes', ['RandomQuoteBlock\ApiHandler', 'fetchAndStoreQuotes']);

// Register Gutenberg Block (API v3)
add_action('init', 'rqb_register_block');
function rqb_register_block() {
    $block_path = RQB_PLUGIN_PATH . 'blocks/random-quote';
    
    if (!file_exists($block_path . '/block.json')) {
        return;
    }
    
    register_block_type($block_path, [
        'render_callback' => 'rqb_render_block_callback'
    ]);
}

// Render callback for frontend (SSR - no extra requests)
function rqb_render_block_callback($attributes, $content, $block) {
    $quote_manager = new RandomQuoteBlock\QuoteManager();
    $quote = $quote_manager->getRandomQuoteForDisplay();
    
    if (!$quote) {
        return '<div class="wp-block-random-quote-random-quote">' . 
               esc_html__('No quotes available. Please check plugin settings.', 'random-quote-block') . 
               '</div>';
    }
    
    $show_author = $attributes['showAuthor'] ?? true;
    $align = $attributes['align'] ?? 'none';
    $classes = ['wp-block-random-quote-random-quote'];
    
    if ($align !== 'none') {
        $classes[] = 'align' . $align;
    }
    
    ob_start();
    ?>
    <div class="<?php echo esc_attr(implode(' ', $classes)); ?>">
        <div class="random-quote-block">
            <blockquote class="random-quote-block__text">
                <?php echo esc_html($quote->quote_text); ?>
            </blockquote>
            <?php if ($show_author && !empty($quote->author)): ?>
                <cite class="random-quote-block__author">
                    — <?php echo esc_html($quote->author); ?>
                </cite>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

// Localize script for editor (pass initial data without extra request)
add_action('enqueue_block_editor_assets', 'rqb_enqueue_block_editor_assets');
function rqb_enqueue_block_editor_assets() {
    $quote_manager = new RandomQuoteBlock\QuoteManager();
    $initial_quote = $quote_manager->getRandomQuoteForDisplay();
    
    wp_localize_script(
        'random-quote-random-quote-editor-script',
        'rqbEditorData',
        [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('rqb_ajax_nonce'),
            'initialQuote' => $initial_quote ? [
                'id' => $initial_quote->quote_id,
                'quote' => $initial_quote->quote_text,
                'author' => $initial_quote->author
            ] : null
        ]
    );
}

// AJAX handler for refreshing quote (only for editor)
add_action('wp_ajax_rqb_refresh_quote', 'rqb_ajax_refresh_quote');

function rqb_ajax_refresh_quote() {
    // Verify nonce
    if (!check_ajax_referer('rqb_ajax_nonce', 'nonce', false)) {
        wp_send_json_error('Invalid nonce');
        return;
    }
    
    $quote_manager = new RandomQuoteBlock\QuoteManager();
    $quote = $quote_manager->getRandomQuoteForDisplay();
    
    if ($quote) {
        wp_send_json_success([
            'id' => $quote->quote_id,
            'quote' => $quote->quote_text,
            'author' => $quote->author
        ]);
    } else {
        wp_send_json_error('No quotes available');
    }
}