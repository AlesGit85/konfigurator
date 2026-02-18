<?php

/**
 * REST API endpoints for configurator
 * 
 * VERZE: 2.0.0
 * 
 * ZMĚNY oproti v1:
 * - Opravený response format (bez extra "data" wrapperu)
 * - Přidány chybějící endpointy (photos, clone, change-title)
 * - Přidány chybějící pole v responses (desks: location/type, mattresses: color/personal/prices, grips: overlays)
 * - Plan detail workspace vrací kompletní desk objekty
 * - User plans vrací "name" místo "title"
 * - Přidány email/phone do customer response
 */

if (!defined('ABSPATH')) {
    exit;
}

class BLOCKids_Configurator_API
{

    private static $namespace = 'blockids/v1';

    public static function register_routes()
    {
        // ===== CUSTOMER =====
        register_rest_route(self::$namespace, '/customers/me/(?P<token>[a-zA-Z0-9\-_\.]+)', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'validate_customer'),
            'permission_callback' => '__return_true'
        ));

        // ===== PRODUCTS =====
        register_rest_route(self::$namespace, '/grips/(?P<lang>cs|en|de)', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_grips'),
            'permission_callback' => '__return_true'
        ));

        register_rest_route(self::$namespace, '/mattresses/(?P<lang>cs|en|de)', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_mattresses'),
            'permission_callback' => '__return_true'
        ));

        register_rest_route(self::$namespace, '/desks/(?P<lang>cs|en|de)', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_desks'),
            'permission_callback' => '__return_true'
        ));

        // ===== CONTENT =====
        register_rest_route(self::$namespace, '/faq-items/(?P<lang>cs|en|de)', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_faq_items'),
            'permission_callback' => '__return_true'
        ));

        register_rest_route(self::$namespace, '/photos/(?P<lang>cs|en|de)', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_photos'),
            'permission_callback' => '__return_true'
        ));

        // ===== PLANS =====
        register_rest_route(self::$namespace, '/plans/create/(?P<lang>cs|en|de)/(?P<token>[a-zA-Z0-9\-_\.]+)', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'create_plan'),
            'permission_callback' => '__return_true'
        ));

        register_rest_route(self::$namespace, '/plans/detail/(?P<lang>cs|en|de)/(?P<token>[a-zA-Z0-9\-_\.]+)/(?P<hash>[a-zA-Z0-9]+)', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_plan_detail'),
            'permission_callback' => '__return_true'
        ));

        register_rest_route(self::$namespace, '/plans/update/(?P<lang>cs|en|de)/(?P<token>[a-zA-Z0-9\-_\.]+)/(?P<hash>[a-zA-Z0-9]+)', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'update_plan'),
            'permission_callback' => '__return_true'
        ));

        register_rest_route(self::$namespace, '/plans/confirm/(?P<lang>cs|en|de)/(?P<token>[a-zA-Z0-9\-_\.]+)/(?P<hash>[a-zA-Z0-9]+)', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'confirm_plan'),
            'permission_callback' => '__return_true'
        ));

        register_rest_route(self::$namespace, '/plans/delete/(?P<lang>cs|en|de)/(?P<token>[a-zA-Z0-9\-_\.]+)/(?P<hash>[a-zA-Z0-9]+)', array(
            'methods' => 'DELETE',
            'callback' => array(__CLASS__, 'delete_plan'),
            'permission_callback' => '__return_true'
        ));

        // NOVÉ endpointy pro konfigurátor
        register_rest_route(self::$namespace, '/plans/clone/(?P<lang>cs|en|de)/(?P<token>[a-zA-Z0-9\-_\.]+)/(?P<hash>[a-zA-Z0-9]+)', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'clone_plan'),
            'permission_callback' => '__return_true'
        ));

        register_rest_route(self::$namespace, '/plans/change-title/(?P<lang>cs|en|de)/(?P<token>[a-zA-Z0-9\-_\.]+)/(?P<hash>[a-zA-Z0-9]+)', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'change_plan_title'),
            'permission_callback' => '__return_true'
        ));

        // ===== REGISTRACE =====
        BLOCKids_Configurator_Registration::register_routes();
    }

    // =========================================================================
    // CUSTOMER
    // =========================================================================

    /**
     * Validate customer token
     * 
     * Konfigurátor čeká response BEZ wrapperu "data":
     * { id, givenName, familyName, email, phone, segment: { id }, planInProgress, plans }
     * 
     * Fetcher.ts to pak obalí do .data automaticky.
     */
    public static function validate_customer($request)
    {
        $token = $request['token'];

        $user_data = BLOCKids_Configurator_Auth::validate_token($token);

        if (!$user_data) {
            return new WP_Error('invalid_token', 'Invalid or expired token', array('status' => 401));
        }

        $user = get_user_by('id', $user_data->user_id);

        if (!$user) {
            return new WP_Error('user_not_found', 'User not found', array('status' => 404));
        }

        // Get user's plan in progress (latest draft)
        $plan_in_progress = BLOCKids_Configurator_Plans::get_user_draft_plan($user->ID);

        // Get customer segment (1 = family, 2 = public)
        $segment_id = (int) get_user_meta($user->ID, 'blockids_segment_id', true);
        if (!$segment_id) {
            $segment_id = 1; // Default: family
        }

        // Response BEZ "data" wrapperu - fetcher.ts to obalí automaticky
        return rest_ensure_response(array(
            'id' => $user->ID,
            'givenName' => $user->first_name ?: $user->display_name,
            'familyName' => $user->last_name ?: '',
            'email' => $user->user_email,
            'phone' => get_user_meta($user->ID, 'billing_phone', true) ?: '',
            'segment' => array(
                'id' => $segment_id
            ),
            'planInProgress' => $plan_in_progress ? $plan_in_progress->access_hash : null,
            'plans' => BLOCKids_Configurator_Plans::get_user_plans($user->ID)
        ));
    }

    // =========================================================================
    // PRODUCTS
    // =========================================================================

    /**
     * Get grips (chyty/holds)
     * 
     * Konfigurátor čeká:
     * [{ id, title, price, currency, order, image, overlays: [{ id, type, orientation, rotation, inputs, image }] }]
     * 
     * Product meta:
     * - _blockids_overlays: JSON pole overlay objektů
     */
    public static function get_grips($request)
    {
        $lang = $request['lang'];

        $args = array(
            'post_type' => 'product',
            'posts_per_page' => -1,
            'tax_query' => array(
                array(
                    'taxonomy' => 'product_cat',
                    'field' => 'slug',
                    'terms' => 'gripy'
                )
            ),
            'orderby' => 'menu_order',
            'order' => 'ASC'
        );

        $products = wc_get_products($args);
        $grips = array();

        foreach ($products as $product) {
            // Overlays z product meta (JSON)
            $overlays_raw = get_post_meta($product->get_id(), '_blockids_overlays', true);
            $overlays = array();
            if ($overlays_raw) {
                $decoded = json_decode($overlays_raw, true);
                if (is_array($decoded)) {
                    $overlays = $decoded;
                }
            }

            $grips[] = array(
                'id' => $product->get_id(),
                'title' => $product->get_name(),
                'price' => (int) $product->get_price(),
                'currency' => 'czk',
                'order' => $product->get_menu_order(),
                'image' => wp_get_attachment_url($product->get_image_id()) ?: '',
                'overlays' => $overlays
            );
        }

        return rest_ensure_response($grips);
    }

    /**
     * Get mattresses (matrace)
     * 
     * Konfigurátor čeká:
     * [{ id, title, price, prices, currency, order, image, color, personal }]
     * 
     * Product meta:
     * - _blockids_color: HEX barva matrace (např. "#FF0000")
     * - _blockids_personal: bool - true = family, false = public
     * - _blockids_prices: JSON pole cen dle šířky [{ minWidth, maxWidth, price }]
     */
    public static function get_mattresses($request)
    {
        $lang = $request['lang'];

        $args = array(
            'post_type' => 'product',
            'posts_per_page' => -1,
            'tax_query' => array(
                array(
                    'taxonomy' => 'product_cat',
                    'field' => 'slug',
                    'terms' => 'matrace'
                )
            ),
            'orderby' => 'menu_order',
            'order' => 'ASC'
        );

        $products = wc_get_products($args);
        $mattresses = array();

        foreach ($products as $product) {
            $color = get_post_meta($product->get_id(), '_blockids_color', true) ?: '';
            $personal = get_post_meta($product->get_id(), '_blockids_personal', true);
            $personal = ($personal === 'yes' || $personal === '1' || $personal === true);

            // Prices pole pro public zákazníky (cena dle šířky stěny)
            $prices_raw = get_post_meta($product->get_id(), '_blockids_prices', true);
            $prices = null;
            if ($prices_raw) {
                $decoded = json_decode($prices_raw, true);
                if (is_array($decoded)) {
                    $prices = $decoded;
                }
            }

            $mattresses[] = array(
                'id' => $product->get_id(),
                'title' => $product->get_name(),
                'price' => (int) $product->get_price(),
                'prices' => $prices, // Pole cen dle šířky, nebo null
                'currency' => 'czk',
                'order' => $product->get_menu_order(),
                'image' => wp_get_attachment_url($product->get_image_id()) ?: '',
                'color' => $color,
                'personal' => $personal
            );
        }

        return rest_ensure_response($mattresses);
    }

    /**
     * Get desks (desky/panely)
     * 
     * Konfigurátor čeká:
     * [{ id, title, price, currency, order, image, location, type, overlays }]
     * 
     * Product meta:
     * - _blockids_location: "indoor" | "outdoor"
     * - _blockids_type: "rectangle" | "triangle" | "blackboard" (nebo table)
     * - _blockids_overlays: JSON pole overlay objektů
     */
    public static function get_desks($request)
    {
        $lang = $request['lang'];

        $args = array(
            'post_type' => 'product',
            'posts_per_page' => -1,
            'tax_query' => array(
                array(
                    'taxonomy' => 'product_cat',
                    'field' => 'slug',
                    'terms' => 'desky'
                )
            ),
            'orderby' => 'menu_order',
            'order' => 'ASC'
        );

        $products = wc_get_products($args);
        $desks = array();

        foreach ($products as $product) {
            $location = get_post_meta($product->get_id(), '_blockids_location', true) ?: 'indoor';
            $type = get_post_meta($product->get_id(), '_blockids_type', true) ?: 'rectangle';

            $overlays_raw = get_post_meta($product->get_id(), '_blockids_overlays', true);
            $overlays = array();
            if ($overlays_raw) {
                $decoded = json_decode($overlays_raw, true);
                if (is_array($decoded)) {
                    $overlays = $decoded;
                }
            }

            $desks[] = array(
                'id' => $product->get_id(),
                'title' => $product->get_name(),
                'price' => (int) $product->get_price(),
                'currency' => 'czk',
                'order' => $product->get_menu_order(),
                'image' => wp_get_attachment_url($product->get_image_id()) ?: '',
                'location' => $location,
                'type' => $type,
                'overlays' => $overlays
            );
        }

        return rest_ensure_response($desks);
    }

    // =========================================================================
    // CONTENT
    // =========================================================================

    /**
     * Get FAQ items
     */
    public static function get_faq_items($request)
    {
        $lang = $request['lang'];

        // Načíst FAQ z custom post type nebo z options
        $faq_items = get_option('blockids_faq_items', array());

        if (!empty($faq_items) && is_string($faq_items)) {
            $faq_items = json_decode($faq_items, true);
        }

        if (!is_array($faq_items)) {
            $faq_items = array();
        }

        return rest_ensure_response($faq_items);
    }

    /**
     * Get inspiration photos
     * NOVÝ endpoint - konfigurátor volá /photos/{lang}
     */
    public static function get_photos($request)
    {
        $lang = $request['lang'];

        // Načíst inspirační fotky z options nebo custom post type
        $photos = get_option('blockids_inspiration_photos', array());

        if (!empty($photos) && is_string($photos)) {
            $photos = json_decode($photos, true);
        }

        if (!is_array($photos)) {
            $photos = array();
        }

        return rest_ensure_response($photos);
    }

    // =========================================================================
    // PLANS
    // =========================================================================

    /**
     * Create new plan
     * 
     * Request body: { location: "indoor" | "outdoor" }
     * Response: { accessHash: "xxx" }  (BEZ data wrapperu)
     */
    public static function create_plan($request)
    {
        $token = $request['token'];
        $lang = $request['lang'];
        $body = $request->get_json_params();

        $user_data = BLOCKids_Configurator_Auth::validate_token($token);
        if (!$user_data) {
            return new WP_Error('invalid_token', 'Invalid token', array('status' => 401));
        }

        $location = isset($body['location']) ? sanitize_text_field($body['location']) : 'indoor';

        $plan = BLOCKids_Configurator_Plans::create_plan($user_data->user_id, $location);

        if (!$plan) {
            return new WP_Error('plan_creation_failed', 'Failed to create plan', array('status' => 500));
        }

        // Response BEZ data wrapperu
        return rest_ensure_response(array(
            'accessHash' => $plan['access_hash']
        ));
    }

    /**
     * Get plan detail
     * 
     * Response: Celý plan objekt BEZ data wrapperu
     */
    public static function get_plan_detail($request)
    {
        $token = $request['token'];
        $hash = $request['hash'];
        $lang = $request['lang'];

        $user_data = BLOCKids_Configurator_Auth::validate_token($token);
        if (!$user_data) {
            return new WP_Error('invalid_token', 'Invalid token', array('status' => 401));
        }

        $plan = BLOCKids_Configurator_Plans::get_plan_by_hash($hash, $user_data->user_id);

        if (!$plan) {
            return new WP_Error('plan_not_found', 'Plan not found', array('status' => 404));
        }

        // Response BEZ data wrapperu
        return rest_ensure_response($plan);
    }

    /**
     * Update plan
     */
    public static function update_plan($request)
    {
        $token = $request['token'];
        $hash = $request['hash'];
        $lang = $request['lang'];
        $body = $request->get_json_params();

        $user_data = BLOCKids_Configurator_Auth::validate_token($token);
        if (!$user_data) {
            return new WP_Error('invalid_token', 'Invalid token', array('status' => 401));
        }

        $updated = BLOCKids_Configurator_Plans::update_plan($hash, $user_data->user_id, $body);

        if (!$updated) {
            return new WP_Error('update_failed', 'Failed to update plan', array('status' => 500));
        }

        return rest_ensure_response(array(
            'success' => true,
            'accessHash' => $hash
        ));
    }

    /**
     * Confirm plan (add to cart)
     * 
     * Response: { accessHash: "xxx" }  (BEZ data wrapperu)
     */
    public static function confirm_plan($request)
    {
        $token = $request['token'];
        $hash = $request['hash'];
        $lang = $request['lang'];
        $body = $request->get_json_params();

        $user_data = BLOCKids_Configurator_Auth::validate_token($token);
        if (!$user_data) {
            return new WP_Error('invalid_token', 'Invalid token', array('status' => 401));
        }

        // Update plan data
        if (!empty($body)) {
            BLOCKids_Configurator_Plans::update_plan($hash, $user_data->user_id, $body);
        }

        // Mark as confirmed
        BLOCKids_Configurator_Plans::confirm_plan($hash, $user_data->user_id);

        // Response BEZ data wrapperu
        return rest_ensure_response(array(
            'accessHash' => $hash
        ));
    }

    /**
     * Delete plan
     */
    public static function delete_plan($request)
    {
        $token = $request['token'];
        $hash = $request['hash'];
        $lang = $request['lang'];

        $user_data = BLOCKids_Configurator_Auth::validate_token($token);
        if (!$user_data) {
            return new WP_Error('invalid_token', 'Invalid token', array('status' => 401));
        }

        $deleted = BLOCKids_Configurator_Plans::delete_plan($hash, $user_data->user_id);

        if (!$deleted) {
            return new WP_Error('delete_failed', 'Failed to delete plan', array('status' => 500));
        }

        return rest_ensure_response(array(
            'success' => true,
            'accessHash' => $hash
        ));
    }

    /**
     * Clone plan
     * NOVÝ endpoint - konfigurátor volá /plans/clone/{lang}/{token}/{hash}
     * 
     * Response: { accessHash: "xxx" }
     */
    public static function clone_plan($request)
    {
        $token = $request['token'];
        $hash = $request['hash'];
        $lang = $request['lang'];

        $user_data = BLOCKids_Configurator_Auth::validate_token($token);
        if (!$user_data) {
            return new WP_Error('invalid_token', 'Invalid token', array('status' => 401));
        }

        $new_plan = BLOCKids_Configurator_Plans::clone_plan($hash, $user_data->user_id);

        if (!$new_plan) {
            return new WP_Error('clone_failed', 'Failed to clone plan', array('status' => 500));
        }

        return rest_ensure_response(array(
            'accessHash' => $new_plan['access_hash']
        ));
    }

    /**
     * Change plan title
     * NOVÝ endpoint - konfigurátor volá /plans/change-title/{lang}/{token}/{hash}
     * 
     * Request body: { title: "Nový název" }
     * Response: { success: true }
     */
    public static function change_plan_title($request)
    {
        $token = $request['token'];
        $hash = $request['hash'];
        $lang = $request['lang'];
        $body = $request->get_json_params();

        $user_data = BLOCKids_Configurator_Auth::validate_token($token);
        if (!$user_data) {
            return new WP_Error('invalid_token', 'Invalid token', array('status' => 401));
        }

        if (!isset($body['title'])) {
            return new WP_Error('missing_title', 'Title is required', array('status' => 400));
        }

        $updated = BLOCKids_Configurator_Plans::change_title($hash, $user_data->user_id, sanitize_text_field($body['title']));

        if (!$updated) {
            return new WP_Error('update_failed', 'Failed to update title', array('status' => 500));
        }

        return rest_ensure_response(array(
            'success' => true,
            'accessHash' => $hash
        ));
    }
}
