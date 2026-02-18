<?php
/**
 * Registrace zákazníků - obousměrná synchronizace konfigurátor ↔ e-shop
 * 
 * VERZE: 1.1.0
 * 
 * OPRAVY v 1.1.0:
 * - Přidány hooky pro standardní WordPress registraci (register_form + user_register)
 * - Oprava ukládání segmentu v "Podrobnosti účtu" (nonce + záložní hook)
 * - Funguje jak s WooCommerce registrací, tak se standardní WP registrací
 */

if (!defined('ABSPATH')) {
    exit;
}

class BLOCKids_Configurator_Registration {
    
    public static function init() {
        // === WORDPRESS STANDARDNÍ REGISTRACE (wp-login.php?action=register) ===
        add_action('register_form', array(__CLASS__, 'render_segment_field_on_wp_register'));
        add_action('user_register', array(__CLASS__, 'save_segment_on_wp_register'), 10, 1);
        add_filter('registration_errors', array(__CLASS__, 'validate_segment_on_wp_register'), 10, 3);
        
        // === WOOCOMMERCE REGISTRACE (pokud se používá) ===
        add_action('woocommerce_register_form', array(__CLASS__, 'render_segment_field_on_wc_register'));
        add_action('woocommerce_created_customer', array(__CLASS__, 'save_segment_on_wc_register'), 10, 3);
        add_filter('woocommerce_registration_errors', array(__CLASS__, 'validate_segment_on_wc_register'), 10, 3);
        
        // === ADMIN PROFIL: Zobrazit/uložit segment ===
        add_action('show_user_profile', array(__CLASS__, 'render_segment_field_in_admin'));
        add_action('edit_user_profile', array(__CLASS__, 'render_segment_field_in_admin'));
        add_action('personal_options_update', array(__CLASS__, 'save_segment_in_admin'));
        add_action('edit_user_profile_update', array(__CLASS__, 'save_segment_in_admin'));
        
        // === MŮJ ÚČET - PODROBNOSTI ÚČTU ===
        add_action('woocommerce_edit_account_form', array(__CLASS__, 'render_segment_field_in_account'));
        add_action('woocommerce_save_account_details', array(__CLASS__, 'save_segment_in_account'), 10, 1);
        
        // Záložní hook - zachytí POST pokud WC hook nefunguje
        add_action('wp_loaded', array(__CLASS__, 'maybe_save_segment_from_account_form'), 20);
    }
    
    // =========================================================================
    // REST API ENDPOINT - REGISTRACE Z KONFIGURATORU
    // =========================================================================
    
    public static function register_routes() {
        register_rest_route('blockids/v1', '/customers/register', array(
            'methods'  => 'POST',
            'callback' => array(__CLASS__, 'handle_register'),
            'permission_callback' => '__return_true'
        ));
    }
    
    public static function handle_register($request) {
        $body = $request->get_json_params();
        
        $required = array('givenName', 'familyName', 'email', 'password', 'segment');
        foreach ($required as $field) {
            if (empty($body[$field])) {
                return new WP_Error('missing_field', sprintf('Pole "%s" je povinné.', $field), array('status' => 400));
            }
        }
        
        $email     = sanitize_email($body['email']);
        $password  = $body['password'];
        $firstName = sanitize_text_field($body['givenName']);
        $lastName  = sanitize_text_field($body['familyName']);
        $phone     = isset($body['phone']) ? sanitize_text_field($body['phone']) : '';
        $segment   = (int) $body['segment'];
        
        if (!is_email($email)) {
            return new WP_Error('invalid_email', 'Neplatná e-mailová adresa.', array('status' => 400));
        }
        if (email_exists($email)) {
            return new WP_Error('email_exists', 'Uživatel s tímto e-mailem již existuje.', array('status' => 409));
        }
        if (username_exists($email)) {
            return new WP_Error('username_exists', 'Uživatelské jméno již existuje.', array('status' => 409));
        }
        if (!in_array($segment, array(1, 2))) {
            return new WP_Error('invalid_segment', 'Neplatný segment. Použijte 1 (Rodina) nebo 2 (Veřejný prostor).', array('status' => 400));
        }
        if (strlen($password) < 6) {
            return new WP_Error('weak_password', 'Heslo musí mít alespoň 6 znaků.', array('status' => 400));
        }
        
        $user_id = wc_create_new_customer($email, $email, $password);
        if (is_wp_error($user_id)) {
            return new WP_Error('registration_failed', $user_id->get_error_message(), array('status' => 500));
        }
        
        wp_update_user(array(
            'ID' => $user_id, 'first_name' => $firstName, 'last_name' => $lastName,
            'display_name' => $firstName . ' ' . $lastName,
        ));
        update_user_meta($user_id, 'billing_first_name', $firstName);
        update_user_meta($user_id, 'billing_last_name', $lastName);
        update_user_meta($user_id, 'billing_email', $email);
        if ($phone) { update_user_meta($user_id, 'billing_phone', $phone); }
        update_user_meta($user_id, 'blockids_segment_id', $segment);
        
        $token = BLOCKids_Configurator_Auth::generate_token($user_id);
        if (!$token) {
            return new WP_Error('token_failed', 'Účet vytvořen, ale nepodařilo se vygenerovat token.', array('status' => 500));
        }
        
        return rest_ensure_response(array(
            'id' => $user_id, 'givenName' => $firstName, 'familyName' => $lastName,
            'email' => $email, 'phone' => $phone,
            'segment' => array('id' => $segment),
            'token' => $token, 'planInProgress' => null, 'plans' => array()
        ));
    }
    
    // =========================================================================
    // WORDPRESS STANDARDNÍ REGISTRACE (wp-login.php?action=register)
    // =========================================================================
    
    /**
     * Zobrazit segment na WP registrační stránce
     */
    public static function render_segment_field_on_wp_register() {
        $selected = isset($_POST['blockids_segment']) ? $_POST['blockids_segment'] : '';
        ?>
        <p style="margin-bottom: 16px;">
            <label style="display: block; margin-bottom: 8px; font-weight: 600;">
                <?php esc_html_e('Vyberte, do které skupiny patříte', 'blockids-configurator'); ?> <span class="required" style="color: #dc3232;">*</span>
            </label>
            <label style="display: block; margin-bottom: 8px; padding: 10px 12px; border: 1px solid #ddd; border-radius: 4px; cursor: pointer; background: #fafafa;">
                <input type="radio" name="blockids_segment" value="1" <?php checked($selected, '1'); ?> style="margin-right: 6px;">
                <strong><?php esc_html_e('Rodina', 'blockids-configurator'); ?></strong>
            </label>
            <label style="display: block; padding: 10px 12px; border: 1px solid #ddd; border-radius: 4px; cursor: pointer; background: #fafafa;">
                <input type="radio" name="blockids_segment" value="2" <?php checked($selected, '2'); ?> style="margin-right: 6px;">
                <strong><?php esc_html_e('Veřejný prostor', 'blockids-configurator'); ?></strong>
                <span style="display: block; margin-left: 24px; color: #666; font-size: 0.85em;">
                    <?php esc_html_e('mateřské a základní školy, domovy dětí a mládeže a další', 'blockids-configurator'); ?>
                </span>
            </label>
        </p>
        <?php
    }
    
    /**
     * Validace segmentu při WP registraci
     */
    public static function validate_segment_on_wp_register($errors, $sanitized_user_login, $user_email) {
        if (empty($_POST['blockids_segment']) || !in_array($_POST['blockids_segment'], array('1', '2'))) {
            $errors->add('blockids_segment_error', '<strong>CHYBA</strong>: Vyberte prosím skupinu (Rodina nebo Veřejný prostor).');
        }
        return $errors;
    }
    
    /**
     * Uložit segment po WP registraci
     */
    public static function save_segment_on_wp_register($user_id) {
        if (isset($_POST['blockids_segment'])) {
            $segment = (int) $_POST['blockids_segment'];
            if (in_array($segment, array(1, 2))) {
                update_user_meta($user_id, 'blockids_segment_id', $segment);
            }
        }
    }
    
    // =========================================================================
    // WOOCOMMERCE REGISTRACE
    // =========================================================================
    
    public static function render_segment_field_on_wc_register() {
        ?>
        <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
            <label><?php esc_html_e('Vyberte, do které skupiny patříte', 'blockids-configurator'); ?>&nbsp;<span class="required">*</span></label>
            <span class="blockids-segment-options" style="display: block; margin-top: 8px;">
                <label style="display: block; margin-bottom: 10px; padding: 12px 15px; border: 1px solid #ddd; border-radius: 4px; cursor: pointer; background: #fafafa;">
                    <input type="radio" name="blockids_segment" value="1" 
                        <?php checked(isset($_POST['blockids_segment']) ? $_POST['blockids_segment'] : '', '1'); ?> 
                        style="margin-right: 8px;">
                    <strong><?php esc_html_e('Rodina', 'blockids-configurator'); ?></strong>
                </label>
                <label style="display: block; padding: 12px 15px; border: 1px solid #ddd; border-radius: 4px; cursor: pointer; background: #fafafa;">
                    <input type="radio" name="blockids_segment" value="2" 
                        <?php checked(isset($_POST['blockids_segment']) ? $_POST['blockids_segment'] : '', '2'); ?> 
                        style="margin-right: 8px;">
                    <strong><?php esc_html_e('Veřejný prostor', 'blockids-configurator'); ?></strong>
                    <span style="display: block; margin-left: 26px; color: #666; font-size: 0.9em;">
                        <?php esc_html_e('mateřské a základní školy, domovy dětí a mládeže a další', 'blockids-configurator'); ?>
                    </span>
                </label>
            </span>
        </p>
        <?php
    }
    
    public static function validate_segment_on_wc_register($errors, $username, $email) {
        if (empty($_POST['blockids_segment']) || !in_array($_POST['blockids_segment'], array('1', '2'))) {
            $errors->add('blockids_segment_error', __('Vyberte prosím skupinu (Rodina nebo Veřejný prostor).', 'blockids-configurator'));
        }
        return $errors;
    }
    
    public static function save_segment_on_wc_register($customer_id, $new_customer_data, $password_generated) {
        if (isset($_POST['blockids_segment'])) {
            $segment = (int) $_POST['blockids_segment'];
            if (in_array($segment, array(1, 2))) {
                update_user_meta($customer_id, 'blockids_segment_id', $segment);
            }
        }
    }
    
    // =========================================================================
    // ADMIN PROFIL
    // =========================================================================
    
    public static function render_segment_field_in_admin($user) {
        $segment = (int) get_user_meta($user->ID, 'blockids_segment_id', true);
        ?>
        <h3><?php esc_html_e('BLOCKids Konfigurátor', 'blockids-configurator'); ?></h3>
        <table class="form-table">
            <tr>
                <th><label><?php esc_html_e('Typ zákazníka', 'blockids-configurator'); ?></label></th>
                <td>
                    <label style="display: block; margin-bottom: 5px;">
                        <input type="radio" name="blockids_segment_id" value="1" <?php checked($segment, 1); ?>>
                        🏠 <?php esc_html_e('Rodina (Domácnost)', 'blockids-configurator'); ?>
                    </label>
                    <label style="display: block;">
                        <input type="radio" name="blockids_segment_id" value="2" <?php checked($segment, 2); ?>>
                        🏢 <?php esc_html_e('Veřejný prostor', 'blockids-configurator'); ?>
                    </label>
                    <p class="description">
                        <?php esc_html_e('Ovlivňuje, které produkty se zákazníkovi zobrazí v konfiguratoru.', 'blockids-configurator'); ?>
                    </p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    public static function save_segment_in_admin($user_id) {
        if (!current_user_can('edit_user', $user_id)) {
            return;
        }
        if (isset($_POST['blockids_segment_id'])) {
            $segment = (int) $_POST['blockids_segment_id'];
            if (in_array($segment, array(1, 2))) {
                update_user_meta($user_id, 'blockids_segment_id', $segment);
            }
        }
    }
    
    // =========================================================================
    // MŮJ ÚČET → PODROBNOSTI ÚČTU
    // =========================================================================
    
    /**
     * Zobrazit segment pole + vlastní nonce
     */
    public static function render_segment_field_in_account() {
        $user_id = get_current_user_id();
        $segment = (int) get_user_meta($user_id, 'blockids_segment_id', true);
        
        // Vlastní nonce - záložní mechanismus pro uložení
        wp_nonce_field('blockids_save_segment_account', 'blockids_segment_nonce');
        ?>
        <fieldset>
            <legend><?php esc_html_e('Typ zákazníka (BLOCKids)', 'blockids-configurator'); ?></legend>
            <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                <label style="display: block; margin-bottom: 8px; padding: 10px 12px; border: 1px solid #ddd; border-radius: 4px; cursor: pointer;">
                    <input type="radio" name="blockids_segment_id" value="1" <?php checked($segment, 1); ?> style="margin-right: 6px;">
                    <strong><?php esc_html_e('Rodina', 'blockids-configurator'); ?></strong>
                </label>
                <label style="display: block; padding: 10px 12px; border: 1px solid #ddd; border-radius: 4px; cursor: pointer;">
                    <input type="radio" name="blockids_segment_id" value="2" <?php checked($segment, 2); ?> style="margin-right: 6px;">
                    <strong><?php esc_html_e('Veřejný prostor', 'blockids-configurator'); ?></strong>
                    <span style="color: #666; font-size: 0.9em;">(<?php esc_html_e('školy, domovy dětí apod.', 'blockids-configurator'); ?>)</span>
                </label>
            </p>
        </fieldset>
        <?php
    }
    
    /**
     * Uložit segment - WooCommerce hook
     */
    public static function save_segment_in_account($user_id) {
        if (isset($_POST['blockids_segment_id'])) {
            $segment = (int) $_POST['blockids_segment_id'];
            if (in_array($segment, array(1, 2))) {
                update_user_meta($user_id, 'blockids_segment_id', $segment);
            }
        }
    }
    
    /**
     * Záložní metoda - zachytí uložení formuláře pokud WC hook nefunguje
     * Spouští se na wp_loaded, ověřuje vlastní nonce
     */
    public static function maybe_save_segment_from_account_form() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }
        if (!is_user_logged_in()) {
            return;
        }
        // Ověřit naše nonce
        if (!isset($_POST['blockids_segment_nonce']) || !wp_verify_nonce($_POST['blockids_segment_nonce'], 'blockids_save_segment_account')) {
            return;
        }
        if (!isset($_POST['blockids_segment_id'])) {
            return;
        }
        
        $user_id = get_current_user_id();
        $segment = (int) $_POST['blockids_segment_id'];
        
        if (in_array($segment, array(1, 2))) {
            update_user_meta($user_id, 'blockids_segment_id', $segment);
        }
    }
}