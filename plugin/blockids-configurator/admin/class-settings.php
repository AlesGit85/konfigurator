<?php
/**
 * Admin settings page
 */

if (!defined('ABSPATH')) {
    exit;
}

class BLOCKids_Configurator_Settings {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu_page'));
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    public function add_menu_page() {
        add_menu_page(
            __('BLOCKids Konfigurátor', 'blockids-configurator'),
            __('BLOCKids', 'blockids-configurator'),
            'manage_options',
            'blockids-configurator',
            array($this, 'render_settings_page'),
            'dashicons-admin-tools',
            56
        );
    }
    
    public function register_settings() {
        register_setting('blockids_configurator_settings', 'blockids_configurator_url');
        register_setting('blockids_configurator_settings', 'blockids_api_base_url');
        register_setting('blockids_configurator_settings', 'blockids_jwt_secret_key');
        register_setting('blockids_configurator_settings', 'blockids_jwt_expiration');
    }
    
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Handle form submission
        if (isset($_POST['blockids_settings_submit'])) {
            check_admin_referer('blockids_settings');
            
            update_option('blockids_configurator_url', sanitize_text_field($_POST['configurator_url']));
            update_option('blockids_api_base_url', sanitize_text_field($_POST['api_base_url']));
            update_option('blockids_jwt_secret_key', sanitize_text_field($_POST['jwt_secret_key']));
            update_option('blockids_jwt_expiration', intval($_POST['jwt_expiration']));
            
            echo '<div class="notice notice-success"><p>' . __('Nastavení uloženo.', 'blockids-configurator') . '</p></div>';
        }
        
        $configurator_url = get_option('blockids_configurator_url', 'https://configurator.blockids.eu');
        $api_base_url = get_option('blockids_api_base_url', home_url('/wp-json/blockids/v1'));
        $jwt_secret_key = get_option('blockids_jwt_secret_key');
        $jwt_expiration = get_option('blockids_jwt_expiration', 3600);
        
        ?>
        <div class="wrap">
            <h1><?php _e('BLOCKids Konfigurátor - Nastavení', 'blockids-configurator'); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('blockids_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="configurator_url"><?php _e('URL Konfiguratoru', 'blockids-configurator'); ?></label>
                        </th>
                        <td>
                            <input type="url" id="configurator_url" name="configurator_url" value="<?php echo esc_attr($configurator_url); ?>" class="regular-text">
                            <p class="description"><?php _e('Např. https://configurator.blockids.eu', 'blockids-configurator'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="api_base_url"><?php _e('API Base URL', 'blockids-configurator'); ?></label>
                        </th>
                        <td>
                            <input type="url" id="api_base_url" name="api_base_url" value="<?php echo esc_attr($api_base_url); ?>" class="regular-text">
                            <p class="description"><?php _e('URL vašeho WordPress REST API endpointu', 'blockids-configurator'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="jwt_secret_key"><?php _e('JWT Secret Key', 'blockids-configurator'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="jwt_secret_key" name="jwt_secret_key" value="<?php echo esc_attr($jwt_secret_key); ?>" class="regular-text">
                            <p class="description"><?php _e('Tajný klíč pro podepisování JWT tokenů (automaticky vygenerovaný při instalaci)', 'blockids-configurator'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="jwt_expiration"><?php _e('JWT Token Expiration', 'blockids-configurator'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="jwt_expiration" name="jwt_expiration" value="<?php echo esc_attr($jwt_expiration); ?>" class="small-text">
                            <p class="description"><?php _e('Platnost tokenu v sekundách (výchozí: 3600 = 1 hodina)', 'blockids-configurator'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <h2><?php _e('Testování', 'blockids-configurator'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Test JWT Token', 'blockids-configurator'); ?></th>
                        <td>
                            <?php
                            $current_user = wp_get_current_user();
                            $test_token = BLOCKids_Configurator_Auth::generate_token($current_user->ID);
                            ?>
                            <textarea readonly class="large-text" rows="4"><?php echo esc_textarea($test_token); ?></textarea>
                            <p class="description">
                                <?php _e('Testovací token pro aktuálně přihlášeného uživatele. Použij pro test konfiguratoru.', 'blockids-configurator'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Link do konfiguratoru', 'blockids-configurator'); ?></th>
                        <td>
                            <?php
                            $locale = substr(get_locale(), 0, 2);
                            if (!in_array($locale, array('cs', 'en', 'de'))) {
                                $locale = 'cs';
                            }
                            $configurator_test_url = $configurator_url . '/' . $locale . '/sso?token=' . $test_token;
                            ?>
                            <a href="<?php echo esc_url($configurator_test_url); ?>" target="_blank" class="button">
                                <?php _e('Otevřít konfigurátor s tímto tokenem', 'blockids-configurator'); ?>
                            </a>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('API Endpointy', 'blockids-configurator'); ?></th>
                        <td>
                            <ul>
                                <li><code>GET <?php echo esc_html($api_base_url); ?>/customers/me/{token}</code></li>
                                <li><code>GET <?php echo esc_html($api_base_url); ?>/grips/cs</code></li>
                                <li><code>GET <?php echo esc_html($api_base_url); ?>/mattresses/cs</code></li>
                                <li><code>GET <?php echo esc_html($api_base_url); ?>/desks/cs</code></li>
                                <li><code>POST <?php echo esc_html($api_base_url); ?>/plans/create/cs/{token}</code></li>
                                <li><code>GET <?php echo esc_html($api_base_url); ?>/plans/detail/cs/{token}/{hash}</code></li>
                            </ul>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(__('Uložit nastavení', 'blockids-configurator'), 'primary', 'blockids_settings_submit'); ?>
            </form>
            
            <hr>
            
            <h2><?php _e('Kategorie produktů', 'blockids-configurator'); ?></h2>
            <p><?php _e('Pro správnou funkci konfiguratoru vytvořte v WooCommerce tyto kategorie produktů:', 'blockids-configurator'); ?></p>
            <ul>
                <li><strong>gripy</strong> - <?php _e('Pro lezecké chyty', 'blockids-configurator'); ?></li>
                <li><strong>matrace</strong> - <?php _e('Pro dopadové matrace', 'blockids-configurator'); ?></li>
                <li><strong>desky</strong> - <?php _e('Pro panely/desky lezecké stěny', 'blockids-configurator'); ?></li>
            </ul>
        </div>
        <?php
    }
}

new BLOCKids_Configurator_Settings();
