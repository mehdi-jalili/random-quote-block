<?php
namespace RandomQuoteBlock;

class AdminSettings {
    public function __construct() {
        add_action('admin_menu', [$this, 'addAdminMenu']);
        add_action('admin_init', [$this, 'registerSettings']);
    }
    
    public function addAdminMenu() {
        add_options_page(
            'Random Quote Settings',
            'Random Quote',
            'manage_options',
            'random-quote-settings',
            [$this, 'renderSettingsPage']
        );
    }
    
    public function registerSettings() {
        register_setting('rqb_settings', 'rqb_api_url', [
            'type' => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default' => 'https://dummyjson.com/quotes'
        ]);
        
        register_setting('rqb_settings', 'rqb_api_limit', [
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 10
        ]);
        
        register_setting('rqb_settings', 'rqb_api_skip', [
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 0
        ]);
        
        add_settings_section('rqb_main', 'API Settings', null, 'random-quote-settings');
        
        add_settings_field(
            'rqb_api_url',
            'API URL',
            [$this, 'renderApiUrlField'],
            'random-quote-settings',
            'rqb_main'
        );
        
        add_settings_field(
            'rqb_api_limit',
            'Quotes Limit',
            [$this, 'renderLimitField'],
            'random-quote-settings',
            'rqb_main'
        );
        
        add_settings_field(
            'rqb_api_skip',
            'Skip Count',
            [$this, 'renderSkipField'],
            'random-quote-settings',
            'rqb_main'
        );
    }
    
    public function renderApiUrlField() {
        $value = get_option('rqb_api_url', 'https://dummyjson.com/quotes');
        echo '<input type="url" name="rqb_api_url" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">API endpoint for fetching quotes</p>';
    }
    
    public function renderLimitField() {
        $value = get_option('rqb_api_limit', 10);
        echo '<input type="number" name="rqb_api_limit" value="' . esc_attr($value) . '" min="1" max="100" />';
        echo '<p class="description">Number of quotes to fetch per request (1-100)</p>';
    }
    
    public function renderSkipField() {
        $value = get_option('rqb_api_skip', 0);
        echo '<input type="number" name="rqb_api_skip" value="' . esc_attr($value) . '" min="0" />';
        echo '<p class="description">Number of quotes to skip (for pagination)</p>';
    }
    
    public function renderSettingsPage() {
        $quote_manager = new QuoteManager();
        $stats = $quote_manager->getQuoteStats();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Random Quote Block Settings', 'random-quote-block'); ?></h1>
            
            <div class="notice notice-info">
                <p>
                    <strong><?php echo esc_html__('Statistics:', 'random-quote-block'); ?></strong>
                    <?php echo esc_html($stats['total']); ?> 
                    <?php echo esc_html__('quotes stored in database.', 'random-quote-block'); ?>
                </p>
            </div>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('rqb_settings');
                do_settings_sections('random-quote-settings');
                submit_button();
                ?>
            </form>
            
            <hr />
            
            <h2><?php echo esc_html__('Manual Quote Fetch', 'random-quote-block'); ?></h2>
            <p><?php echo esc_html__('Fetch quotes from API and store them in database.', 'random-quote-block'); ?></p>
            
            <button id="rqb-fetch-quotes" class="button button-primary">
                <?php echo esc_html__('Fetch Quotes Now', 'random-quote-block'); ?>
            </button>
            <div id="rqb-fetch-status" style="margin-top: 10px;"></div>
            
            <script type="text/javascript">
            document.getElementById('rqb-fetch-quotes').addEventListener('click', function() {
                var status = document.getElementById('rqb-fetch-status');
                var button = this;
                
                status.innerHTML = '<span class="spinner is-active" style="float: none;"></span> Fetching quotes...';
                button.disabled = true;
                
                var formData = new FormData();
                formData.append('action', 'rqb_manual_fetch');
                formData.append('nonce', '<?php echo wp_create_nonce('rqb_ajax_nonce'); ?>');
                
                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        status.innerHTML = '<div class="notice notice-success"><p>' + data.data.message + '</p></div>';
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        status.innerHTML = '<div class="notice notice-error"><p>' + data.data.message + '</p></div>';
                        button.disabled = false;
                    }
                })
                .catch(error => {
                    status.innerHTML = '<div class="notice notice-error"><p>Error: ' + error.message + '</p></div>';
                    button.disabled = false;
                });
            });
            </script>
        </div>
        <?php
    }
}


add_action('wp_ajax_rqb_manual_fetch', function() {
    if (!check_ajax_referer('rqb_ajax_nonce', 'nonce', false)) {
        wp_send_json_error('Invalid security token');
        return;
    }
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
        return;
    }
    
    $count = ApiHandler::fetchAndStoreQuotes();
    
    if ($count > 0) {
        wp_send_json_success([
            'message' => sprintf('%d quotes fetched and stored successfully!', $count)
        ]);
    } else {
        wp_send_json_error('No quotes fetched. Please check API settings.');
    }
});