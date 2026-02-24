<?php
/**
 * Admin settings page + Product meta fields
 * 
 * VERZE: 2.3.0
 * ZMĚNY oproti 2.2.0:
 * - Sekce gripy: textarea → vizuální SVG repeater s WordPress media library
 * - enqueue_admin_assets: přidáno wp_enqueue_media() + inline JS pro repeater
 * - save_product_meta_fields: přepsáno ukládání overlays z repeater polí
 */

if (!defined('ABSPATH')) {
    exit;
}

class BLOCKids_Configurator_Settings {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu_page'));
        add_action('admin_init', array($this, 'register_settings'));
        
        // Product meta fields - vlastní meta box místo woocommerce_product_options
        add_action('add_meta_boxes', array($this, 'add_product_meta_box'));
        add_action('woocommerce_process_product_meta', array($this, 'save_product_meta_fields'));
        
        // Admin JS/CSS
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }
    
    public function add_menu_page() {
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
    
    public function register_settings() {
        register_setting('blockids_configurator_settings', 'blockids_configurator_url');
        register_setting('blockids_configurator_settings', 'blockids_api_base_url');
        register_setting('blockids_configurator_settings', 'blockids_jwt_secret_key');
        register_setting('blockids_configurator_settings', 'blockids_jwt_expiration');
    }
    
    // =========================================================================
    // PRODUCT META BOX
    // =========================================================================
    
    public function add_product_meta_box() {
        add_meta_box(
            'blockids_product_meta',
            '🧗 BLOCKids – Nastavení pro konfigurátor',
            array($this, 'render_product_meta_box'),
            'product',
            'normal',
            'high'
        );
    }
    
    public function render_product_meta_box($post) {
        wp_nonce_field('blockids_product_meta', 'blockids_product_meta_nonce');
        
        // Aktuální hodnoty
        $type        = get_post_meta($post->ID, '_blockids_type', true);
        $location    = get_post_meta($post->ID, '_blockids_location', true);
        $color       = get_post_meta($post->ID, '_blockids_color', true);
        $personal    = get_post_meta($post->ID, '_blockids_personal', true);
        $prices_raw  = get_post_meta($post->ID, '_blockids_prices', true);
        $overlays_raw = get_post_meta($post->ID, '_blockids_overlays', true);
        
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
        
        // Zjistit aktuální kategorie
        $terms      = wp_get_post_terms($post->ID, 'product_cat', array('fields' => 'slugs'));
        $is_desky   = in_array('desky', $terms);
        $is_gripy   = in_array('gripy', $terms);
        $is_matrace = in_array('matrace', $terms);
        $has_category = $is_desky || $is_gripy || $is_matrace;
        
        ?>
        
        <!-- Zpráva když produkt nemá konfigurátorovou kategorii -->
        <div id="blockids-no-category" style="<?php echo $has_category ? 'display:none;' : ''; ?>padding: 15px; background: #f0f0f1; border-left: 4px solid #dba617; margin-bottom: 15px;">
            <p style="margin:0;">
                <strong>ℹ️ Tento produkt nemá přiřazenou konfigurátorovou kategorii.</strong><br>
                Přiřaďte produkt do jedné z kategorií: <strong>desky</strong>, <strong>gripy</strong> nebo <strong>matrace</strong> 
                (v panelu "Kategorie produktu" vpravo) a tato sekce se automaticky zobrazí.
            </p>
        </div>
        
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
                    U chytů stačí nastavit <strong>název</strong>, <strong>cenu</strong> a <strong>obrázek</strong> v základních datech produktu výše.
                    Konfigurátor automaticky spočítá kolik sad chytů zákazník potřebuje podle počtu desek.
                </p>
            </div>
            
            <table class="form-table">
                <tr>
                    <th style="padding-top: 14px;"><label>Vizuální overlays</label></th>
                    <td>
                        <p class="description" style="margin-bottom: 12px;">
                            SVG obrázky chytů zobrazené přes desky v konfiguratoru.<br>
                            <strong>Pro každou kombinaci Typ desky × Orientace přidejte jeden overlay.</strong><br>
                            Např: obdélník+vertikální, obdélník+horizontální, trojúhelník nahoře+vertikální…<br>
                            Pokud nemáte SVG soubory, nechte prázdné — konfigurátor funguje i bez nich.
                        </p>

                        <!-- Repeater seznam overlays - data se načtou přes JS -->
                        <div id="blockids-overlays-list" 
                             data-overlays="<?php echo esc_attr(wp_json_encode($overlays)); ?>">
                            <!-- Řádky vykreslí JavaScript níže -->
                        </div>

                        <button type="button" id="blockids-overlay-add">
                            ＋ Přidat overlay
                        </button>

                        <p class="description" style="margin-top: 8px; font-size: 11px; color: #888;">
                            Klikněte na náhled nebo tlačítko <strong>📁 SVG</strong> pro výběr ze souborů. 
                            Orientace určuje, pro který směr desky se overlay použije. 
                            Inputs = zákazník si chyty rozmístí sám.
                        </p>
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
                
                var isDesky = false, isGripy = false, isMatrace = false;
                categories.forEach(function(cat) {
                    if (cat.indexOf('desky')   !== -1) isDesky   = true;
                    if (cat.indexOf('gripy')   !== -1) isGripy   = true;
                    if (cat.indexOf('matrace') !== -1) isMatrace = true;
                });
                var hasAny = isDesky || isGripy || isMatrace;
                
                $('#blockids-no-category').toggle(!hasAny);
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
                var id          = data.id          || generateOverlayId();
                var image       = data.image        || '';
                var type        = data.type         || '';
                var orientation = data.orientation  || 'vertical';
                var inputs      = data.inputs       ? true : false;
                var idx         = overlayCounter++;

                var previewInner = image
                    ? '<img src="' + $('<div>').text(image).html() + '" alt="overlay SVG">'
                    : '<span style="font-size:22px;color:#ccc;">🖼️</span>';

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
                    '<div class="blockids-overlay-field full-width">' +
                    '<label>SVG soubor</label>' +
                    '<div class="blockids-overlay-url-display">' + (image ? $('<div>').text(image).html() : '— nevybráno —') + '</div>' +
                    '</div>'
                );

                // Orientace
                $fields.append(
                    '<div class="blockids-overlay-field">' +
                    '<label>Orientace desky</label>' +
                    '<select name="blockids_overlay_orientation[]">' +
                    '<option value="vertical"'   + (orientation === 'vertical'   ? ' selected' : '') + '>Vertikální</option>' +
                    '<option value="horizontal"' + (orientation === 'horizontal' ? ' selected' : '') + '>Horizontální</option>' +
                    '</select>' +
                    '</div>'
                );

                // Typ desky - musí matchovat s desk.type v konfiguratoru
                $fields.append(
                    '<div class="blockids-overlay-field">' +
                    '<label>Typ desky</label>' +
                    '<select name="blockids_overlay_type[]">' +
                    '<option value="rectangle"'     + (type === 'rectangle'     ? ' selected' : '') + '>⬜ Obdélník (rectangle)</option>' +
                    '<option value="triangle_top"'  + (type === 'triangle_top'  ? ' selected' : '') + '>🔺 Trojúhelník nahoře (triangle_top)</option>' +
                    '<option value="triangle_left"' + (type === 'triangle_left' ? ' selected' : '') + '>◀ Trojúhelník vlevo (triangle_left)</option>' +
                    '</select>' +
                    '</div>'
                );

                // Inputs checkbox
                $fields.append(
                    '<div class="blockids-overlay-field checkbox-field">' +
                    '<input type="checkbox" id="blockids_overlay_inputs_' + idx + '" name="blockids_overlay_inputs_' + idx + '" value="1"' + (inputs ? ' checked' : '') + '>' +
                    '<input type="hidden" name="blockids_overlay_inputs_idx[]" value="' + idx + '">' +
                    '<label for="blockids_overlay_inputs_' + idx + '">Inputs (zákazník rozmístí chyty sám)</label>' +
                    '</div>'
                );

                $row.append($fields);

                // Akční tlačítka
                var $actions = $('<div class="blockids-overlay-actions">');
                var $btnSelect = $('<button type="button" class="blockids-overlay-btn-select">📁 SVG</button>');
                var $btnRemove = $('<button type="button" class="blockids-overlay-btn-remove">✕ Smazat</button>');
                $actions.append($btnSelect).append($btnRemove);
                $row.append($actions);

                // Media picker
                function openMediaPicker() {
                    var frame = wp.media({
                        title: 'Vybrat SVG overlay pro chyty',
                        button: { text: 'Použít tento SVG' },
                        multiple: false,
                        library: { type: ['image/svg+xml', 'image'] }
                    });
                    frame.on('select', function() {
                        var attachment = frame.state().get('selection').first().toJSON();
                        var url = attachment.url;
                        $row.find('.blockids-overlay-image-url').val(url);
                        $row.find('.blockids-overlay-url-display').text(url);
                        $preview.html('<img src="' + url + '" alt="overlay SVG">');
                    });
                    frame.open();
                }

                $btnSelect.on('click', openMediaPicker);
                $preview.on('click',   openMediaPicker);

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
                $list.sortable({ handle: '.blockids-overlay-preview', cursor: 'grab' });
            }

        }); // end jQuery ready
        </script>
        <?php
    }
    
    // =========================================================================
    // ULOŽIT META POLE PRODUKTU
    // =========================================================================
    
    public function save_product_meta_fields($post_id) {
        if (!isset($_POST['blockids_product_meta_nonce']) || 
            !wp_verify_nonce($_POST['blockids_product_meta_nonce'], 'blockids_product_meta')) {
            return;
        }
        
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
            $terms = wp_get_post_terms($post_id, 'product_cat', array('fields' => 'slugs'));
            if (in_array('matrace', $terms)) {
                delete_post_meta($post_id, '_blockids_prices');
            }
        }
    }
    
    // =========================================================================
    // ADMIN ASSETS
    // =========================================================================
    
    public function enqueue_admin_assets($hook) {
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
    
    public function render_settings_page() {
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
        
        $configurator_url = get_option('blockids_configurator_url', 'https://configurator.blockids.eu');
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
                            <p class="description">Např. <code>https://configurator.blockids.eu</code></p>
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
}

// Instanciovat třídu - zaregistruje všechny hooky (meta box, settings stránka, admin assets)
new BLOCKids_Configurator_Settings();