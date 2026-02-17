<?php
/**
 * Plans management
 * 
 * VERZE: 2.0.0
 * 
 * ZMĚNY:
 * - format_plan_response: workspace vrací kompletní desk objekty (ne jen id+rotation)
 * - get_user_plans: vrací "name" místo "title" (DraftListItemType)
 * - Nová metoda: clone_plan()
 * - Nová metoda: change_title()
 */

if (!defined('ABSPATH')) {
    exit;
}

class BLOCKids_Configurator_Plans {
    
    public static function init() {
        // Hooks
    }
    
    /**
     * Generate unique access hash
     */
    private static function generate_access_hash() {
        return bin2hex(random_bytes(16));
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
                'orientation' => 'horizontal',
                'calculated_width' => 0,
                'calculated_height' => 0,
                'custom_width' => 0,
                'custom_height' => 0,
                'workspace' => json_encode(new stdClass()), // Prázdný objekt {}
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%s', '%s', '%s')
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
            $update_data['orientation'] = sanitize_text_field($data['orientation']);
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
            $update_data['grip_id'] = $data['grip'] ? (int) $data['grip'] : null;
        }
        if (isset($data['gripQuantity'])) {
            $update_data['grip_quantity'] = (int) $data['gripQuantity'];
        }
        if (isset($data['mattress'])) {
            $update_data['mattress_id'] = $data['mattress'] ? (int) $data['mattress'] : null;
        }
        if (isset($data['mattressQuantity'])) {
            $update_data['mattress_quantity'] = (int) $data['mattressQuantity'];
        }
        if (isset($data['workspace'])) {
            // Uložit workspace jako přijatý z konfiguratoru
            // Formát: { "A1": { "id": 5, "rotation": 0 }, "A2": "", ... }
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
            array(
                'status' => 'confirmed',
                'updated_at' => current_time('mysql')
            ),
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
     * Clone plan
     * NOVÁ metoda
     */
    public static function clone_plan($hash, $user_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'blockids_plans';
        
        // Najít originální plán
        $original = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE access_hash = %s AND user_id = %d",
            $hash, $user_id
        ), ARRAY_A);
        
        if (!$original) {
            return false;
        }
        
        $new_hash = self::generate_access_hash();
        
        $inserted = $wpdb->insert(
            $table,
            array(
                'user_id' => $user_id,
                'access_hash' => $new_hash,
                'title' => $original['title'] . ' (kopie)',
                'status' => 'draft',
                'location' => $original['location'],
                'orientation' => $original['orientation'],
                'calculated_width' => $original['calculated_width'],
                'calculated_height' => $original['calculated_height'],
                'custom_width' => $original['custom_width'],
                'custom_height' => $original['custom_height'],
                'grip_id' => $original['grip_id'],
                'grip_quantity' => $original['grip_quantity'],
                'mattress_id' => $original['mattress_id'],
                'mattress_quantity' => $original['mattress_quantity'],
                'workspace' => $original['workspace'],
                'plan_data' => $original['plan_data'],
                'total_price' => $original['total_price'],
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            )
        );
        
        if ($inserted) {
            return array(
                'id' => $wpdb->insert_id,
                'access_hash' => $new_hash
            );
        }
        
        return false;
    }
    
    /**
     * Change plan title
     * NOVÁ metoda
     */
    public static function change_title($hash, $user_id, $title) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'blockids_plans';
        
        $updated = $wpdb->update(
            $table,
            array(
                'title' => $title,
                'updated_at' => current_time('mysql')
            ),
            array(
                'access_hash' => $hash,
                'user_id' => $user_id
            )
        );
        
        return $updated !== false;
    }
    
    /**
     * Get user's draft plan (latest)
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
     * 
     * OPRAVA: Konfigurátor čeká DraftListItemType:
     * { id: number, name: string, accessHash: string }
     * → Vrací "name" místo "title"
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
                'id' => (int) $plan['id'],
                'name' => $plan['title'],       // OPRAVA: "name" ne "title"
                'accessHash' => $plan['access_hash'],
            );
        }
        
        return $formatted;
    }
    
    /**
     * Format plan for API response
     * 
     * OPRAVA: Workspace musí vracet kompletní desk objekty, ne jen id+rotation.
     * 
     * Konfigurátor v setInitialGrid čte:
     *   value.desk.id, value.desk.title, value.desk.image, 
     *   value.desk.type, value.desk.price, value.desk.currency
     *   value.rotation
     */
    private static function format_plan_response($plan) {
        // Get grip product details
        $grip = null;
        if ($plan['grip_id']) {
            $grip_product = wc_get_product($plan['grip_id']);
            if ($grip_product) {
                $overlays_raw = get_post_meta($grip_product->get_id(), '_blockids_overlays', true);
                $overlays = array();
                if ($overlays_raw) {
                    $decoded = json_decode($overlays_raw, true);
                    if (is_array($decoded)) {
                        $overlays = $decoded;
                    }
                }

                $grip = array(
                    'id' => $grip_product->get_id(),
                    'title' => $grip_product->get_name(),
                    'price' => (int) $grip_product->get_price(),
                    'currency' => 'czk',
                    'image' => wp_get_attachment_url($grip_product->get_image_id()) ?: '',
                    'overlays' => $overlays
                );
            }
        }
        
        // Get mattress product details
        $mattress = null;
        if ($plan['mattress_id']) {
            $mattress_product = wc_get_product($plan['mattress_id']);
            if ($mattress_product) {
                $color = get_post_meta($mattress_product->get_id(), '_blockids_color', true) ?: '';
                $personal = get_post_meta($mattress_product->get_id(), '_blockids_personal', true);
                $personal = ($personal === 'yes' || $personal === '1' || $personal === true);

                $prices_raw = get_post_meta($mattress_product->get_id(), '_blockids_prices', true);
                $prices = null;
                if ($prices_raw) {
                    $decoded = json_decode($prices_raw, true);
                    if (is_array($decoded)) {
                        $prices = $decoded;
                    }
                }

                $mattress = array(
                    'id' => $mattress_product->get_id(),
                    'title' => $mattress_product->get_name(),
                    'price' => (int) $mattress_product->get_price(),
                    'prices' => $prices,
                    'currency' => 'czk',
                    'image' => wp_get_attachment_url($mattress_product->get_image_id()) ?: '',
                    'color' => $color,
                    'personal' => $personal
                );
            }
        }
        
        // ===== WORKSPACE: Rozšířit o kompletní desk objekty =====
        $raw_workspace = $plan['workspace'] ? json_decode($plan['workspace'], true) : array();
        $workspace = new stdClass(); // Prázdný objekt jako default
        
        if (is_array($raw_workspace) && !empty($raw_workspace)) {
            $workspace = array();
            foreach ($raw_workspace as $position => $item) {
                if (empty($item) || $item === '') {
                    // Prázdná buňka
                    $workspace[$position] = '';
                } elseif (is_array($item) && isset($item['id'])) {
                    // Buňka s deskou - rozšířit o kompletní desk data
                    $desk_id = (int) $item['id'];
                    $rotation = isset($item['rotation']) ? (int) $item['rotation'] : 0;
                    
                    $desk_product = wc_get_product($desk_id);
                    if ($desk_product) {
                        $desk_type = get_post_meta($desk_id, '_blockids_type', true) ?: 'rectangle';
                        
                        $workspace[$position] = array(
                            'desk' => array(
                                'id' => $desk_product->get_id(),
                                'title' => $desk_product->get_name(),
                                'image' => wp_get_attachment_url($desk_product->get_image_id()) ?: '',
                                'type' => $desk_type,
                                'price' => (int) $desk_product->get_price(),
                                'currency' => 'czk',
                            ),
                            'rotation' => $rotation,
                        );
                    } else {
                        // Produkt neexistuje, ponechat prázdné
                        $workspace[$position] = '';
                    }
                } elseif (is_array($item) && isset($item['desk'])) {
                    // Už je v kompletním formátu (např. z původního API)
                    $workspace[$position] = $item;
                }
            }
        }
        
        return array(
            'id' => (int) $plan['id'],
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
            'mattressQuantity' => (int) $plan['mattress_quantity'],
        );
    }
    
    /**
     * Calculate total price
     * 
     * Vzorec:
     * desky = součet cen všech desek ve workspace
     * gripy = cena gripu × počet
     * materialPrice = desky + gripy
     * matrace = cena matrace × počet
     * design config = materialPrice × 0.10 (vždy)
     * custom rozměry = desky × 0.10 (jen pokud custom < calculated)
     * CELKEM = materialPrice + matrace + design config + custom rozměry
     */
    private static function calculate_price($data) {
        $total = 0;
        
        // 1. Cena desek z workspace
        $desk_price = 0;
        if (isset($data['workspace']) && is_array($data['workspace'])) {
            foreach ($data['workspace'] as $position => $item) {
                if (is_array($item) && isset($item['id']) && $item['id']) {
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
        
        // 4. Design config (10% z materiálové ceny - vždy)
        $design_config = $material_price * 0.10;
        
        // 5. Custom rozměry (10% z ceny desek - jen pokud custom < calculated)
        $custom_size_extra = 0;
        if (isset($data['customWidth']) && isset($data['calculatedWidth'])) {
            if ((int) $data['customWidth'] < (int) $data['calculatedWidth'] ||
                (int) $data['customHeight'] < (int) $data['calculatedHeight']) {
                $custom_size_extra = $desk_price * 0.10;
            }
        }
        
        $total = $material_price + $mattress_price + $design_config + $custom_size_extra;
        
        return round($total, 2);
    }
}