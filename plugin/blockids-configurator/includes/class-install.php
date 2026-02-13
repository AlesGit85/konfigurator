<?php
/**
 * Installation and database setup
 */

if (!defined('ABSPATH')) {
    exit;
}

class BLOCKids_Configurator_Install {
    
    public static function activate() {
        self::create_tables();
        self::create_options();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    public static function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    private static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Table for plans
        $table_plans = $wpdb->prefix . 'blockids_plans';
        
        $sql_plans = "CREATE TABLE IF NOT EXISTS $table_plans (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            access_hash varchar(255) NOT NULL,
            title varchar(255) NOT NULL DEFAULT 'Můj návrh',
            status varchar(20) NOT NULL DEFAULT 'draft',
            location varchar(20) NOT NULL DEFAULT 'indoor',
            orientation varchar(20) NOT NULL DEFAULT 'horizontal',
            calculated_width int(11) NOT NULL DEFAULT 0,
            calculated_height int(11) NOT NULL DEFAULT 0,
            custom_width int(11) NOT NULL DEFAULT 0,
            custom_height int(11) NOT NULL DEFAULT 0,
            grip_id int(11) DEFAULT NULL,
            grip_quantity int(11) NOT NULL DEFAULT 0,
            mattress_id int(11) DEFAULT NULL,
            mattress_quantity int(11) NOT NULL DEFAULT 0,
            workspace longtext,
            plan_data longtext,
            total_price decimal(10,2) DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY access_hash (access_hash),
            KEY user_id (user_id),
            KEY status (status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_plans);
    }
    
    private static function create_options() {
        // Default options
        $defaults = array(
            'configurator_url' => 'https://configurator.blockids.eu',
            'api_base_url' => 'https://api.blockids.eu/v1',
            'jwt_secret_key' => self::generate_secret_key(),
            'jwt_expiration' => 3600, // 1 hour
        );
        
        foreach ($defaults as $key => $value) {
            $option_name = 'blockids_' . $key;
            if (!get_option($option_name)) {
                add_option($option_name, $value);
            }
        }
    }
    
    private static function generate_secret_key() {
        return wp_generate_password(64, true, true);
    }
}
