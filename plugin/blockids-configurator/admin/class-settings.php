<?php
/**
 * Admin settings page + Product meta fields
 * 
 * VERZE: 2.2.0
 * 
 * Meta pole se zobrazují podle kategorie produktu:
 * - Desky: typ desky + umístění
 * - Gripy: (zatím bez speciálních polí, overlays jsou technická záležitost)
 * - Matrace: barva + osobní/veřejné + ceník dle šířky
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
    
    /**
     * Přidat meta box na stránku editace produktu
     */
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
    
    /**
     * Render meta boxu - zobrazí pole podle kategorie
     */
    public function render_product_meta_box($post) {
        wp_nonce_field('blockids_product_meta', 'blockids_product_meta_nonce');
        
        // Aktuální hodnoty
        $type = get_post_meta($post->ID, '_blockids_type', true);
        $location = get_post_meta($post->ID, '_blockids_location', true);
        $color = get_post_meta($post->ID, '_blockids_color', true);
        $personal = get_post_meta($post->ID, '_blockids_personal', true);
        $prices_raw = get_post_meta($post->ID, '_blockids_prices', true);
        $overlays_raw = get_post_meta($post->ID, '_blockids_overlays', true);
        
        // Rozparsovat ceny pro repeater
        $prices = array();
        if ($prices_raw) {
            $decoded = json_decode($prices_raw, true);
            if (is_array($decoded)) {
                $prices = $decoded;
            }
        }
        
        // Zjistit aktuální kategorie
        $terms = wp_get_post_terms($post->ID, 'product_cat', array('fields' => 'slugs'));
        $is_desky = in_array('desky', $terms);
        $is_gripy = in_array('gripy', $terms);
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
            <div style="background: #e7f5fe; padding: 12px 15px; border-radius: 4px; margin-bottom: 15px;">
                <strong>📐 Nastavení desky pro konfigurátor</strong>
                <p style="margin: 5px 0 0; color: #555;">Každá deska musí mít vyplněný <strong>tvar</strong> a <strong>umístění</strong>, jinak se v konfiguratoru nezobrazí správně.</p>
            </div>
            
            <table class="form-table">
                <tr>
                    <th><label for="blockids_type">Tvar desky <span style="color:red;">*</span></label></th>
                    <td>
                        <select name="blockids_type" id="blockids_type" style="min-width: 250px;">
                            <option value="" <?php selected($type, ''); ?>>— Vyberte tvar —</option>
                            <option value="rectangle" <?php selected($type, 'rectangle'); ?>>⬜ Obdélník (standardní panel)</option>
                            <option value="triangle" <?php selected($type, 'triangle'); ?>>🔺 Trojúhelník</option>
                            <option value="blackboard" <?php selected($type, 'blackboard'); ?>>📝 Kreslící tabule</option>
                        </select>
                        <p class="description">Určuje tvar panelu v konfiguratoru a jak se počítají chyty.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="blockids_location">Umístění <span style="color:red;">*</span></label></th>
                    <td>
                        <select name="blockids_location" id="blockids_location" style="min-width: 250px;">
                            <option value="" <?php selected($location, ''); ?>>— Vyberte umístění —</option>
                            <option value="indoor" <?php selected($location, 'indoor'); ?>>🏠 Interiér (indoor)</option>
                            <option value="outdoor" <?php selected($location, 'outdoor'); ?>>🌳 Exteriér (outdoor)</option>
                        </select>
                        <p class="description">Zákazník si v konfiguratoru vybere typ stěny a zobrazí se mu jen desky pro daný typ.</p>
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
                    <th><label>Vizuální overlay (volitelné)</label></th>
                    <td>
                        <textarea name="blockids_overlays" rows="3" class="large-text" placeholder="Nechte prázdné pokud nemáte speciální obrázky"><?php echo esc_textarea($overlays_raw); ?></textarea>
                        <p class="description">
                            <strong>Pokročilé nastavení</strong> — obrázky chytů zobrazené na deskách v konfiguratoru. 
                            Pokud nemáte připravené overlay obrázky, nechte prázdné. 
                            Konfigurátor bude fungovat i bez nich.<br>
                            <em>Formát vyplní programátor.</em>
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
        
        <!-- Hidden field pro overlays JSON (desky) -->
        <input type="hidden" name="blockids_overlays_desks" value="<?php echo esc_attr($overlays_raw); ?>">
        
        <script>
        jQuery(document).ready(function($) {
            
            // ===== Synchronizace color pickeru =====
            $('#blockids_color').on('input change', function() {
                $('#blockids_color_text').val($(this).val());
            });
            $('#blockids_color_text').on('input change', function() {
                var val = $(this).val();
                if (/^#[0-9A-Fa-f]{6}$/.test(val)) {
                    $('#blockids_color').val(val);
                }
            });
            
            // ===== Zobrazit/skrýt ceník dle typu zákazníka =====
            $('input[name="blockids_personal"]').on('change', function() {
                if ($(this).val() === 'no') {
                    $('#blockids-prices-section').slideDown(200);
                } else {
                    $('#blockids-prices-section').slideUp(200);
                }
            });
            
            // ===== Přidat řádek ceníku =====
            $('#blockids-add-price').on('click', function() {
                var row = '<tr class="blockids-price-row">' +
                    '<td><input type="number" name="blockids_price_min[]" value="0" min="0" style="width:100%;"></td>' +
                    '<td><input type="number" name="blockids_price_max[]" value="0" min="0" style="width:100%;"></td>' +
                    '<td><input type="number" name="blockids_price_val[]" value="0" min="0" style="width:100%;"></td>' +
                    '<td><button type="button" class="button blockids-remove-price" title="Odebrat">✕</button></td>' +
                    '</tr>';
                $('#blockids-prices-table tbody').append(row);
            });
            
            // ===== Odebrat řádek ceníku =====
            $(document).on('click', '.blockids-remove-price', function() {
                $(this).closest('tr').remove();
            });
            
            // ===== Sledovat změny kategorií produktu =====
            function updateBlockidsFields() {
                var categories = [];
                
                // Checkboxy kategorií v panelu vpravo
                $('#product_catchecklist input:checked, #product_catchecklist-pop input:checked').each(function() {
                    var label = $(this).closest('label, li').text().trim().toLowerCase();
                    categories.push(label);
                });
                
                // Zjistit jestli je vybraná blockids kategorie
                var isDesky = false, isGripy = false, isMatrace = false;
                
                categories.forEach(function(cat) {
                    if (cat.indexOf('desky') !== -1) isDesky = true;
                    if (cat.indexOf('gripy') !== -1) isGripy = true;
                    if (cat.indexOf('matrace') !== -1) isMatrace = true;
                });
                
                var hasAny = isDesky || isGripy || isMatrace;
                
                // Zobrazit/skrýt sekce
                $('#blockids-no-category').toggle(!hasAny);
                $('#blockids-fields-desky').toggle(isDesky);
                $('#blockids-fields-gripy').toggle(isGripy);
                $('#blockids-fields-matrace').toggle(isMatrace);
                
                // Ceník jen pro public matrace
                if (isMatrace && $('input[name="blockids_personal"]:checked').val() === 'no') {
                    $('#blockids-prices-section').show();
                } else {
                    $('#blockids-prices-section').hide();
                }
            }
            
            // Sledovat změny checkboxů kategorií
            $(document).on('change', '#product_catchecklist input, #product_catchecklist-pop input', function() {
                setTimeout(updateBlockidsFields, 100);
            });
            
            // Počáteční stav (po načtení stránky)
            // Malé zpoždění aby WooCommerce stihlo načíst kategorie
            setTimeout(updateBlockidsFields, 500);
        });
        </script>
        <?php
    }
    
    /**
     * Uložit meta pole produktu
     */
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
        
        // Overlays (gripy) - uložit jako JSON string
        if (isset($_POST['blockids_overlays'])) {
            update_post_meta($post_id, '_blockids_overlays', sanitize_text_field($_POST['blockids_overlays']));
        }
        
        // Ceny dle šířky - sestavit JSON z repeater polí
        if (isset($_POST['blockids_price_min']) && is_array($_POST['blockids_price_min'])) {
            $prices = array();
            $mins = $_POST['blockids_price_min'];
            $maxs = $_POST['blockids_price_max'];
            $vals = $_POST['blockids_price_val'];
            
            for ($i = 0; $i < count($mins); $i++) {
                $min = (int) $mins[$i];
                $max = (int) $maxs[$i];
                $val = (int) $vals[$i];
                
                // Přeskočit prázdné řádky
                if ($max > 0 || $val > 0) {
                    $prices[] = array(
                        'minWidth' => $min,
                        'maxWidth' => $max,
                        'price' => $val,
                    );
                }
            }
            
            if (!empty($prices)) {
                update_post_meta($post_id, '_blockids_prices', json_encode($prices));
            } else {
                delete_post_meta($post_id, '_blockids_prices');
            }
        } else {
            // Pokud nejsou žádné price řádky, smazat
            // (ale jen pokud je to matrace - nemazat u jiných kategorií)
            $terms = wp_get_post_terms($post_id, 'product_cat', array('fields' => 'slugs'));
            if (in_array('matrace', $terms)) {
                delete_post_meta($post_id, '_blockids_prices');
            }
        }
    }
    
    /**
     * Admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Jen na stránce editace produktu
        if (!in_array($hook, array('post.php', 'post-new.php'))) {
            return;
        }
        
        global $post;
        if ($post && $post->post_type === 'product') {
            // Inline styl pro meta box
            wp_add_inline_style('woocommerce_admin_styles', '
                #blockids_product_meta .form-table th { width: 180px; padding: 12px 10px; }
                #blockids_product_meta .form-table td { padding: 12px 10px; }
                #blockids_product_meta .form-table td .description { margin-top: 4px; }
                #blockids-prices-table td { padding: 6px 4px; }
                #blockids-prices-table input { text-align: right; }
                .blockids-price-row:hover { background: #f0f0f1; }
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
        $api_base_url = get_option('blockids_api_base_url', home_url('/wp-json/blockids/v1'));
        $jwt_secret_key = get_option('blockids_jwt_secret_key');
        $jwt_expiration = get_option('blockids_jwt_expiration', 3600);
        $launch_url = add_query_arg('blockids_launch', '1', home_url('/'));
        
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
                            <p class="description">Např. http://localhost:3000 nebo https://configurator.blockids.eu</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="api_base_url">API Base URL</label></th>
                        <td>
                            <input type="url" id="api_base_url" name="api_base_url" value="<?php echo esc_attr($api_base_url); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="jwt_secret_key">JWT Secret Key</label></th>
                        <td><input type="text" id="jwt_secret_key" name="jwt_secret_key" value="<?php echo esc_attr($jwt_secret_key); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="jwt_expiration">JWT Expiration (s)</label></th>
                        <td>
                            <input type="number" id="jwt_expiration" name="jwt_expiration" value="<?php echo esc_attr($jwt_expiration); ?>" class="small-text">
                            <p class="description">Výchozí: 3600 = 1 hodina</p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button('Uložit nastavení', 'primary', 'blockids_settings_submit'); ?>
            </form>
            
            <hr>
            
            <h2>Testování</h2>
            <p>
                <a href="<?php echo esc_url($launch_url); ?>" target="_blank" class="button button-primary">
                    🚀 Spustit konfigurátor
                </a>
            </p>
            
            <hr>
            
            <h2>Vložení na web</h2>
            <p>Shortcode: <code>[blockids_configurator_button]</code></p>
            <p>Nebo přímý odkaz pro Elementor tlačítko: <code><?php echo esc_html($launch_url); ?></code></p>
            
            <hr>
            
            <h2>Nastavení .env konfiguratoru</h2>
            <pre style="background: #f5f5f5; padding: 15px; border: 1px solid #ddd;">
API_BASE_PATH="<?php echo esc_html(home_url('/wp-json/blockids/')); ?>"
API_BASE_VERSION="v1"

NEXT_PUBLIC_URL_REDIRECT_PATH_CS="<?php echo esc_html(home_url('/?blockids_confirm=1')); ?>"
NEXT_PUBLIC_URL_REDIRECT_PATH_EN="<?php echo esc_html(home_url('/?blockids_confirm=1')); ?>"
NEXT_PUBLIC_URL_REDIRECT_PATH_DE="<?php echo esc_html(home_url('/?blockids_confirm=1')); ?>"</pre>
        </div>
        <?php
    }
}

new BLOCKids_Configurator_Settings();