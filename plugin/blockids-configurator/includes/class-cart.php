<?php
/**
 * WooCommerce Cart integration
 * 
 * VERZE: 2.3.0
 * 
 * ZMĚNY:
 * - Meta box s detailem návrhu přímo v objednávce
 * - Automatická synchronizace statusu návrhu se stavem objednávky:
 *   objednáno → "ordered", dokončeno → "completed"
 * - Auto-login přes JWT cookie při návratu z konfiguratoru
 */

if (!defined('ABSPATH')) {
    exit;
}

class BLOCKids_Configurator_Cart {
    
    public static function init() {
        // Handle redirect from configurator
        add_action('template_redirect', array(__CLASS__, 'handle_configurator_redirect'));
        
        // Custom price pro plan v košíku
        add_action('woocommerce_before_calculate_totals', array(__CLASS__, 'set_custom_price'), 20, 1);
        
        // Unikátní cart item
        add_filter('woocommerce_add_cart_item_data', array(__CLASS__, 'add_plan_to_cart_item'), 10, 2);
        
        // Display plan data in cart
        add_filter('woocommerce_get_item_data', array(__CLASS__, 'display_plan_in_cart'), 10, 2);
        
        // Add plan data to order
        add_action('woocommerce_checkout_create_order_line_item', array(__CLASS__, 'add_plan_to_order_item'), 10, 4);
        
        // Display plan in order items (admin + customer email)
        add_action('woocommerce_order_item_meta_end', array(__CLASS__, 'display_plan_in_order'), 10, 3);
        
        // Meta box v detailu objednávky (admin)
        add_action('add_meta_boxes', array(__CLASS__, 'add_order_meta_box'));
        
        // Synchronizace statusu návrhu se stavem objednávky
        add_action('woocommerce_order_status_changed', array(__CLASS__, 'sync_plan_status_with_order'), 10, 4);
        
        // Při vytvoření objednávky (checkout) označit plány jako "ordered"
        add_action('woocommerce_checkout_order_processed', array(__CLASS__, 'mark_plans_as_ordered'), 10, 3);
    }
    
    // =========================================================================
    // AUTO-LOGIN Z COOKIE
    // =========================================================================
    
    private static function auto_login_from_cookie() {
        $user_id = get_current_user_id();
        if ($user_id) {
            return $user_id;
        }
        
        if (empty($_COOKIE['blockids_auth_token'])) {
            return 0;
        }
        
        $token = sanitize_text_field($_COOKIE['blockids_auth_token']);
        $user_data = BLOCKids_Configurator_Auth::validate_token($token);
        
        if (!$user_data || !isset($user_data->user_id)) {
            setcookie('blockids_auth_token', '', time() - 3600, '/');
            return 0;
        }
        
        $user = get_user_by('id', $user_data->user_id);
        if (!$user) {
            return 0;
        }
        
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, true);
        setcookie('blockids_auth_token', '', time() - 3600, '/');
        
        return $user->ID;
    }
    
    // =========================================================================
    // REDIRECT HANDLERS
    // =========================================================================
    
    public static function handle_configurator_redirect() {
        if (isset($_GET['blockids_confirm'])) {
            self::handle_confirm_redirect();
            return;
        }
        
        if (isset($_GET['plan'])) {
            self::handle_plan_redirect();
            return;
        }
    }
    
    private static function handle_confirm_redirect() {
        $user_id = self::auto_login_from_cookie();
        
        if (!$user_id) {
            wp_redirect(wp_login_url(add_query_arg('blockids_confirm', '1', home_url('/'))));
            exit;
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'blockids_plans';
        
        $plan_row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d AND status = 'confirmed' ORDER BY updated_at DESC LIMIT 1",
            $user_id
        ), ARRAY_A);
        
        if (!$plan_row) {
            wc_add_notice(__('Nebyl nalezen žádný potvrzený návrh.', 'blockids-configurator'), 'error');
            wp_redirect(wc_get_page_permalink('shop'));
            exit;
        }
        
        $plan = BLOCKids_Configurator_Plans::get_plan_by_hash($plan_row['access_hash'], $user_id);
        
        if (!$plan) {
            wc_add_notice(__('Návrh nebyl nalezen.', 'blockids-configurator'), 'error');
            wp_redirect(wc_get_page_permalink('shop'));
            exit;
        }
        
        self::add_plan_to_wc_cart($plan);
        
        $wpdb->update(
            $table,
            array('status' => 'in_cart'),
            array('access_hash' => $plan['accessHash'], 'user_id' => $user_id)
        );
        
        wp_redirect(wc_get_cart_url());
        exit;
    }
    
    private static function handle_plan_redirect() {
        $access_hash = sanitize_text_field($_GET['plan']);
        $user_id = self::auto_login_from_cookie();
        
        if (!$user_id) {
            wp_redirect(wp_login_url(add_query_arg('plan', $access_hash, wc_get_cart_url())));
            exit;
        }
        
        $plan = BLOCKids_Configurator_Plans::get_plan_by_hash($access_hash, $user_id);
        
        if (!$plan) {
            wc_add_notice(__('Návrh nebyl nalezen.', 'blockids-configurator'), 'error');
            wp_redirect(wc_get_page_permalink('shop'));
            exit;
        }
        
        self::add_plan_to_wc_cart($plan);
        
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'blockids_plans',
            array('status' => 'in_cart'),
            array('access_hash' => $access_hash, 'user_id' => $user_id)
        );
        
        wp_redirect(wc_get_cart_url());
        exit;
    }
    
    // =========================================================================
    // PŘIDÁNÍ DO KOŠÍKU
    // =========================================================================
    
    private static function add_plan_to_wc_cart($plan) {
        $product_id = self::get_or_create_custom_wall_product();
        
        $orientation_label = $plan['orientation'] === 'horizontal' 
            ? __('Horizontální', 'blockids-configurator') 
            : __('Vertikální', 'blockids-configurator');
        
        $cart_item_data = array(
            'blockids_plan' => array(
                'access_hash' => $plan['accessHash'],
                'title' => $plan['title'],
                'location' => $plan['location'],
                'orientation' => $plan['orientation'],
                'orientation_label' => $orientation_label,
                'dimensions' => $plan['calculatedWidth'] . ' × ' . $plan['calculatedHeight'] . ' cm',
                'custom_dimensions' => $plan['customWidth'] . ' × ' . $plan['customHeight'] . ' cm',
                'grip_title' => $plan['grip'] ? $plan['grip']['title'] : '',
                'grip_quantity' => $plan['gripQuantity'],
                'mattress_title' => $plan['mattress'] ? $plan['mattress']['title'] : '',
                'mattress_quantity' => $plan['mattressQuantity'],
                'total_price' => self::get_plan_total_price($plan),
            )
        );
        
        WC()->cart->add_to_cart($product_id, 1, 0, array(), $cart_item_data);
        
        wc_add_notice(__('Návrh lezecké stěny byl přidán do košíku.', 'blockids-configurator'), 'success');
    }
    
    private static function get_plan_total_price($plan) {
        global $wpdb;
        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT total_price FROM {$wpdb->prefix}blockids_plans WHERE access_hash = %s",
            $plan['accessHash']
        ));
        return ($total && (float) $total > 0) ? (float) $total : 0;
    }
    
    private static function get_or_create_custom_wall_product() {
        $product_id = get_option('blockids_custom_wall_product_id');
        
        if ($product_id && get_post($product_id)) {
            return $product_id;
        }
        
        $product = new WC_Product_Simple();
        $product->set_name(__('Vlastní lezecká stěna', 'blockids-configurator'));
        $product->set_slug('vlastni-lezecka-stena');
        $product->set_regular_price(0);
        $product->set_virtual(true);
        $product->set_catalog_visibility('hidden');
        $product->set_status('publish');
        $product_id = $product->save();
        
        update_option('blockids_custom_wall_product_id', $product_id);
        
        return $product_id;
    }
    
    // =========================================================================
    // CART DISPLAY & PRICE
    // =========================================================================
    
    public static function set_custom_price($cart) {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }
        
        foreach ($cart->get_cart() as $cart_item) {
            if (isset($cart_item['blockids_plan']) && !empty($cart_item['blockids_plan']['total_price'])) {
                $price = (float) $cart_item['blockids_plan']['total_price'];
                if ($price > 0) {
                    $cart_item['data']->set_price($price);
                }
            }
        }
    }
    
    public static function add_plan_to_cart_item($cart_item_data, $product_id) {
        if (isset($cart_item_data['blockids_plan'])) {
            $cart_item_data['unique_key'] = md5($cart_item_data['blockids_plan']['access_hash']);
        }
        return $cart_item_data;
    }
    
    public static function display_plan_in_cart($item_data, $cart_item) {
        if (!isset($cart_item['blockids_plan'])) {
            return $item_data;
        }
        
        $plan = $cart_item['blockids_plan'];
        
        $item_data[] = array('name' => 'Konfigurace', 'value' => $plan['title']);
        $item_data[] = array('name' => 'Rozměry', 'value' => $plan['dimensions']);
        $item_data[] = array('name' => 'Orientace', 'value' => $plan['orientation_label']);
        
        if (!empty($plan['grip_title'])) {
            $item_data[] = array('name' => 'Chyty', 'value' => $plan['grip_title'] . ' (' . $plan['grip_quantity'] . '×)');
        }
        
        if (!empty($plan['mattress_title'])) {
            $item_data[] = array('name' => 'Matrace', 'value' => $plan['mattress_title'] . ' (' . $plan['mattress_quantity'] . '×)');
        }
        
        return $item_data;
    }
    
    // =========================================================================
    // ORDER ITEMS
    // =========================================================================
    
    public static function add_plan_to_order_item($item, $cart_item_key, $values, $order) {
        if (isset($values['blockids_plan'])) {
            $item->add_meta_data('_blockids_plan', $values['blockids_plan'], true);
        }
    }
    
    /**
     * Zobrazení plánu pod položkou objednávky (v seznamu položek)
     */
    public static function display_plan_in_order($item_id, $item, $order) {
        $plan = $item->get_meta('_blockids_plan');
        if (!$plan) return;
        
        echo '<div style="margin-top: 8px; padding: 8px 12px; background: #f0f6fc; border-left: 3px solid #0073aa; border-radius: 3px; font-size: 13px;">';
        echo '<strong>🧗 ' . esc_html($plan['title']) . '</strong> — ';
        echo esc_html($plan['dimensions']) . ', ' . esc_html($plan['orientation_label']);
        
        if (!empty($plan['grip_title'])) {
            echo ' | Chyty: ' . esc_html($plan['grip_title']) . ' (' . $plan['grip_quantity'] . '×)';
        }
        if (!empty($plan['mattress_title'])) {
            echo ' | Matrace: ' . esc_html($plan['mattress_title']) . ' (' . $plan['mattress_quantity'] . '×)';
        }
        
        if (is_admin() && !empty($plan['access_hash'])) {
            $url = admin_url('admin.php?page=blockids-plan-detail&hash=' . $plan['access_hash']);
            echo ' <a href="' . esc_url($url) . '" style="margin-left: 5px;">📐 Detail návrhu →</a>';
        }
        
        echo '</div>';
    }
    
    // =========================================================================
    // META BOX V DETAILU OBJEDNÁVKY
    // =========================================================================
    
    /**
     * Přidat meta box s detailem návrhu do objednávky
     */
    public static function add_order_meta_box() {
        // Klasické objednávky (post type)
        add_meta_box(
            'blockids_plan_order_box',
            '🧗 Návrh lezecké stěny',
            array(__CLASS__, 'render_order_meta_box'),
            'shop_order',
            'normal',
            'high'
        );
        
        // HPOS objednávky
        add_meta_box(
            'blockids_plan_order_box',
            '🧗 Návrh lezecké stěny',
            array(__CLASS__, 'render_order_meta_box'),
            'woocommerce_page_wc-orders',
            'normal',
            'high'
        );
    }
    
    /**
     * Render meta boxu v objednávce
     */
    public static function render_order_meta_box($post_or_order) {
        // HPOS kompatibilita
        if ($post_or_order instanceof WP_Post) {
            $order = wc_get_order($post_or_order->ID);
        } elseif ($post_or_order instanceof WC_Order) {
            $order = $post_or_order;
        } else {
            $order = wc_get_order($post_or_order);
        }
        
        if (!$order) {
            echo '<p style="color:#999;">Objednávka nenalezena.</p>';
            return;
        }
        
        // Najít plány v položkách objednávky
        $found = false;
        
        foreach ($order->get_items() as $item) {
            $plan = $item->get_meta('_blockids_plan');
            if (!$plan || empty($plan['access_hash'])) continue;
            
            $found = true;
            $detail_url = admin_url('admin.php?page=blockids-plan-detail&hash=' . $plan['access_hash']);
            
            // Načíst stav návrhu z DB
            global $wpdb;
            $plan_row = $wpdb->get_row($wpdb->prepare(
                "SELECT status, calculated_width, calculated_height, custom_width, custom_height, location, orientation, total_price FROM {$wpdb->prefix}blockids_plans WHERE access_hash = %s",
                $plan['access_hash']
            ), ARRAY_A);
            
            $statuses = array(
                'draft' => array('label' => 'Rozpracováno', 'color' => '#dba617'),
                'confirmed' => array('label' => 'Potvrzeno', 'color' => '#0073aa'),
                'in_cart' => array('label' => 'V košíku', 'color' => '#e65100'),
                'ordered' => array('label' => 'Objednáno', 'color' => '#2e7d32'),
                'completed' => array('label' => 'Dokončeno', 'color' => '#1b5e20'),
            );
            $st = $statuses[$plan_row['status'] ?? ''] ?? array('label' => $plan_row['status'] ?? '?', 'color' => '#666');
            
            ?>
            <div style="display: flex; gap: 20px; align-items: flex-start; flex-wrap: wrap;">
                
                <!-- Základní info -->
                <div style="flex: 1; min-width: 250px;">
                    <table style="width:100%; border-collapse: collapse;">
                        <tr>
                            <td style="padding: 6px 0; color: #666; width: 130px;">Název návrhu</td>
                            <td style="padding: 6px 0;"><strong><?php echo esc_html($plan['title']); ?></strong></td>
                        </tr>
                        <tr>
                            <td style="padding: 6px 0; color: #666;">Stav návrhu</td>
                            <td style="padding: 6px 0;">
                                <span style="display: inline-block; padding: 2px 10px; border-radius: 10px; font-size: 12px; font-weight: 600; color: white; background: <?php echo $st['color']; ?>;">
                                    <?php echo $st['label']; ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 6px 0; color: #666;">Umístění</td>
                            <td style="padding: 6px 0;"><?php echo ($plan_row['location'] ?? '') === 'outdoor' ? '🌳 Exteriér' : '🏠 Interiér'; ?></td>
                        </tr>
                        <tr>
                            <td style="padding: 6px 0; color: #666;">Orientace</td>
                            <td style="padding: 6px 0;"><?php echo esc_html($plan['orientation_label']); ?></td>
                        </tr>
                        <tr>
                            <td style="padding: 6px 0; color: #666;">Rozměry stěny</td>
                            <td style="padding: 6px 0;">
                                <?php echo esc_html($plan['dimensions']); ?>
                                <?php if (!empty($plan['custom_dimensions']) && $plan['custom_dimensions'] !== $plan['dimensions']) : ?>
                                <br><small style="color:#e65100;">Vlastní: <?php echo esc_html($plan['custom_dimensions']); ?></small>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 6px 0; color: #666;">Chyty</td>
                            <td style="padding: 6px 0;">
                                <?php echo !empty($plan['grip_title']) ? esc_html($plan['grip_title']) . ' (' . $plan['grip_quantity'] . '×)' : '<span style="color:#999;">—</span>'; ?>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 6px 0; color: #666;">Matrace</td>
                            <td style="padding: 6px 0;">
                                <?php echo !empty($plan['mattress_title']) ? esc_html($plan['mattress_title']) . ' (' . $plan['mattress_quantity'] . '×)' : '<span style="color:#999;">—</span>'; ?>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 6px 0; color: #666;">Cena konfigurace</td>
                            <td style="padding: 6px 0;">
                                <strong style="font-size: 15px; color: #2e7d32;">
                                    <?php echo $plan_row['total_price'] ? number_format((float) $plan_row['total_price'], 0, ',', ' ') . ' Kč' : '—'; ?>
                                </strong>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 6px 0; color: #666;">Kód návrhu</td>
                            <td style="padding: 6px 0;"><code style="font-size: 12px;"><?php echo esc_html($plan['access_hash']); ?></code></td>
                        </tr>
                    </table>
                </div>
                
                <!-- Tlačítko na detail -->
                <div style="text-align: center; padding: 20px;">
                    <a href="<?php echo esc_url($detail_url); ?>" class="button button-primary button-large" style="font-size: 14px; padding: 8px 20px;">
                        📐 Zobrazit kompletní návrh s mřížkou
                    </a>
                    <p style="margin-top: 8px; color: #666; font-size: 12px;">
                        Vizuální rozložení desek, rozklad ceny,<br>
                        informace o zákazníkovi
                    </p>
                </div>
                
            </div>
            <?php
        }
        
        if (!$found) {
            echo '<p style="color:#999; padding: 10px 0;">Tato objednávka neobsahuje návrh lezecké stěny z konfiguratoru.</p>';
        }
    }
    
    // =========================================================================
    // SYNCHRONIZACE STATUSU NÁVRHU S OBJEDNÁVKOU
    // =========================================================================
    
    /**
     * Při vytvoření objednávky (checkout) → plány na "ordered"
     */
    public static function mark_plans_as_ordered($order_id, $posted_data, $order) {
        if (!$order) {
            $order = wc_get_order($order_id);
        }
        if (!$order) return;
        
        self::update_plan_statuses_from_order($order, 'ordered');
    }
    
    /**
     * Při změně stavu objednávky → aktualizovat status návrhu
     * 
     * Mapování:
     * - processing, on-hold, pending → ordered
     * - completed → completed
     * - cancelled, refunded, failed → in_cart (vrátit zpět)
     */
    public static function sync_plan_status_with_order($order_id, $old_status, $new_status, $order) {
        if (!$order) {
            $order = wc_get_order($order_id);
        }
        if (!$order) return;
        
        // Mapování WooCommerce stavu na stav návrhu
        $status_map = array(
            'pending' => 'ordered',
            'processing' => 'ordered',
            'on-hold' => 'ordered',
            'completed' => 'completed',
            'cancelled' => 'in_cart',
            'refunded' => 'in_cart',
            'failed' => 'in_cart',
        );
        
        $plan_status = $status_map[$new_status] ?? null;
        
        if ($plan_status) {
            self::update_plan_statuses_from_order($order, $plan_status);
        }
    }
    
    /**
     * Aktualizovat status všech návrhů v objednávce
     */
    private static function update_plan_statuses_from_order($order, $new_plan_status) {
        global $wpdb;
        $table = $wpdb->prefix . 'blockids_plans';
        
        foreach ($order->get_items() as $item) {
            $plan = $item->get_meta('_blockids_plan');
            if (!$plan || empty($plan['access_hash'])) continue;
            
            $wpdb->update(
                $table,
                array('status' => $new_plan_status),
                array('access_hash' => $plan['access_hash'])
            );
        }
    }
}