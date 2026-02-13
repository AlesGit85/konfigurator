<?php
/**
 * Plans management
 */

if (!defined('ABSPATH')) {
    exit;
}

class BLOCKids_Configurator_Plans {
    
    public static function init() {
        // Hooks
    }
    
    /**
     * Create new plan
     */
    public static function create_plan($user_id, $location = 'indoor') {
        global $wpdb;
        
        $table = $wpdb->prefix . 'blockids_plans';
        $access_hash = self::generate_access_hash();
        
        $inserted = $wpdb->insert(
            $table,
            array(
                'user_id' => $user_id,
                'access_hash' => $access_hash,
                'title' => __('Můj návrh', 'blockids-configurator'),
                'status' => 'draft',
                'location' => $location,
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($inserted) {
            return array(
                'id' => $wpdb->insert_id,
                'access_hash' => $access_hash
            );
        }
        
        return false;
    }
    
    /**
     * Get plan by access hash
     */
    public static function get_plan_by_hash($hash, $user_id = null) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'blockids_plans';
        
        $sql = "SELECT * FROM $table WHERE access_hash = %s";
        $params = array($hash);
        
        if ($user_id) {
            $sql .= " AND user_id = %d";
            $params[] = $user_id;
        }
        
        $plan = $wpdb->get_row($wpdb->prepare($sql, $params), ARRAY_A);
        
        if (!$plan) {
            return false;
        }
        
        return self::format_plan_response($plan);
    }
    
    /**
     * Update plan
     */
    public static function update_plan($hash, $user_id, $data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'blockids_plans';
        
        $update_data = array(
            'updated_at' => current_time('mysql')
        );
        
        if (isset($data['orientation'])) {
            $update_data['orientation'] = $data['orientation'];
        }
        if (isset($data['calculatedWidth'])) {
            $update_data['calculated_width'] = (int) $data['calculatedWidth'];
        }
        if (isset($data['calculatedHeight'])) {
            $update_data['calculated_height'] = (int) $data['calculatedHeight'];
        }
        if (isset($data['customWidth'])) {
            $update_data['custom_width'] = (int) $data['customWidth'];
        }
        if (isset($data['customHeight'])) {
            $update_data['custom_height'] = (int) $data['customHeight'];
        }
        if (isset($data['grip'])) {
            $update_data['grip_id'] = (int) $data['grip'];
        }
        if (isset($data['gripQuantity'])) {
            $update_data['grip_quantity'] = (int) $data['gripQuantity'];
        }
        if (isset($data['mattress'])) {
            $update_data['mattress_id'] = (int) $data['mattress'];
        }
        if (isset($data['mattressQuantity'])) {
            $update_data['mattress_quantity'] = (int) $data['mattressQuantity'];
        }
        if (isset($data['workspace'])) {
            $update_data['workspace'] = json_encode($data['workspace']);
        }
        
        // Store complete data for reference
        $update_data['plan_data'] = json_encode($data);
        
        // Calculate price
        $update_data['total_price'] = self::calculate_price($data);
        
        $updated = $wpdb->update(
            $table,
            $update_data,
            array(
                'access_hash' => $hash,
                'user_id' => $user_id
            )
        );
        
        return $updated !== false;
    }
    
    /**
     * Confirm plan
     */
    public static function confirm_plan($hash, $user_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'blockids_plans';
        
        return $wpdb->update(
            $table,
            array('status' => 'confirmed'),
            array(
                'access_hash' => $hash,
                'user_id' => $user_id
            )
        );
    }
    
    /**
     * Delete plan
     */
    public static function delete_plan($hash, $user_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'blockids_plans';
        
        return $wpdb->delete(
            $table,
            array(
                'access_hash' => $hash,
                'user_id' => $user_id
            )
        );
    }
    
    /**
     * Get user's draft plan
     */
    public static function get_user_draft_plan($user_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'blockids_plans';
        
        $plan = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d AND status = 'draft' ORDER BY updated_at DESC LIMIT 1",
            $user_id
        ));
        
        return $plan;
    }
    
    /**
     * Get all user plans
     */
    public static function get_user_plans($user_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'blockids_plans';
        
        $plans = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d ORDER BY updated_at DESC",
            $user_id
        ), ARRAY_A);
        
        $formatted = array();
        foreach ($plans as $plan) {
            $formatted[] = array(
                'id' => $plan['id'],
                'accessHash' => $plan['access_hash'],
                'title' => $plan['title'],
                'status' => $plan['status']
            );
        }
        
        return $formatted;
    }
    
    /**
     * Format plan for API response
     */
    private static function format_plan_response($plan) {
        // Get product details
        $grip = null;
        if ($plan['grip_id']) {
            $grip_product = wc_get_product($plan['grip_id']);
            if ($grip_product) {
                $grip = array(
                    'id' => $grip_product->get_id(),
                    'title' => $grip_product->get_name(),
                    'price' => (int) $grip_product->get_price(),
                    'currency' => 'czk',
                    'image' => wp_get_attachment_url($grip_product->get_image_id())
                );
            }
        }
        
        $mattress = null;
        if ($plan['mattress_id']) {
            $mattress_product = wc_get_product($plan['mattress_id']);
            if ($mattress_product) {
                $mattress = array(
                    'id' => $mattress_product->get_id(),
                    'title' => $mattress_product->get_name(),
                    'price' => (int) $mattress_product->get_price(),
                    'currency' => 'czk',
                    'image' => wp_get_attachment_url($mattress_product->get_image_id())
                );
            }
        }
        
        $workspace = $plan['workspace'] ? json_decode($plan['workspace'], true) : array();
        
        return array(
            'id' => $plan['id'],
            'accessHash' => $plan['access_hash'],
            'status' => $plan['status'],
            'title' => $plan['title'],
            'location' => $plan['location'],
            'orientation' => $plan['orientation'],
            'calculatedWidth' => (int) $plan['calculated_width'],
            'calculatedHeight' => (int) $plan['calculated_height'],
            'customWidth' => (int) $plan['custom_width'],
            'customHeight' => (int) $plan['custom_height'],
            'workspace' => $workspace,
            'grip' => $grip,
            'gripQuantity' => (int) $plan['grip_quantity'],
            'mattress' => $mattress,
            'mattressQuantity' => (int) $plan['mattress_quantity']
        );
    }
    
    /**
     * Calculate total price based on configurator rules
     * 
     * Vzorec od vývojářů:
     * desky = součet cen všech desek ve workspace + (cena gripu × počet)
     * matrace = cena matrace (fixní nebo dle šířky)
     * design config = materialPrice × 0.10 (vždy)
     * vlastní rozměry = desky × 0.10 (jen pokud custom rozměry < vypočítané)
     */
    private static function calculate_price($data) {
        $total = 0;
        
        // 1. Cena desek z workspace
        $desk_price = 0;
        if (isset($data['workspace']) && is_array($data['workspace'])) {
            foreach ($data['workspace'] as $position => $item) {
                if (isset($item['id']) && $item['id']) {
                    $desk_product = wc_get_product($item['id']);
                    if ($desk_product) {
                        $desk_price += (float) $desk_product->get_price();
                    }
                }
            }
        }
        
        // 2. Cena gripů
        $grip_price = 0;
        if (isset($data['grip']) && $data['grip'] && isset($data['gripQuantity'])) {
            $grip_product = wc_get_product($data['grip']);
            if ($grip_product) {
                $grip_price = (float) $grip_product->get_price() * (int) $data['gripQuantity'];
            }
        }
        
        $material_price = $desk_price + $grip_price;
        
        // 3. Cena matrací
        $mattress_price = 0;
        if (isset($data['mattress']) && $data['mattress'] && isset($data['mattressQuantity'])) {
            $mattress_product = wc_get_product($data['mattress']);
            if ($mattress_product) {
                $mattress_price = (float) $mattress_product->get_price() * (int) $data['mattressQuantity'];
            }
        }
        
        // 4. Design config (10% z materiálové ceny)
        $design_config = $material_price * 0.10;
        
        // 5. Custom rozměry (10% z ceny desek, jen pokud custom < calculated)
        $custom_dimensions = 0;
        if (isset($data['customWidth'], $data['calculatedWidth'], $data['customHeight'], $data['calculatedHeight'])) {
            $custom_area = $data['customWidth'] * $data['customHeight'];
            $calculated_area = $data['calculatedWidth'] * $data['calculatedHeight'];
            
            if ($custom_area < $calculated_area) {
                $custom_dimensions = $desk_price * 0.10;
            }
        }
        
        $total = $material_price + $mattress_price + $design_config + $custom_dimensions;
        
        return round($total, 2);
    }
    
    /**
     * Generate unique access hash
     */
    private static function generate_access_hash() {
        return substr(md5(uniqid(rand(), true)), 0, 12);
    }
}
