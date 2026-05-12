<?php

/**
 * Admin settings page + Product meta fields
 * 
 * VERZE: 2.4.0
 * ZMĚNY oproti 2.3.0:
 * - Přidán checkbox "Produkt pro konfigurátor" (_blockids_for_configurator)
 * - Parametry konfiguratoru se zobrazí pouze po zaškrtnutí checkboxu
 * - Slug kategorie matrací změněn z 'matrace' na 'zinenky-a-dopadove-matrace'
 */

if (!defined('ABSPATH')) {
    exit;
}

class BLOCKids_Configurator_Settings
{

    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_menu_page'));
        add_action('admin_init', array($this, 'register_settings'));

        // Product meta fields - vlastní meta box místo woocommerce_product_options
        add_action('add_meta_boxes', array($this, 'add_product_meta_box'));
        add_action('woocommerce_process_product_meta', array($this, 'save_product_meta_fields'));

        // Admin JS/CSS
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

        // Handler pro promázání cache gripy (volá se přes URL parametr)
        add_action('admin_init', array($this, 'handle_clear_grip_cache'));
    }

    public function add_menu_page()
    {
        add_menu_page(
            __('BLOCKids Konfigurátor', 'blockids-configurator'),
            __('BLOCKids', 'blockids-configurator'),
            'manage_options',
            'blockids-configurator',
            array($this, 'render_settings_page'),
            'dashicons-admin-tools',
            56
        );
    }

    public function register_settings()
    {
        register_setting('blockids_configurator_settings', 'blockids_configurator_url');
        register_setting('blockids_configurator_settings', 'blockids_api_base_url');
        register_setting('blockids_configurator_settings', 'blockids_jwt_secret_key');
        register_setting('blockids_configurator_settings', 'blockids_jwt_expiration');
    }

    // =========================================================================
    // PRODUCT META BOX
    // =========================================================================

    public function add_product_meta_box()
    {
        add_meta_box(
            'blockids_product_meta',
            '🧗 BLOCKids – Nastavení pro konfigurátor',
            array($this, 'render_product_meta_box'),
            'product',
            'normal',
            'high'
        );
    }

    public function render_product_meta_box($post)
    {
        wp_nonce_field('blockids_product_meta', 'blockids_product_meta_nonce');

        // Aktuální hodnoty
        $type             = get_post_meta($post->ID, '_blockids_type', true);
        $location         = get_post_meta($post->ID, '_blockids_location', true);
        $color            = get_post_meta($post->ID, '_blockids_color', true);
        $personal         = get_post_meta($post->ID, '_blockids_personal', true);
        $prices_raw       = get_post_meta($post->ID, '_blockids_prices', true);
        $overlays_raw     = get_post_meta($post->ID, '_blockids_overlays', true);
        $for_configurator = get_post_meta($post->ID, '_blockids_for_configurator', true);

        // Rozparsovat ceny pro repeater
        $prices = array();
        if ($prices_raw) {
            $decoded = json_decode($prices_raw, true);
            if (is_array($decoded)) {
                $prices = $decoded;
            }
        }

        // Rozparsovat overlays pro SVG repeater
        $overlays = array();
        if ($overlays_raw) {
            $decoded = json_decode($overlays_raw, true);
            if (is_array($decoded)) {
                $overlays = $decoded;
            }
        }

        // Zjistit aktuální kategorie (slug zinenky-a-dopadove-matrace místo matrace)
        $terms      = wp_get_post_terms($post->ID, 'product_cat', array('fields' => 'slugs'));
        $is_desky   = in_array('desky', $terms);
        $is_gripy   = in_array('gripy', $terms);
        $is_matrace = in_array('zinenky-a-dopadove-matrace', $terms);
        $has_category = $is_desky || $is_gripy || $is_matrace;

        // Zobrazit parametry jen když je checkbox zaškrtnut
        $show_fields = ($for_configurator === '1');

?>

        <!-- Zpráva když produkt nemá konfigurátorovou kategorii -->
        <div id="blockids-no-category" style="<?php echo $has_category ? 'display:none;' : ''; ?>padding: 15px; background: #f0f0f1; border-left: 4px solid #dba617; margin-bottom: 15px;">
            <p style="margin:0;">
                <strong>ℹ️ Tento produkt nemá přiřazenou konfigurátorovou kategorii.</strong><br>
                Přiřaďte produkt do jedné z kategorií: <strong>desky</strong>, <strong>gripy</strong> nebo <strong>Žíněnky a dopadové matrace</strong>
                (v panelu "Kategorie produktu" vpravo) a tato sekce se automaticky zobrazí.
            </p>
        </div>

        <!-- Checkbox "Produkt pro konfigurátor" - zobrazí se jen když je přiřazena kategorie -->
        <div id="blockids-enable-checkbox" style="<?php echo $has_category ? '' : 'display:none;'; ?>padding: 12px 15px; background: #fff8e1; border-left: 4px solid #ffb300; margin-bottom: 15px; border-radius: 0 4px 4px 0;">
            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-size: 14px;">
                <input type="checkbox"
                    name="blockids_for_configurator"
                    id="blockids_for_configurator"
                    value="1"
                    <?php checked($for_configurator, '1'); ?>
                    style="width: 18px; height: 18px; cursor: pointer;">
                <span>
                    <strong>✅ Produkt pro konfigurátor</strong>
                    <span style="display: block; font-size: 12px; color: #666; font-weight: normal; margin-top: 2px;">
                        Zaškrtněte pro zobrazení tohoto produktu v konfiguratoru. Po zaškrtnutí vyplňte parametry níže.
                    </span>
                </span>
            </label>
        </div>

        <!-- Obal pro všechny parametry - skrytý dokud není zaškrtnut checkbox -->
        <div id="blockids-fields-wrapper" style="<?php echo $show_fields ? '' : 'display:none;'; ?>">

        <!-- ===== DESKY ===== -->
        <div id="blockids-fields-desky" class="blockids-field-group" style="<?php echo $is_desky ? '' : 'display:none;'; ?>">
            <div style="background: #e8f4fc; padding: 12px 15px; border-radius: 4px; margin-bottom: 15px;">
                <strong>🟫 Nastavení desky pro konfigurátor</strong>
                <p style="margin: 5px 0 0; color: #555;">Nastavte typ desky, umístění a segment zákazníka.</p>
            </div>

            <table class="form-table">
                <tr>
                    <th><label for="blockids_type">Typ desky</label></th>
                    <td>
                        <select name="blockids_type" id="blockids_type">
                            <option value="rectangle" <?php selected($type, 'rectangle'); ?>>Obdélník (rectangle)</option>
                            <option value="triangle_top" <?php selected($type, 'triangle_top'); ?>>Trojúhelník nahoře (triangle_top)</option>
                            <option value="triangle_left" <?php selected($type, 'triangle_left'); ?>>Trojúhelník vlevo (triangle_left)</option>
                            <option value="blackboard" <?php selected($type, 'blackboard'); ?>>📝 Kreslící tabule (blackboard)</option>
                        </select>
                        <p class="description">Tvar desky jak se zobrazí v konfiguratoru.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="blockids_location">Umístění</label></th>
                    <td>
                        <select name="blockids_location" id="blockids_location">
                            <option value="" <?php selected($location, ''); ?>>— obě umístění —</option>
                            <option value="indoor" <?php selected($location, 'indoor'); ?>>Indoor (interiér)</option>
                            <option value="outdoor" <?php selected($location, 'outdoor'); ?>>Outdoor (exteriér)</option>
                        </select>
                        <p class="description">Zda se deska zobrazí jen pro indoor nebo outdoor konfiguraci. Prázdné = obě.</p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- ===== GRIPY ===== -->
        <div id="blockids-fields-gripy" class="blockids-field-group" style="<?php echo $is_gripy ? '' : 'display:none;'; ?>">
            <div style="background: #fef7e7; padding: 12px 15px; border-radius: 4px; margin-bottom: 15px;">
                <strong>🤏 Nastavení chytů pro konfigurátor</strong>
                <p style="margin: 5px 0 0; color: #555;">
                    Nastavte barvu produktu — plugin automaticky načte SVG overlays ze složky
                    <code><?php echo esc_html(BLOCKids_Grip_Scanner::get_grips_folder()); ?></code>
                    podle názvů souborů.
                </p>
            </div>

            <table class="form-table">
                <!-- Výběr barvy -->
                <tr>
                    <th style="padding-top: 14px;">
                        <label for="blockids_grip_color">Barva gripu</label>
                    </th>
                    <td>
                        <?php
                        $grip_color      = get_post_meta($post->ID, '_blockids_grip_color', true);
                        $available_colors = BLOCKids_Grip_Scanner::get_available_colors();
                        ?>

                        <?php if (!empty($available_colors)) : ?>
                            <select name="blockids_grip_color" id="blockids_grip_color" style="min-width: 200px;">
                                <option value="">— Nevybráno (žádné auto-overlays) —</option>
                                <?php foreach ($available_colors as $c) : ?>
                                    <option value="<?php echo esc_attr($c); ?>" <?php selected($grip_color, $c); ?>>
                                        <?php echo esc_html($c); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">
                                Barva podle názvů SVG souborů ve složce gripy.
                            </p>
                        <?php else : ?>
                            <input type="text"
                                name="blockids_grip_color"
                                id="blockids_grip_color"
                                value="<?php echo esc_attr($grip_color); ?>"
                                class="regular-text"
                                placeholder="např. blue, orange, mint-purple">
                            <p class="description" style="color: #d63638;">
                                ⚠️ Ve složce nebyly nalezeny žádné SVG soubory — zadejte barvu ručně
                                nebo nejprve nahrajte SVG soubory do
                                <code><?php echo esc_html(BLOCKids_Grip_Scanner::get_grips_folder()); ?></code>
                            </p>
                        <?php endif; ?>
                    </td>
                </tr>

                <!-- Přehled nalezených overlays -->
                <tr>
                    <th style="padding-top: 14px; vertical-align: top;">
                        Nalezené overlays
                    </th>
                    <td>
                        <?php if (!empty($grip_color)) : ?>
                            <?php
                            $overlays_preview = BLOCKids_Grip_Scanner::get_overlays_for_color($grip_color);
                            $count_inputs     = count(array_filter($overlays_preview, fn($o) => $o['inputs']));
                            $count_standard   = count(array_filter($overlays_preview, fn($o) => !$o['inputs']));
                            ?>
                            <?php if (!empty($overlays_preview)) : ?>
                                <p style="margin: 0 0 8px; color: #2e7d32; font-weight: 600;">
                                    ✅ Nalezeno <?php echo count($overlays_preview); ?> SVG souborů pro barvu
                                    "<strong><?php echo esc_html($grip_color); ?></strong>"
                                </p>
                                <ul style="margin: 0 0 8px; padding-left: 20px; color: #555; font-size: 13px;">
                                    <li>🪜 <strong>Stupy</strong> (řada 1, inputs: true): <?php echo $count_inputs; ?> souborů</li>
                                    <li>🤏 <strong>Standard</strong> (řady 2–4, inputs: false): <?php echo $count_standard; ?> souborů</li>
                                </ul>

                                <!-- Rozbalitelná tabulka detailů -->
                                <details style="margin-top: 6px;">
                                    <summary style="cursor: pointer; color: #0073aa; font-size: 13px;">
                                        Zobrazit detail všech souborů (<?php echo count($overlays_preview); ?>)
                                    </summary>
                                    <table style="margin-top: 8px; border-collapse: collapse; font-size: 12px; width: 100%;">
                                        <thead>
                                            <tr style="background: #f0f0f1;">
                                                <th style="padding: 5px 8px; text-align: left; border: 1px solid #ddd;">Soubor</th>
                                                <th style="padding: 5px 8px; text-align: left; border: 1px solid #ddd;">Typ</th>
                                                <th style="padding: 5px 8px; text-align: left; border: 1px solid #ddd;">Orientace</th>
                                                <th style="padding: 5px 8px; text-align: center; border: 1px solid #ddd;">Rotace</th>
                                                <th style="padding: 5px 8px; text-align: center; border: 1px solid #ddd;">Stupy</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($overlays_preview as $o) : ?>
                                                <tr style="border-bottom: 1px solid #f0f0f1;">
                                                    <td style="padding: 4px 8px; border: 1px solid #ddd; font-family: monospace; color: #555;">
                                                        <?php echo esc_html($o['_source'] ?? $o['id']); ?>.svg
                                                    </td>
                                                    <td style="padding: 4px 8px; border: 1px solid #ddd;">
                                                        <?php echo esc_html($o['type']); ?>
                                                    </td>
                                                    <td style="padding: 4px 8px; border: 1px solid #ddd;">
                                                        <?php echo esc_html($o['orientation']); ?>
                                                    </td>
                                                    <td style="padding: 4px 8px; border: 1px solid #ddd; text-align: center;">
                                                        <?php echo $o['rotation'] ? '180°' : '0°'; ?>
                                                    </td>
                                                    <td style="padding: 4px 8px; border: 1px solid #ddd; text-align: center;">
                                                        <?php echo $o['inputs'] ? '✅' : '—'; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </details>
                            <?php else : ?>
                                <p style="color: #d63638;">
                                    ❌ Pro barvu "<strong><?php echo esc_html($grip_color); ?></strong>"
                                    nebyly nalezeny žádné SVG soubory.
                                    Zkontrolujte složku a názvy souborů.
                                </p>
                            <?php endif; ?>
                        <?php else : ?>
                            <p style="color: #888; font-style: italic; margin: 0;">
                                Vyberte barvu výše pro zobrazení přehledu.
                            </p>
                        <?php endif; ?>

                        <!-- Tlačítko pro promázání cache -->
                        <?php if (!empty($grip_color)) : ?>
                            <p style="margin-top: 10px;">
                                <a href="<?php echo esc_url(add_query_arg(array(
                                                'blockids_clear_grip_cache' => '1',
                                                'grip_color'               => $grip_color,
                                                '_wpnonce'                 => wp_create_nonce('blockids_clear_cache'),
                                            ), get_edit_post_link($post->ID))); ?>"
                                    class="button button-secondary" style="font-size: 12px;">
                                    🔄 Promázat cache overlays (<?php echo esc_html($grip_color); ?>)
                                </a>
                                <span style="font-size: 11px; color: #888; margin-left: 8px;">
                                    Cache se obnoví automaticky každou hodinu.
                                    Promažte ručně po nahrání nových SVG souborů.
                                </span>
                            </p>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>

        <!-- ===== MATRACE ===== -->
        <div id="blockids-fields-matrace" class="blockids-field-group" style="<?php echo $is_matrace ? '' : 'display:none;'; ?>">
            <div style="background: #f0faf0; padding: 12px 15px; border-radius: 4px; margin-bottom: 15px;">
                <strong>🟦 Nastavení matrace pro konfigurátor</strong>
                <p style="margin: 5px 0 0; color: #555;">Nastavte barvu, typ zákazníka a případně ceník dle šířky stěny.</p>
            </div>

            <table class="form-table">
                <tr>
                    <th><label for="blockids_color">Barva matrace</label></th>
                    <td>
                        <input type="color" name="blockids_color" id="blockids_color" value="<?php echo esc_attr($color ?: '#cccccc'); ?>" style="width: 60px; height: 40px; padding: 2px; cursor: pointer;">
                        <input type="text" id="blockids_color_text" value="<?php echo esc_attr($color); ?>" placeholder="#000000" style="width: 100px; margin-left: 5px;">
                        <p class="description">Barva ikonky matrace v konfiguratoru. Klikněte pro výběr barvy.</p>
                    </td>
                </tr>
                <tr>
                    <th><label>Typ zákazníka</label></th>
                    <td>
                        <label style="display: block; margin-bottom: 8px; cursor: pointer;">
                            <input type="radio" name="blockids_personal" value="yes" <?php checked($personal, 'yes'); ?>>
                            <strong>🏠 Domácnosti (family)</strong>
                            <span style="color: #666;"> — matrace s pevnou cenou pro rodiny</span>
                        </label>
                        <label style="display: block; cursor: pointer;">
                            <input type="radio" name="blockids_personal" value="no" <?php checked(($personal !== 'yes'), true); ?>>
                            <strong>🏢 Veřejné prostory (public)</strong>
                            <span style="color: #666;"> — matrace s cenou podle šířky stěny</span>
                        </label>
                        <p class="description" style="margin-top: 8px;">Zákazníkům se zobrazí jen matrace pro jejich typ. Typ zákazníka se nastavuje v jeho profilu.</p>
                    </td>
                </tr>
            </table>

            <!-- Ceník dle šířky - jen pro public -->
            <div id="blockids-prices-section" style="<?php echo ($personal !== 'yes' && $is_matrace) ? '' : 'display:none;'; ?>margin-top: 10px; padding: 15px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px;">
                <h4 style="margin-top: 0;">💰 Ceník dle šířky stěny</h4>
                <p style="color: #666; margin-bottom: 15px;">
                    Pro veřejné prostory se cena matrace odvíjí od šířky lezecké stěny.
                    Přidejte řádky s rozsahy šířek a příslušnými cenami.<br>
                    <em>Pokud nechcete ceník dle šířky, nechte tabulku prázdnou a použije se základní cena produktu.</em>
                </p>

                <table id="blockids-prices-table" class="widefat" style="max-width: 600px;">
                    <thead>
                        <tr>
                            <th style="width: 150px;">Šířka od (cm)</th>
                            <th style="width: 150px;">Šířka do (cm)</th>
                            <th style="width: 150px;">Cena (Kč)</th>
                            <th style="width: 50px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($prices)) : ?>
                            <?php foreach ($prices as $i => $row) : ?>
                                <tr class="blockids-price-row">
                                    <td><input type="number" name="blockids_price_min[]" value="<?php echo esc_attr($row['minWidth']); ?>" min="0" style="width:100%;"></td>
                                    <td><input type="number" name="blockids_price_max[]" value="<?php echo esc_attr($row['maxWidth']); ?>" min="0" style="width:100%;"></td>
                                    <td><input type="number" name="blockids_price_val[]" value="<?php echo esc_attr($row['price']); ?>" min="0" style="width:100%;"></td>
                                    <td><button type="button" class="button blockids-remove-price" title="Odebrat">✕</button></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

                <button type="button" id="blockids-add-price" class="button" style="margin-top: 10px;">
                    + Přidat cenový rozsah
                </button>
            </div>
        </div>

        </div><!-- /#blockids-fields-wrapper -->

        <script>
            jQuery(document).ready(function($) {

                // =====================================================================
                // KATEGORIE - zobrazit/skrýt sekce
                // =====================================================================
                function updateBlockidsFields() {
                    var categories = [];
                    $('#product_catchecklist input:checked, #product_catchecklist-pop input:checked').each(function() {
                        var label = $(this).closest('label, li').text().trim().toLowerCase();
                        categories.push(label);
                    });

                    var isDesky = false,
                        isGripy = false,
                        isMatrace = false;
                    categories.forEach(function(cat) {
                        if (cat.indexOf('desky') !== -1) isDesky = true;
                        if (cat.indexOf('gripy') !== -1) isGripy = true;
                        // Detekce dle textu "žíněnky a dopadové matrace" nebo obecně "matrace"
                        if (cat.indexOf('matrace') !== -1) isMatrace = true;
                    });
                    var hasAny = isDesky || isGripy || isMatrace;
                    var forConfigurator = $('#blockids_for_configurator').is(':checked');

                    // Zpráva "bez kategorie"
                    $('#blockids-no-category').toggle(!hasAny);

                    // Checkbox sekce - zobrazit jen když je přiřazena konfigurátorová kategorie
                    $('#blockids-enable-checkbox').toggle(hasAny);

                    // Wrapper s parametry - jen když je checkbox zaškrtnut
                    $('#blockids-fields-wrapper').toggle(hasAny && forConfigurator);

                    // Jednotlivé skupiny polí uvnitř wrapperu
                    $('#blockids-fields-desky').toggle(isDesky);
                    $('#blockids-fields-gripy').toggle(isGripy);
                    $('#blockids-fields-matrace').toggle(isMatrace);

                    if (isMatrace && $('input[name="blockids_personal"]:checked').val() === 'no') {
                        $('#blockids-prices-section').show();
                    } else {
                        $('#blockids-prices-section').hide();
                    }
                }

                $(document).on('change', '#product_catchecklist input, #product_catchecklist-pop input', function() {
                    setTimeout(updateBlockidsFields, 100);
                });
                $(document).on('change', 'input[name="blockids_personal"]', function() {
                    updateBlockidsFields();
                });
                // Checkbox "Produkt pro konfigurátor"
                $(document).on('change', '#blockids_for_configurator', function() {
                    updateBlockidsFields();
                });
                setTimeout(updateBlockidsFields, 500);

                // =====================================================================
                // BARVA - synchronizace color picker + text pole
                // =====================================================================
                $('#blockids_color').on('input change', function() {
                    $('#blockids_color_text').val($(this).val());
                });
                $('#blockids_color_text').on('input', function() {
                    var val = $(this).val();
                    if (/^#[0-9A-Fa-f]{6}$/.test(val)) {
                        $('#blockids_color').val(val);
                    }
                });

                // =====================================================================
                // CENÍK MATRACÍ - repeater
                // =====================================================================
                $('#blockids-add-price').on('click', function() {
                    var row = '<tr class="blockids-price-row">' +
                        '<td><input type="number" name="blockids_price_min[]" value="0" min="0" style="width:100%;"></td>' +
                        '<td><input type="number" name="blockids_price_max[]" value="0" min="0" style="width:100%;"></td>' +
                        '<td><input type="number" name="blockids_price_val[]" value="0" min="0" style="width:100%;"></td>' +
                        '<td><button type="button" class="button blockids-remove-price" title="Odebrat">✕</button></td>' +
                        '</tr>';
                    $('#blockids-prices-table tbody').append(row);
                });
                $(document).on('click', '.blockids-remove-price', function() {
                    $(this).closest('tr').remove();
                });

                // =====================================================================
                // SVG OVERLAY REPEATER
                // =====================================================================

                var overlayCounter = 0;

                function generateOverlayId() {
                    return 'ov_' + Date.now().toString(36) + '_' + Math.random().toString(36).substr(2, 5);
                }

                function createOverlayRow(data) {
                    data = data || {};
                    var id = data.id || generateOverlayId();
                    var image = data.image || '';
                    var type = data.type || '';
                    var orientation = data.orientation || 'vertical';
                    var inputs = data.inputs ? true : false;
                    var idx = overlayCounter++;

                    var previewInner = image ?
                        '<img src="' + $('<div>').text(image).html() + '" alt="overlay SVG">' :
                        '<span style="font-size:22px;color:#ccc;">🖼️</span>';

                    var $row = $('<div class="blockids-overlay-row">').attr('data-idx', idx);

                    // Preview (kliknutím otevře picker)
                    var $preview = $('<div class="blockids-overlay-preview">').html(previewInner);
                    $row.append($preview);

                    // Skrytá pole s daty
                    $row.append('<input type="hidden" name="blockids_overlay_id[]" value="' + $('<div>').text(id).html() + '">');
                    $row.append('<input type="hidden" name="blockids_overlay_image[]" class="blockids-overlay-image-url" value="' + $('<div>').text(image).html() + '">');

                    // Formulářová pole
                    var $fields = $('<div class="blockids-overlay-fields">');

                    // URL souboru (jen zobrazení)
                    $fields.append(
                        $('<div class="blockids-overlay-field full-width">').append(
                            $('<label>').text('URL souboru'),
                            $('<div class="blockids-overlay-url-display">').text(image || '— nevybráno —')
                        )
                    );

                    // Typ
                    var $typeField = $('<div class="blockids-overlay-field">').append(
                        $('<label>').text('Typ'),
                        $('<select name="blockids_overlay_type[]">').append(
                            $('<option value="">').text('— vyberte —'),
                            $('<option value="rectangle">').text('rectangle').prop('selected', type === 'rectangle'),
                            $('<option value="triangle_top">').text('triangle_top').prop('selected', type === 'triangle_top'),
                            $('<option value="triangle_left">').text('triangle_left').prop('selected', type === 'triangle_left')
                        )
                    );
                    $fields.append($typeField);

                    // Orientace
                    var $orientField = $('<div class="blockids-overlay-field">').append(
                        $('<label>').text('Orientace'),
                        $('<select name="blockids_overlay_orientation[]">').append(
                            $('<option value="vertical">').text('vertical').prop('selected', orientation === 'vertical'),
                            $('<option value="horizontal">').text('horizontal').prop('selected', orientation === 'horizontal')
                        )
                    );
                    $fields.append($orientField);

                    // Inputs checkbox
                    var $inputsField = $('<div class="blockids-overlay-field checkbox-field">').append(
                        $('<input type="hidden">').attr('name', 'blockids_overlay_inputs_idx[]').val(idx),
                        $('<input type="checkbox">').attr({
                            name: 'blockids_overlay_inputs_' + idx,
                            id: 'blockids_overlay_inputs_' + idx
                        }).prop('checked', inputs),
                        $('<label>').attr('for', 'blockids_overlay_inputs_' + idx).text('Stupy (inputs)')
                    );
                    $fields.append($inputsField);

                    $row.append($fields);

                    // Akce (vybrat SVG, odebrat)
                    var $actions = $('<div class="blockids-overlay-actions">');
                    var $btnSelect = $('<button type="button" class="blockids-overlay-btn-select">').text('📁 Vybrat SVG');
                    var $btnRemove = $('<button type="button" class="blockids-overlay-btn-remove">').text('✕ Odebrat');
                    $actions.append($btnSelect, $btnRemove);
                    $row.append($actions);

                    // WordPress media picker
                    function openMediaPicker() {
                        var frame = wp.media({
                            title: 'Vyberte SVG overlay',
                            button: { text: 'Použít jako overlay' },
                            multiple: false,
                            library: { type: ['image/svg+xml'] }
                        });
                        frame.on('select', function() {
                            var attachment = frame.state().get('selection').first().toJSON();
                            var url = attachment.url;
                            $row.find('.blockids-overlay-image-url').val(url);
                            $row.find('.blockids-overlay-url-display').text(url);
                            $preview.html('<img src="' + $('<div>').text(url).html() + '" alt="overlay SVG">');
                        });
                        frame.open();
                    }

                    $btnSelect.on('click', openMediaPicker);
                    $preview.on('click', openMediaPicker);

                    // Smazat řádek
                    $btnRemove.on('click', function() {
                        $row.remove();
                    });

                    return $row;
                }

                // Načíst existující overlays z PHP (JSON v data atributu)
                var $list = $('#blockids-overlays-list');
                var existingData = $list.data('overlays');
                if (existingData && Array.isArray(existingData)) {
                    existingData.forEach(function(overlay) {
                        $list.append(createOverlayRow(overlay));
                    });
                }

                // Přidat nový prázdný řádek
                $('#blockids-overlay-add').on('click', function() {
                    $list.append(createOverlayRow({}));
                });

                // Drag & drop řazení (pokud je jQuery UI Sortable dostupné)
                if ($.fn.sortable) {
                    $list.sortable({
                        handle: '.blockids-overlay-preview',
                        cursor: 'grab'
                    });
                }

            }); // end jQuery ready
        </script>
    <?php
    }

    // =========================================================================
    // ULOŽIT META POLE PRODUKTU
    // =========================================================================

    public function save_product_meta_fields($post_id)
    {
        if (
            !isset($_POST['blockids_product_meta_nonce']) ||
            !wp_verify_nonce($_POST['blockids_product_meta_nonce'], 'blockids_product_meta')
        ) {
            return;
        }

        // Checkbox "Produkt pro konfigurátor"
        $for_configurator = isset($_POST['blockids_for_configurator']) ? '1' : '0';
        update_post_meta($post_id, '_blockids_for_configurator', $for_configurator);

        // Typ desky
        if (isset($_POST['blockids_type'])) {
            update_post_meta($post_id, '_blockids_type', sanitize_text_field($_POST['blockids_type']));
        }

        // Umístění
        if (isset($_POST['blockids_location'])) {
            update_post_meta($post_id, '_blockids_location', sanitize_text_field($_POST['blockids_location']));
        }

        // Barva matrace
        if (isset($_POST['blockids_color'])) {
            update_post_meta($post_id, '_blockids_color', sanitize_text_field($_POST['blockids_color']));
        }

        // Osobní (family/public)
        if (isset($_POST['blockids_personal'])) {
            update_post_meta($post_id, '_blockids_personal', sanitize_text_field($_POST['blockids_personal']));
        }

        // =====================================================================
        // OVERLAYS (gripy) - sestavit JSON z SVG repeater polí
        // =====================================================================
        // Uložit barvu gripu (pro auto-scanner)
        if (isset($_POST['blockids_grip_color'])) {
            $new_color = sanitize_text_field($_POST['blockids_grip_color']);
            $old_color = get_post_meta($post_id, '_blockids_grip_color', true);

            // Promázat cache staré barvy při změně
            if ($old_color && $old_color !== $new_color) {
                BLOCKids_Grip_Scanner::clear_cache($old_color);
            }

            if (!empty($new_color)) {
                update_post_meta($post_id, '_blockids_grip_color', $new_color);
            } else {
                delete_post_meta($post_id, '_blockids_grip_color');
            }
        }

        if (isset($_POST['blockids_overlay_image']) && is_array($_POST['blockids_overlay_image'])) {

            $images       = $_POST['blockids_overlay_image'];
            $ids          = isset($_POST['blockids_overlay_id'])          ? (array) $_POST['blockids_overlay_id']          : array();
            $types        = isset($_POST['blockids_overlay_type'])        ? (array) $_POST['blockids_overlay_type']        : array();
            $orientations = isset($_POST['blockids_overlay_orientation']) ? (array) $_POST['blockids_overlay_orientation'] : array();
            $inputs_idxs  = isset($_POST['blockids_overlay_inputs_idx'])  ? (array) $_POST['blockids_overlay_inputs_idx']  : array();

            $overlays = array();
            foreach ($images as $i => $image_url) {
                $image_url = trim($image_url);
                if (empty($image_url)) {
                    continue; // Přeskočit řádky bez vybraného SVG
                }

                // Zjistit, zda je inputs checkbox zaškrtnutý pro tento index
                $row_idx    = isset($inputs_idxs[$i]) ? (int) $inputs_idxs[$i] : $i;
                $is_inputs  = isset($_POST['blockids_overlay_inputs_' . $row_idx]);

                // Orientace: přijmout jen povolené hodnoty
                $orientation = (isset($orientations[$i]) && in_array($orientations[$i], array('vertical', 'horizontal')))
                    ? $orientations[$i]
                    : 'vertical';

                $overlays[] = array(
                    'id'          => sanitize_text_field($ids[$i] ?? '') ?: ('ov_' . uniqid()),
                    'type'        => sanitize_text_field($types[$i] ?? ''),
                    'orientation' => $orientation,
                    'rotation'    => 0,
                    'inputs'      => $is_inputs,
                    'image'       => esc_url_raw($image_url),
                );
            }

            if (!empty($overlays)) {
                update_post_meta($post_id, '_blockids_overlays', wp_json_encode($overlays));
            } else {
                // Všechny řádky odstraněny → smazat meta
                delete_post_meta($post_id, '_blockids_overlays');
            }
        }
        // Poznámka: pokud blockids_overlay_image v POST chybí úplně,
        // znamená to že produkt není gripy kategorie → overlays neměníme

        // =====================================================================
        // Ceny dle šířky - sestavit JSON z repeater polí
        // =====================================================================
        if (isset($_POST['blockids_price_min']) && is_array($_POST['blockids_price_min'])) {
            $prices = array();
            $mins = $_POST['blockids_price_min'];
            $maxs = $_POST['blockids_price_max'];
            $vals = $_POST['blockids_price_val'];

            for ($i = 0; $i < count($mins); $i++) {
                $min = (int) $mins[$i];
                $max = (int) $maxs[$i];
                $val = (int) $vals[$i];

                if ($max > 0 || $val > 0) {
                    $prices[] = array(
                        'minWidth' => $min,
                        'maxWidth' => $max,
                        'price'    => $val,
                    );
                }
            }

            if (!empty($prices)) {
                update_post_meta($post_id, '_blockids_prices', json_encode($prices));
            } else {
                delete_post_meta($post_id, '_blockids_prices');
            }
        } else {
            // Slug matrace změněn na zinenky-a-dopadove-matrace
            $terms = wp_get_post_terms($post_id, 'product_cat', array('fields' => 'slugs'));
            if (in_array('zinenky-a-dopadove-matrace', $terms)) {
                delete_post_meta($post_id, '_blockids_prices');
            }
        }
    }

    // =========================================================================
    // ADMIN ASSETS
    // =========================================================================

    public function enqueue_admin_assets($hook)
    {
        if (!in_array($hook, array('post.php', 'post-new.php'))) {
            return;
        }

        global $post;
        if ($post && $post->post_type === 'product') {

            // Načíst WordPress media library (nutné pro SVG picker)
            // Ochrana před dvojím voláním které může kolidovat s WooCommerce
            if (!did_action('wp_enqueue_media')) {
                wp_enqueue_media();
            }

            // Vlastní style handle - bezpečnější než přidávat k woocommerce_admin_styles
            // (ten nemusí být v danou chvíli enqueued → Deprecated warnings)
            wp_register_style('blockids-admin-styles', false, array(), BLOCKIDS_CONFIGURATOR_VERSION);
            wp_enqueue_style('blockids-admin-styles');

            // Styl pro meta box + overlay repeater
            wp_add_inline_style('blockids-admin-styles', '
                #blockids_product_meta .form-table th { width: 180px; padding: 12px 10px; }
                #blockids_product_meta .form-table td { padding: 12px 10px; }
                #blockids_product_meta .form-table td .description { margin-top: 4px; }
                #blockids-prices-table td { padding: 6px 4px; }
                #blockids-prices-table input { text-align: right; }
                .blockids-price-row:hover { background: #f0f0f1; }

                /* --- SVG Overlay repeater --- */
                #blockids-overlays-list { margin-bottom: 10px; }

                .blockids-overlay-row {
                    display: flex;
                    align-items: flex-start;
                    gap: 12px;
                    background: #f9f9f9;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                    padding: 10px 12px;
                    margin-bottom: 8px;
                }
                .blockids-overlay-preview {
                    flex-shrink: 0;
                    width: 64px;
                    height: 64px;
                    border: 2px dashed #ccc;
                    border-radius: 3px;
                    background: #f5f5f5;
                    overflow: hidden;
                    cursor: pointer;
                    transition: border-color .15s;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: #aaa;
                    font-size: 22px;
                    line-height: 1;
                }
                .blockids-overlay-preview:hover { border-color: #0073aa; }
                .blockids-overlay-preview img {
                    width: 100%;
                    height: 100%;
                    object-fit: contain;
                    display: block;
                }
                .blockids-overlay-fields {
                    flex: 1;
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 6px 14px;
                    align-items: start;
                }
                .blockids-overlay-field label {
                    display: block;
                    font-size: 11px;
                    color: #666;
                    margin-bottom: 2px;
                    font-weight: 500;
                }
                .blockids-overlay-field select,
                .blockids-overlay-field input[type="text"] {
                    width: 100%;
                    padding: 4px 6px;
                    border: 1px solid #ccc;
                    border-radius: 3px;
                    font-size: 13px;
                    background: #fff;
                }
                .blockids-overlay-field.full-width { grid-column: 1 / -1; }
                .blockids-overlay-field.checkbox-field {
                    grid-column: 1 / -1;
                    display: flex;
                    align-items: center;
                    gap: 6px;
                    margin-top: 2px;
                }
                .blockids-overlay-field.checkbox-field label {
                    margin: 0;
                    font-size: 13px;
                    color: #333;
                    font-weight: normal;
                }
                .blockids-overlay-url-display {
                    font-size: 11px;
                    color: #888;
                    word-break: break-all;
                    max-width: 100%;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    white-space: nowrap;
                    padding: 3px 6px;
                    background: #fff;
                    border: 1px solid #eee;
                    border-radius: 3px;
                }
                .blockids-overlay-actions {
                    flex-shrink: 0;
                    display: flex;
                    flex-direction: column;
                    gap: 5px;
                }
                .blockids-overlay-btn-select {
                    background: #0073aa;
                    color: #fff;
                    border: none;
                    padding: 5px 9px;
                    border-radius: 3px;
                    cursor: pointer;
                    font-size: 11px;
                    white-space: nowrap;
                }
                .blockids-overlay-btn-select:hover { background: #006799; }
                .blockids-overlay-btn-remove {
                    background: #cc1818;
                    color: #fff;
                    border: none;
                    padding: 5px 9px;
                    border-radius: 3px;
                    cursor: pointer;
                    font-size: 11px;
                    white-space: nowrap;
                }
                .blockids-overlay-btn-remove:hover { background: #a00; }
                #blockids-overlay-add {
                    display: inline-flex;
                    align-items: center;
                    gap: 6px;
                    background: #f0f8ff;
                    border: 1px dashed #0073aa;
                    color: #0073aa;
                    padding: 8px 14px;
                    border-radius: 4px;
                    cursor: pointer;
                    font-size: 13px;
                    font-weight: 500;
                    margin-top: 2px;
                }
                #blockids-overlay-add:hover { background: #e0f0ff; }
            ');
        }
    }

    // =========================================================================
    // SETTINGS PAGE
    // =========================================================================

    public function render_settings_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (isset($_POST['blockids_settings_submit'])) {
            check_admin_referer('blockids_settings');

            update_option('blockids_configurator_url', sanitize_text_field($_POST['configurator_url']));
            update_option('blockids_api_base_url', sanitize_text_field($_POST['api_base_url']));
            update_option('blockids_jwt_secret_key', sanitize_text_field($_POST['jwt_secret_key']));
            update_option('blockids_jwt_expiration', intval($_POST['jwt_expiration']));

            echo '<div class="notice notice-success"><p>' . __('Nastavení uloženo.', 'blockids-configurator') . '</p></div>';
        }

        $configurator_url = get_option('blockids_configurator_url', 'https://konfigurator.blockids.eu');
        $api_base_url     = get_option('blockids_api_base_url', home_url('/wp-json/blockids/v1'));
        $jwt_secret_key   = get_option('blockids_jwt_secret_key');
        $jwt_expiration   = get_option('blockids_jwt_expiration', 3600);
        $launch_url       = add_query_arg('blockids_launch', '1', home_url('/'));

    ?>
        <div class="wrap">
            <h1><?php _e('BLOCKids Konfigurátor - Nastavení', 'blockids-configurator'); ?></h1>

            <form method="post" action="">
                <?php wp_nonce_field('blockids_settings'); ?>

                <table class="form-table">
                    <tr>
                        <th><label for="configurator_url">URL Konfiguratoru</label></th>
                        <td>
                            <input type="url" id="configurator_url" name="configurator_url" value="<?php echo esc_attr($configurator_url); ?>" class="regular-text">
                            <p class="description">Např. <code>https://konfigurator.blockids.eu</code></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="api_base_url">API Base URL</label></th>
                        <td>
                            <input type="url" id="api_base_url" name="api_base_url" value="<?php echo esc_attr($api_base_url); ?>" class="regular-text">
                            <p class="description">Automaticky: <code><?php echo esc_html(home_url('/wp-json/blockids/v1')); ?></code></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="jwt_secret_key">JWT Secret Key</label></th>
                        <td>
                            <input type="text" id="jwt_secret_key" name="jwt_secret_key" value="<?php echo esc_attr($jwt_secret_key); ?>" class="regular-text">
                            <p class="description">Tajný klíč pro JWT tokeny. Nechte vygenerovaný klíč.</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="jwt_expiration">Platnost JWT (sec)</label></th>
                        <td>
                            <input type="number" id="jwt_expiration" name="jwt_expiration" value="<?php echo esc_attr($jwt_expiration); ?>" min="300" step="300" style="width: 100px;">
                            <span class="description"> sekund (3600 = 1 hodina)</span>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="submit" name="blockids_settings_submit" class="button button-primary">
                        <?php _e('Uložit nastavení', 'blockids-configurator'); ?>
                    </button>
                </p>
            </form>

            <hr>

            <h2>🔧 Testování</h2>
            <?php
            $test_user_id = get_current_user_id();
            $test_token   = $test_user_id ? BLOCKids_Configurator_Auth::generate_token($test_user_id) : null;
            ?>
            <?php if ($test_token) : ?>
                <table class="form-table">
                    <tr>
                        <th>Test JWT Token</th>
                        <td>
                            <code style="word-break: break-all; font-size: 11px;"><?php echo esc_html($test_token); ?></code>
                        </td>
                    </tr>
                    <tr>
                        <th>Link do konfiguratoru</th>
                        <td>
                            <a href="<?php echo esc_url($launch_url); ?>" target="_blank" class="button">
                                🚀 Otevřít konfigurátor (SSO)
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <th>API Endpointy</th>
                        <td>
                            <a href="<?php echo esc_url(home_url('/wp-json/blockids/v1/grips/cs')); ?>" target="_blank">
                                /grips/cs
                            </a> &nbsp;|&nbsp;
                            <a href="<?php echo esc_url(home_url('/wp-json/blockids/v1/mattresses/cs')); ?>" target="_blank">
                                /mattresses/cs
                            </a> &nbsp;|&nbsp;
                            <a href="<?php echo esc_url(home_url('/wp-json/blockids/v1/desks/cs')); ?>" target="_blank">
                                /desks/cs
                            </a>
                        </td>
                    </tr>
                </table>
            <?php else : ?>
                <p>Přihlaste se jako uživatel pro testování JWT tokenů.</p>
            <?php endif; ?>
        </div>
<?php
    }

        // -------------------------------------------------------------------------
    // Tuhle metodu přidej jako novou do třídy:
    // -------------------------------------------------------------------------
    public function handle_clear_grip_cache() {
        if (!isset($_GET['blockids_clear_grip_cache'])) {
            return;
        }
        if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'blockids_clear_cache')) {
            return;
        }
        if (!current_user_can('manage_options')) {
            return;
        }

        $color = sanitize_text_field($_GET['grip_color'] ?? '');
        BLOCKids_Grip_Scanner::clear_cache($color ?: null);

        add_action('admin_notices', function() use ($color) {
            echo '<div class="notice notice-success is-dismissible"><p>✅ Cache gripy promázána'
                . ($color ? ' pro barvu <strong>' . esc_html($color) . '</strong>' : '') . '.</p></div>';
        });
    }
}

// Instanciovat třídu - zaregistruje všechny hooky (meta box, settings stránka, admin assets)
new BLOCKids_Configurator_Settings();