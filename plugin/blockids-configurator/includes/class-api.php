<?php

/**
 * REST API endpoints for configurator
 */

if (!defined('ABSPATH')) {
    exit;
}

class BLOCKids_Configurator_API
{

    private static $namespace = 'blockids/v1';

    public static function register_routes()
    {
        // Validation endpoint
        register_rest_route(self::$namespace, '/customers/me/(?P<token>[a-zA-Z0-9\-_\.]+)', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'validate_customer'),
            'permission_callback' => '__return_true'
        ));

        // Product endpoints
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

        register_rest_route(self::$namespace, '/faq-items/(?P<lang>cs|en|de)', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_faq_items'),
            'permission_callback' => '__return_true'
        ));

        // Plan endpoints
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
    }

    /**
     * Validate customer token
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

        // Get user's plan in progress
        $plan_in_progress = BLOCKids_Configurator_Plans::get_user_draft_plan($user->ID);

        return array(
            'data' => array(
                'id' => $user->ID,
                'givenName' => $user->first_name ?: $user->display_name,
                'familyName' => $user->last_name,
                'segment' => array(
                    'id' => 1
                ),
                'planInProgress' => $plan_in_progress ? $plan_in_progress->access_hash : null,
                'plans' => BLOCKids_Configurator_Plans::get_user_plans($user->ID)
            )
        );
    }

    /**
     * Get grips (chyty)
     */
    public static function get_grips($request)
    {
        $lang = $request['lang'];

        // Get products from WooCommerce with category 'gripy' or tag 'grip'
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => -1,
            'tax_query' => array(
                array(
                    'taxonomy' => 'product_cat',
                    'field' => 'slug',
                    'terms' => 'gripy'
                )
            )
        );

        $products = wc_get_products($args);
        $grips = array();

        foreach ($products as $product) {
            $grips[] = array(
                'id' => $product->get_id(),
                'title' => $product->get_name(),
                'price' => (int) $product->get_price(),
                'currency' => 'czk',
                'order' => $product->get_menu_order(),
                'image' => wp_get_attachment_url($product->get_image_id()),
                'overlays' => array() // TODO: Get from product meta
            );
        }

        return rest_ensure_response($grips);
    }

    /**
     * Get mattresses (matrace)
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
            )
        );

        $products = wc_get_products($args);
        $mattresses = array();

        foreach ($products as $product) {
            $mattresses[] = array(
                'id' => $product->get_id(),
                'title' => $product->get_name(),
                'price' => (int) $product->get_price(),
                'currency' => 'czk',
                'order' => $product->get_menu_order(),
                'image' => wp_get_attachment_url($product->get_image_id())
            );
        }

        return rest_ensure_response($mattresses);
    }

    /**
     * Get desks (desky/panely)
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
            )
        );

        $products = wc_get_products($args);
        $desks = array();

        foreach ($products as $product) {
            $desks[] = array(
                'id' => $product->get_id(),
                'title' => $product->get_name(),
                'price' => (int) $product->get_price(),
                'currency' => 'czk',
                'order' => $product->get_menu_order(),
                'image' => wp_get_attachment_url($product->get_image_id()),
                'overlays' => array() // TODO: Get from product meta
            );
        }

        return rest_ensure_response($desks);
    }

    /**
     * Create new plan
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

        $location = isset($body['location']) ? $body['location'] : 'indoor';

        $plan = BLOCKids_Configurator_Plans::create_plan($user_data->user_id, $location);

        if (!$plan) {
            return new WP_Error('plan_creation_failed', 'Failed to create plan', array('status' => 500));
        }

        return rest_ensure_response(array(
            'data' => array(
                'accessHash' => $plan['access_hash']
            )
        ));
    }

    /**
     * Get plan detail
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

        return rest_ensure_response(array('data' => $plan));
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

        return rest_ensure_response(array('success' => true));
    }

    /**
     * Confirm plan (add to cart)
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

        // Update plan
        BLOCKids_Configurator_Plans::update_plan($hash, $user_data->user_id, $body);

        // Mark as confirmed
        BLOCKids_Configurator_Plans::confirm_plan($hash, $user_data->user_id);

        return rest_ensure_response(array(
            'data' => array(
                'accessHash' => $hash
            )
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

        return rest_ensure_response(array('success' => true));
    }

    /**
     * Get FAQ items
     */
    public static function get_faq_items($request)
    {
        // Pro teď vrátíme prázdné pole
        // Můžeš později přidat FAQ z WordPress postů
        return rest_ensure_response(array());
    }
}
