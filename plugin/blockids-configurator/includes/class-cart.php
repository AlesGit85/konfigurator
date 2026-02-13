<?php
/**
 * WooCommerce Cart integration
 */

if (!defined('ABSPATH')) {
    exit;
}

class BLOCKids_Configurator_Cart {
    
    public static function init() {
        // Handle redirect from configurator
        add_action('template_redirect', array(__CLASS__, 'handle_configurator_redirect'));
        
        // Add plan data to cart item
        add_filter('woocommerce_add_cart_item_data', array(__CLASS__, 'add_plan_to_cart_item'), 10, 2);
        
        // Display plan data in cart
        add_filter('woocommerce_get_item_data', array(__CLASS__, 'display_plan_in_cart'), 10, 2);
        
        // Add plan data to order
        add_action('woocommerce_checkout_create_order_line_item', array(__CLASS__, 'add_plan_to_order_item'), 10, 4);
        
        // Display plan in order details
        add_action('woocommerce_order_item_meta_end', array(__CLASS__, 'display_plan_in_order'), 10, 3);
    }
    
    /**
     * Handle redirect from configurator with ?plan=xxx
     */
    public static function handle_configurator_redirect() {
        if (!isset($_GET['plan'])) {
            return;
        }
        
        $access_hash = sanitize_text_field($_GET['plan']);
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            // Redirect to login
            wp_redirect(wp_login_url(add_query_arg('plan', $access_hash, wc_get_cart_url())));
            exit;
        }
        
        // Get plan details
        $plan = BLOCKids_Configurator_Plans::get_plan_by_hash($access_hash, $user_id);
        
        if (!$plan) {
            wc_add_notice(__('Návrh nebyl nalezen.', 'blockids-configurator'), 'error');
            wp_redirect(wc_get_page_permalink('shop'));
            exit;
        }
        
        // Add plan to cart
        self::add_plan_to_wc_cart($plan);
        
        // Redirect to cart
        wp_redirect(wc_get_cart_url());
        exit;
    }
    
    /**
     * Add plan to WooCommerce cart
     */
    private static function add_plan_to_wc_cart($plan) {
        // Get or create virtual "Custom Wall" product
        $product_id = self::get_or_create_custom_wall_product();
        
        // Prepare cart item data
        $cart_item_data = array(
            'blockids_plan' => array(
                'access_hash' => $plan['accessHash'],
                'title' => $plan['title'],
                'location' => $plan['location'],
                'orientation' => $plan['orientation'],
                'dimensions' => $plan['calculatedWidth'] . 'x' . $plan['calculatedHeight'] . ' cm',
                'grip_title' => $plan['grip'] ? $plan['grip']['title'] : '',
                'grip_quantity' => $plan['gripQuantity'],
                'mattress_title' => $plan['mattress'] ? $plan['mattress']['title'] : __('Žádná', 'blockids-configurator'),
                'mattress_quantity' => $plan['mattressQuantity']
            )
        );
        
        // Add to cart
        WC()->cart->add_to_cart($product_id, 1, 0, array(), $cart_item_data);
        
        wc_add_notice(__('Návrh lezecké stěny byl přidán do košíku.', 'blockids-configurator'), 'success');
    }
    
    /**
     * Get or create virtual product for custom walls
     */
    private static function get_or_create_custom_wall_product() {
        $product_id = get_option('blockids_custom_wall_product_id');
        
        // Check if product exists
        if ($product_id && get_post($product_id)) {
            return $product_id;
        }
        
        // Create new virtual product
        $product = new WC_Product_Simple();
        $product->set_name(__('Vlastní lezecká stěna', 'blockids-configurator'));
        $product->set_slug('vlastni-lezecka-stena');
        $product->set_regular_price(0); // Price will be calculated from plan
        $product->set_virtual(true);
        $product->set_sold_individually(true);
        $product->set_catalog_visibility('hidden');
        $product_id = $product->save();
        
        update_option('blockids_custom_wall_product_id', $product_id);
        
        return $product_id;
    }
    
    /**
     * Add plan data to cart item
     */
    public static function add_plan_to_cart_item($cart_item_data, $product_id) {
        // This filter is called when adding to cart
        // We already have the data from handle_configurator_redirect
        return $cart_item_data;
    }
    
    /**
     * Display plan details in cart
     */
    public static function display_plan_in_cart($item_data, $cart_item) {
        if (!isset($cart_item['blockids_plan'])) {
            return $item_data;
        }
        
        $plan = $cart_item['blockids_plan'];
        
        $item_data[] = array(
            'name' => __('Konfigurace', 'blockids-configurator'),
            'value' => $plan['title']
        );
        
        $item_data[] = array(
            'name' => __('Rozměry', 'blockids-configurator'),
            'value' => $plan['dimensions']
        );
        
        $item_data[] = array(
            'name' => __('Orientace', 'blockids-configurator'),
            'value' => $plan['orientation'] === 'horizontal' ? __('Horizontální', 'blockids-configurator') : __('Vertikální', 'blockids-configurator')
        );
        
        if ($plan['grip_title']) {
            $item_data[] = array(
                'name' => __('Chyty', 'blockids-configurator'),
                'value' => $plan['grip_title'] . ' (' . $plan['grip_quantity'] . 'x)'
            );
        }
        
        if ($plan['mattress_title'] && $plan['mattress_title'] !== __('Žádná', 'blockids-configurator')) {
            $item_data[] = array(
                'name' => __('Matrace', 'blockids-configurator'),
                'value' => $plan['mattress_title'] . ' (' . $plan['mattress_quantity'] . 'x)'
            );
        }
        
        return $item_data;
    }
    
    /**
     * Add plan data to order item
     */
    public static function add_plan_to_order_item($item, $cart_item_key, $values, $order) {
        if (isset($values['blockids_plan'])) {
            $item->add_meta_data('_blockids_plan', $values['blockids_plan'], true);
        }
    }
    
    /**
     * Display plan in order details
     */
    public static function display_plan_in_order($item_id, $item, $order) {
        $plan = $item->get_meta('_blockids_plan');
        
        if (!$plan) {
            return;
        }
        
        echo '<div class="blockids-plan-details" style="margin-top: 10px; padding: 10px; background: #f5f5f5;">';
        echo '<strong>' . __('Konfigurace lezecké stěny:', 'blockids-configurator') . '</strong><br>';
        echo __('Název:', 'blockids-configurator') . ' ' . esc_html($plan['title']) . '<br>';
        echo __('Rozměry:', 'blockids-configurator') . ' ' . esc_html($plan['dimensions']) . '<br>';
        echo __('Orientace:', 'blockids-configurator') . ' ' . esc_html($plan['orientation']) . '<br>';
        
        if ($plan['grip_title']) {
            echo __('Chyty:', 'blockids-configurator') . ' ' . esc_html($plan['grip_title']) . ' (' . esc_html($plan['grip_quantity']) . 'x)<br>';
        }
        
        if ($plan['mattress_title'] && $plan['mattress_title'] !== __('Žádná', 'blockids-configurator')) {
            echo __('Matrace:', 'blockids-configurator') . ' ' . esc_html($plan['mattress_title']) . ' (' . esc_html($plan['mattress_quantity']) . 'x)<br>';
        }
        
        echo '<small><a href="' . esc_url(admin_url('admin.php?page=blockids-plan&hash=' . $plan['access_hash'])) . '">' . __('Zobrazit detail návrhu', 'blockids-configurator') . '</a></small>';
        echo '</div>';
    }
}
