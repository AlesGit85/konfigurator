<?php
/**
 * Plugin Name: BLOCKids Konfigurátor Integration
 * Plugin URI: https://blockids.eu
 * Description: Integrace konfiguratoru lezeckých stěn s WooCommerce eshopem
 * Version: 2.1.4
 * Author: Aleš
 * Author URI: https://blockids.eu
 * Text Domain: blockids-configurator
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * WC requires at least: 8.0
 * WC tested up to: 9.0
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('before_woocommerce_init', function() {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
    }
});

define('BLOCKIDS_CONFIGURATOR_VERSION', '2.1.0');
define('BLOCKIDS_CONFIGURATOR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BLOCKIDS_CONFIGURATOR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BLOCKIDS_CONFIGURATOR_PLUGIN_BASENAME', plugin_basename(__FILE__));

class BLOCKids_Configurator {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->includes();
        $this->init_hooks();
    }
    
    private function includes() {
        require_once BLOCKIDS_CONFIGURATOR_PLUGIN_DIR . 'includes/class-install.php';
        require_once BLOCKIDS_CONFIGURATOR_PLUGIN_DIR . 'includes/class-auth.php';
        require_once BLOCKIDS_CONFIGURATOR_PLUGIN_DIR . 'includes/class-api.php';
        require_once BLOCKIDS_CONFIGURATOR_PLUGIN_DIR . 'includes/class-plans.php';
        require_once BLOCKIDS_CONFIGURATOR_PLUGIN_DIR . 'includes/class-cart.php';
        
        if (is_admin()) {
            require_once BLOCKIDS_CONFIGURATOR_PLUGIN_DIR . 'admin/class-settings.php';
            require_once BLOCKIDS_CONFIGURATOR_PLUGIN_DIR . 'admin/class-plan-detail.php';
        }
    }
    
    private function init_hooks() {
        register_activation_hook(__FILE__, array('BLOCKids_Configurator_Install', 'activate'));
        register_deactivation_hook(__FILE__, array('BLOCKids_Configurator_Install', 'deactivate'));
        
        add_action('plugins_loaded', array($this, 'init'));
        add_action('rest_api_init', array('BLOCKids_Configurator_API', 'register_routes'));
        add_action('rest_api_init', array($this, 'add_cors_headers'));
        
        // SSO launch handler - zachytí ?blockids_launch, uloží cookie, přesměruje do konfiguratoru
        add_action('template_redirect', array($this, 'handle_sso_launch'), 5);
        
        // Shortcode pro tlačítko konfiguratoru
        add_shortcode('blockids_configurator_button', array($this, 'render_configurator_button'));
    }
    
    public function init() {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }
        
        load_plugin_textdomain('blockids-configurator', false, dirname(BLOCKIDS_CONFIGURATOR_PLUGIN_BASENAME) . '/languages');
        
        BLOCKids_Configurator_Auth::init();
        BLOCKids_Configurator_Plans::init();
        BLOCKids_Configurator_Cart::init();
    }
    
    /**
     * SSO Launch handler
     * 
     * Když uživatel klikne na tlačítko konfiguratoru na webu:
     * 1. Zkontroluje přihlášení do WP
     * 2. Vygeneruje JWT token
     * 3. Uloží token do cookie (pro pozdější auto-login při návratu)
     * 4. Přesměruje na konfigurátor s ?t=TOKEN
     * 
     * URL: https://blockids.creaticom.cz/?blockids_launch=1
     */
    public function handle_sso_launch() {
        if (!isset($_GET['blockids_launch'])) {
            return;
        }
        
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            // Nepřihlášený → login stránka, po přihlášení zpět sem
            wp_redirect(wp_login_url(add_query_arg('blockids_launch', '1', home_url('/'))));
            exit;
        }
        
        // Vygenerovat JWT token
        $token = BLOCKids_Configurator_Auth::generate_token($user_id);
        
        if (!$token) {
            wp_die(__('Nepodařilo se vygenerovat token.', 'blockids-configurator'));
        }
        
        // Uložit token do cookie - při návratu z konfiguratoru auto-login
        $expiration = get_option('blockids_jwt_expiration', 3600);
        setcookie(
            'blockids_auth_token',
            $token,
            time() + (int) $expiration,
            '/',
            '',     // Doména - prázdná = aktuální
            is_ssl(),
            true    // HttpOnly
        );
        
        // Sestavit URL konfiguratoru
        $configurator_url = get_option('blockids_configurator_url', 'https://configurator.blockids.eu');
        $locale = substr(get_locale(), 0, 2);
        if (!in_array($locale, array('cs', 'en', 'de'))) {
            $locale = 'cs';
        }
        
        $redirect_url = $configurator_url . '/' . $locale . '/sso?t=' . $token;
        
        wp_redirect($redirect_url);
        exit;
    }
    
    /**
     * Shortcode: [blockids_configurator_button]
     * 
     * Tlačítko které jde přes ?blockids_launch=1 → nastaví cookie → redirect do konfiguratoru
     * 
     * Atributy:
     * - text: Text tlačítka
     * - class: CSS třída
     * - login_text: Text pro nepřihlášené
     */
    public function render_configurator_button($atts) {
        $atts = shortcode_atts(array(
            'text' => __('Nakonfigurovat lezeckou stěnu', 'blockids-configurator'),
            'class' => 'button',
            'login_text' => __('Přihlásit se pro konfiguraci', 'blockids-configurator'),
        ), $atts);
        
        $launch_url = add_query_arg('blockids_launch', '1', home_url('/'));
        
        if (is_user_logged_in()) {
            return '<a href="' . esc_url($launch_url) . '" class="' . esc_attr($atts['class']) . '">' 
                . esc_html($atts['text']) . '</a>';
        } else {
            $login_url = wp_login_url($launch_url);
            return '<a href="' . esc_url($login_url) . '" class="' . esc_attr($atts['class']) . '">' 
                . esc_html($atts['login_text']) . '</a>';
        }
    }
    
    /**
     * CORS headers pro konfigurátor
     */
    public function add_cors_headers() {
        $configurator_url = get_option('blockids_configurator_url', 'https://configurator.blockids.eu');
        $configurator_url = rtrim($configurator_url, '/');
        
        add_filter('rest_pre_serve_request', function($value) use ($configurator_url) {
            $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
            
            $allowed_origins = array(
                $configurator_url,
                'http://localhost:3000',
                'http://localhost:3001',
            );
            
            if (in_array($origin, $allowed_origins)) {
                header('Access-Control-Allow-Origin: ' . $origin);
                header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
                header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept');
                header('Access-Control-Allow-Credentials: true');
            }
            
            return $value;
        });
        
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
            $allowed_origins = array(
                $configurator_url,
                'http://localhost:3000',
                'http://localhost:3001',
            );
            
            if (in_array($origin, $allowed_origins)) {
                header('Access-Control-Allow-Origin: ' . $origin);
                header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
                header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept');
                header('Access-Control-Allow-Credentials: true');
                header('Access-Control-Max-Age: 86400');
                status_header(200);
                exit;
            }
        }
    }
    
    public function woocommerce_missing_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php _e('BLOCKids Konfigurátor vyžaduje aktivní WooCommerce plugin.', 'blockids-configurator'); ?></p>
        </div>
        <?php
    }
}

function blockids_configurator() {
    return BLOCKids_Configurator::get_instance();
}

blockids_configurator();