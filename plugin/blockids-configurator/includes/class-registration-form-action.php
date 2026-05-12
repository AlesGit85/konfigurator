<?php
/**
 * Vlastní Elementor Form Action - Registrace zákazníka BLOCKids
 * Zobrazí se v Elementor Form → Action After Submit → "BLOCKids Registrace"
 */

if (!defined('ABSPATH')) {
    exit;
}

class BLOCKids_Registration_Form_Action extends \ElementorPro\Modules\Forms\Classes\Action_Base {

    public function get_name() {
        return 'blockids_registration';
    }

    public function get_label() {
        return 'BLOCKids Registrace';
    }

    public function register_settings_section($widget) {
        // Zde můžeš přidat nastavení akce v editoru (není povinné)
    }

    public function on_export($element) {}

    public function run($record, $ajax_handler) {
        $fields = $record->get('fields');

        // Načtení hodnot z formuláře (klíče = Field ID v Elementoru)
        $email    = sanitize_email($fields['email']['value'] ?? '');
        $password = $fields['password']['value'] ?? '';
        $jmeno    = sanitize_text_field($fields['first_name']['value'] ?? '');
        $prijmeni = sanitize_text_field($fields['last_name']['value'] ?? '');
        $telefon  = sanitize_text_field($fields['phone']['value'] ?? '');
        // segment: "family" nebo "public" - hodnota z radio/select pole
        $segment  = sanitize_text_field($fields['segment']['value'] ?? 'family');

        // Validace
        if (empty($email) || empty($password)) {
            $ajax_handler->add_error_message(__('E-mail a heslo jsou povinné.', 'blockids-configurator'));
            return;
        }

        if (email_exists($email)) {
            $ajax_handler->add_error_message(__('Tento e-mail je již zaregistrován.', 'blockids-configurator'));
            return;
        }

        // Vytvoření uživatele
        $user_id = wp_create_user($email, $password, $email);

        if (is_wp_error($user_id)) {
            $ajax_handler->add_error_message($user_id->get_error_message());
            return;
        }

        // Uložení dalších dat
        wp_update_user([
            'ID'            => $user_id,
            'first_name'    => $jmeno,
            'last_name'     => $prijmeni,
            'display_name'  => trim($jmeno . ' ' . $prijmeni),
            'role'          => 'customer',
        ]);

        // Telefon (WooCommerce billing pole)
        if ($telefon) {
            update_user_meta($user_id, 'billing_phone', $telefon);
            update_user_meta($user_id, 'billing_first_name', $jmeno);
            update_user_meta($user_id, 'billing_last_name', $prijmeni);
        }

        // Segment: family = 1, public = 2 (stejně jako v pluginu)
        $segment_id = ($segment === 'public') ? 2 : 1;
        update_user_meta($user_id, 'blockids_segment_id', $segment_id);

        // Automatické přihlášení po registraci
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id, true);

        // Přesměrování lze nastavit přes Elementor "Redirect" akci kombinací,
        // nebo takhle přímo:
        // $ajax_handler->set_success_url( home_url('/muj-ucet/') );
    }
}