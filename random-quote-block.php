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


define('RQB_VERSION', '1.0.0');
define('RQB_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('RANDOM_QUOTE_BLOCK_PLUGIN_INC', RQB_PLUGIN_PATH . 'includes/');
define('RQB_PLUGIN_URL', plugin_dir_url(__FILE__));
define('RQB_TABLE_NAME', 'random_quotes');


// must change with autoload
require_once RQB_PLUGIN_PATH . 'includes/class_database_manager.php';
require_once RQB_PLUGIN_PATH . 'includes/class_api_handler.php';
require_once RQB_PLUGIN_PATH . 'includes/class_quote_manager.php';
require_once RQB_PLUGIN_PATH . 'includes/class_admin_settings.php';
require_once RQB_PLUGIN_PATH . 'includes/class_plugin_activator.php';
require_once RQB_PLUGIN_PATH . 'includes/class_plugin_deactivator.php';


register_activation_hook(__FILE__, ['RandomQuoteBlock\PluginActivator', 'activate']);
register_deactivation_hook(__FILE__, ['RandomQuoteBlock\PluginDeactivator', 'deactivate']);


add_action('plugins_loaded', function() {
    if (is_admin()) {
        new RandomQuoteBlock\AdminSettings();
    }

    new RandomQuoteBlock\QuoteManager();

    if (!wp_next_scheduled('rqb_daily_fetch_quotes')) {
        wp_schedule_event(time(), 'daily', 'rqb_daily_fetch_quotes');
    }

});


function rqb_register_block() {
    register_block_type(RQB_PLUGIN_PATH . 'blocks/random-quote', [
        'render_callback' => 'rqb_render_block_callback'
    ]);
}
add_action('init', 'rqb_register_block');


function rqb_render_block_callback($attributes) {
    $quote_manager = new RandomQuoteBlock\QuoteManager();
    $quote = $quote_manager->getRandomQuoteForDisplay();

    if (!$quote || empty($quote->quote_text)) {
        return '<div class="random-quote-block">No quote available.</div>';
    }

    $show_author = isset($attributes['showAuthor']) ? (bool) $attributes['showAuthor'] : true;

    $html  = '<div class="random-quote-block">';
    $html .= '<blockquote>' . esc_html($quote->quote_text) . '</blockquote>';

    if ($show_author && !empty($quote->author)) {
        $html .= '<cite>— ' . esc_html($quote->author) . '</cite>';
    }

    $html .= '</div>';

    return $html;
}

add_action('enqueue_block_editor_assets', 'rqb_editor_localize');


function rqb_editor_localize() {
    if (!wp_script_is('random-quote-random-quote-editor-script', 'enqueued')) {
        return;
    }

    $quote_manager = new RandomQuoteBlock\QuoteManager();
    $quote = $quote_manager->getRandomQuoteForDisplay();

    wp_localize_script(
        'random-quote-random-quote-editor-script',
        'rqbEditorData',
        [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('rqb_ajax_nonce'),
            'initialQuote' => $quote ? [
                'quote' => $quote->quote_text,
                'author' => $quote->author
            ] : null
        ]
    );
}

add_action('wp_ajax_rqb_refresh_quote', 'rqb_ajax_refresh_quote');


function rqb_ajax_refresh_quote() {
    check_ajax_referer('rqb_ajax_nonce', 'nonce');

    $quote_manager = new RandomQuoteBlock\QuoteManager();
    $quote = $quote_manager->getRandomQuoteForDisplay();

    wp_send_json_success([
        'quote' => $quote->quote_text,
        'author' => $quote->author
    ]);
}