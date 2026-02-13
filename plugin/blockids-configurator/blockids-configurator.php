<?php
/**
 * Plugin Name: BLOCKids Konfigurátor Integration
 * Plugin URI: https://blockids.eu
 * Description: Integrace konfiguratoru lezeckých stěn s WooCommerce eshopem
 * Version: 1.0.4
 * Author: Aleš
 * Author URI: https://blockids.eu
 * Text Domain: blockids-configurator
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * WC requires at least: 8.0
 * WC tested up to: 9.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Declare WooCommerce HPOS compatibility
add_action('before_woocommerce_init', function() {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
    }
});

// Plugin constants
define('BLOCKIDS_CONFIGURATOR_VERSION', '1.0.1');
define('BLOCKIDS_CONFIGURATOR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BLOCKIDS_CONFIGURATOR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BLOCKIDS_CONFIGURATOR_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Main plugin class
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
        // Core includes
        require_once BLOCKIDS_CONFIGURATOR_PLUGIN_DIR . 'includes/class-install.php';
        require_once BLOCKIDS_CONFIGURATOR_PLUGIN_DIR . 'includes/class-auth.php';
        require_once BLOCKIDS_CONFIGURATOR_PLUGIN_DIR . 'includes/class-api.php';
        require_once BLOCKIDS_CONFIGURATOR_PLUGIN_DIR . 'includes/class-plans.php';
        require_once BLOCKIDS_CONFIGURATOR_PLUGIN_DIR . 'includes/class-cart.php';
        
        // Admin
        if (is_admin()) {
            require_once BLOCKIDS_CONFIGURATOR_PLUGIN_DIR . 'admin/class-settings.php';
        }
    }
    
    private function init_hooks() {
        // Activation/Deactivation
        register_activation_hook(__FILE__, array('BLOCKids_Configurator_Install', 'activate'));
        register_deactivation_hook(__FILE__, array('BLOCKids_Configurator_Install', 'deactivate'));
        
        // Initialize components
        add_action('plugins_loaded', array($this, 'init'));
        add_action('rest_api_init', array('BLOCKids_Configurator_API', 'register_routes'));
    }
    
    public function init() {
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }
        
        // Load textdomain
        load_plugin_textdomain('blockids-configurator', false, dirname(BLOCKIDS_CONFIGURATOR_PLUGIN_BASENAME) . '/languages');
        
        // Initialize components
        BLOCKids_Configurator_Auth::init();
        BLOCKids_Configurator_Plans::init();
        BLOCKids_Configurator_Cart::init();
    }
    
    public function woocommerce_missing_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php _e('BLOCKids Konfigurátor vyžaduje aktivní WooCommerce plugin.', 'blockids-configurator'); ?></p>
        </div>
        <?php
    }
}

// Initialize plugin
function blockids_configurator() {
    return BLOCKids_Configurator::get_instance();
}

// Start the plugin
blockids_configurator();
