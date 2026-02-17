<?php
/**
 * Admin - Detail návrhu lezecké stěny
 * 
 * VERZE: 2.2.0
 * 
 * Stránka zobrazí:
 * - Informace o zákazníkovi
 * - Vizuální mřížku s rozmístěním desek
 * - Seznam použitých komponent
 * - Rozklad ceny
 * - Stav návrhu
 * 
 * Přístup: admin.php?page=blockids-plan-detail&hash=XXXXX
 */

if (!defined('ABSPATH')) {
    exit;
}

class BLOCKids_Configurator_Plan_Detail {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_submenu_page'));
        
        // Sloupec v seznamu objednávek
        add_filter('manage_edit-shop_order_columns', array($this, 'add_order_column'));
        add_action('manage_shop_order_posts_custom_column', array($this, 'render_order_column'), 10, 2);
        
        // HPOS kompatibilita
        add_filter('manage_woocommerce_page_wc-orders_columns', array($this, 'add_order_column'));
        add_action('manage_woocommerce_page_wc-orders_custom_column', array($this, 'render_order_column_hpos'), 10, 2);
    }
    
    /**
     * Přidat skrytou submenu stránku
     */
    public function add_submenu_page() {
        add_submenu_page(
            null, // Skrytá stránka (bez menu položky)
            'Detail návrhu',
            'Detail návrhu',
            'manage_woocommerce',
            'blockids-plan-detail',
            array($this, 'render_page')
        );
        
        // Seznam všech návrhů pod BLOCKids menu
        add_submenu_page(
            'blockids-configurator',
            'Návrhy stěn',
            '📋 Návrhy stěn',
            'manage_woocommerce',
            'blockids-plans-list',
            array($this, 'render_plans_list')
        );
    }
    
    // =========================================================================
    // DETAIL NÁVRHU
    // =========================================================================
    
    public function render_page() {
        if (!current_user_can('manage_woocommerce')) {
            wp_die('Nemáte oprávnění zobrazit tuto stránku.');
        }
        
        $hash = isset($_GET['hash']) ? sanitize_text_field($_GET['hash']) : '';
        
        if (empty($hash)) {
            echo '<div class="wrap"><h1>Chyba</h1><p>Nebyl zadán kód návrhu.</p></div>';
            return;
        }
        
        // Načíst plán z databáze
        global $wpdb;
        $table = $wpdb->prefix . 'blockids_plans';
        $plan = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE access_hash = %s",
            $hash
        ), ARRAY_A);
        
        if (!$plan) {
            echo '<div class="wrap"><h1>Návrh nenalezen</h1><p>Návrh s kódem <code>' . esc_html($hash) . '</code> nebyl nalezen.</p></div>';
            return;
        }
        
        // Data
        $user = get_user_by('id', $plan['user_id']);
        $workspace = $plan['workspace'] ? json_decode($plan['workspace'], true) : array();
        $plan_data = $plan['plan_data'] ? json_decode($plan['plan_data'], true) : array();
        
        // Orientace a grid rozměry
        $orientation = $plan['orientation'] ?: 'horizontal';
        if ($orientation === 'vertical') {
            $columns = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H');
            $rows = array(1, 2, 3);
        } else {
            $columns = array('A', 'B', 'C', 'D', 'E');
            $rows = array(1, 2, 3, 4);
        }
        
        // Status label
        $statuses = array(
            'draft' => array('label' => 'Rozpracováno', 'color' => '#dba617', 'bg' => '#fef7e7'),
            'confirmed' => array('label' => 'Potvrzeno', 'color' => '#0073aa', 'bg' => '#e7f5fe'),
            'in_cart' => array('label' => 'V košíku', 'color' => '#e65100', 'bg' => '#fff3e0'),
            'ordered' => array('label' => 'Objednáno', 'color' => '#2e7d32', 'bg' => '#e8f5e9'),
            'completed' => array('label' => 'Dokončeno', 'color' => '#1b5e20', 'bg' => '#c8e6c9'),
        );
        $status = $statuses[$plan['status']] ?? array('label' => $plan['status'], 'color' => '#666', 'bg' => '#f0f0f0');
        
        // Spočítat rozklad ceny
        $desk_prices = array();
        $desk_total = 0;
        $desk_types = array('rectangle' => 0, 'triangle' => 0, 'blackboard' => 0);
        
        if (!empty($workspace)) {
            foreach ($workspace as $pos => $cell) {
                $desk_id = null;
                $desk_title = '';
                $desk_price = 0;
                $desk_type = '';
                $desk_image = '';
                
                // Formát: {desk: {id, title, price, type, image}, rotation} nebo {id, rotation}
                if (isset($cell['desk']) && is_array($cell['desk'])) {
                    $desk_id = $cell['desk']['id'] ?? null;
                    $desk_title = $cell['desk']['title'] ?? '';
                    $desk_price = $cell['desk']['price'] ?? 0;
                    $desk_type = $cell['desk']['type'] ?? '';
                    $desk_image = $cell['desk']['image'] ?? '';
                } elseif (isset($cell['id'])) {
                    $desk_id = $cell['id'];
                }
                
                // Doplnit data z WooCommerce pokud chybí
                if ($desk_id && (empty($desk_title) || empty($desk_price))) {
                    $product = wc_get_product($desk_id);
                    if ($product) {
                        $desk_title = $desk_title ?: $product->get_name();
                        $desk_price = $desk_price ?: (float) $product->get_price();
                        $desk_type = $desk_type ?: get_post_meta($desk_id, '_blockids_type', true);
                        $desk_image = $desk_image ?: wp_get_attachment_url($product->get_image_id());
                    }
                }
                
                if ($desk_id) {
                    $desk_total += $desk_price;
                    if (isset($desk_types[$desk_type])) {
                        $desk_types[$desk_type]++;
                    }
                    
                    if (!isset($desk_prices[$desk_id])) {
                        $desk_prices[$desk_id] = array(
                            'title' => $desk_title,
                            'price' => $desk_price,
                            'type' => $desk_type,
                            'count' => 0,
                        );
                    }
                    $desk_prices[$desk_id]['count']++;
                }
            }
        }
        
        // Grip info
        $grip = null;
        $grip_total = 0;
        if ($plan['grip_id']) {
            $grip_product = wc_get_product($plan['grip_id']);
            if ($grip_product) {
                $grip = array(
                    'title' => $grip_product->get_name(),
                    'price' => (float) $grip_product->get_price(),
                    'quantity' => (int) $plan['grip_quantity'],
                    'image' => wp_get_attachment_url($grip_product->get_image_id()),
                );
                $grip_total = $grip['price'] * $grip['quantity'];
            }
        }
        
        // Mattress info
        $mattress = null;
        $mattress_total = 0;
        if ($plan['mattress_id']) {
            $mattress_product = wc_get_product($plan['mattress_id']);
            if ($mattress_product) {
                $mattress = array(
                    'title' => $mattress_product->get_name(),
                    'price' => (float) $mattress_product->get_price(),
                    'quantity' => (int) $plan['mattress_quantity'],
                    'image' => wp_get_attachment_url($mattress_product->get_image_id()),
                );
                $mattress_total = $mattress['price'] * $mattress['quantity'];
            }
        }
        
        $material_price = $desk_total + $grip_total;
        $design_config = $material_price * 0.10;
        
        // Vlastní rozměry příplatek
        $custom_surcharge = 0;
        if ($plan['custom_width'] > 0 && $plan['calculated_width'] > 0) {
            if ($plan['custom_width'] < $plan['calculated_width'] || $plan['custom_height'] < $plan['calculated_height']) {
                $custom_surcharge = $desk_total * 0.10;
            }
        }
        
        $calculated_total = $desk_total + $grip_total + $mattress_total + $design_config + $custom_surcharge;
        $stored_total = (float) $plan['total_price'];
        
        ?>
        <div class="wrap">
            <h1>
                🧗 Návrh: <?php echo esc_html($plan['title'] ?: 'Bez názvu'); ?>
                <span style="display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 13px; font-weight: 600; color: <?php echo $status['color']; ?>; background: <?php echo $status['bg']; ?>; margin-left: 10px; vertical-align: middle;">
                    <?php echo $status['label']; ?>
                </span>
            </h1>
            
            <p>
                <a href="<?php echo admin_url('admin.php?page=blockids-plans-list'); ?>" class="button">← Zpět na seznam návrhů</a>
            </p>
            
            <div style="display: flex; gap: 20px; flex-wrap: wrap; margin-top: 20px;">
                
                <!-- LEVÝ SLOUPEC: Mřížka + komponenty -->
                <div style="flex: 1; min-width: 500px;">
                    
                    <!-- ZÁKAZNÍK -->
                    <div class="postbox" style="padding: 15px;">
                        <h3 style="margin-top:0;">👤 Zákazník</h3>
                        <?php if ($user) : ?>
                        <table class="form-table" style="margin:0;">
                            <tr><th style="width:120px;">Jméno</th><td><strong><?php echo esc_html($user->display_name); ?></strong></td></tr>
                            <tr><th>E-mail</th><td><a href="mailto:<?php echo esc_attr($user->user_email); ?>"><?php echo esc_html($user->user_email); ?></a></td></tr>
                            <tr><th>Telefon</th><td><?php echo esc_html(get_user_meta($user->ID, 'billing_phone', true) ?: '—'); ?></td></tr>
                            <tr><th>Segment</th><td><?php 
                                $seg = get_user_meta($user->ID, 'blockids_segment_id', true);
                                echo $seg == 2 ? '🏢 Veřejný prostor' : '🏠 Domácnost';
                            ?></td></tr>
                        </table>
                        <?php else : ?>
                        <p>Uživatel ID <?php echo (int) $plan['user_id']; ?> nenalezen.</p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- VIZUÁLNÍ MŘÍŽKA -->
                    <div class="postbox" style="padding: 15px;">
                        <h3 style="margin-top:0;">
                            📐 Rozložení stěny
                            <span style="font-weight:normal; font-size: 13px; color: #666;">
                                (<?php echo $orientation === 'horizontal' ? 'horizontální' : 'vertikální'; ?> orientace)
                            </span>
                        </h3>
                        
                        <div style="display: flex; gap: 15px; margin-bottom: 15px; flex-wrap: wrap;">
                            <div style="padding: 8px 15px; background: #f0f0f1; border-radius: 4px;">
                                <strong>Vypočítané:</strong> <?php echo (int) $plan['calculated_width']; ?> × <?php echo (int) $plan['calculated_height']; ?> cm
                            </div>
                            <?php if ($plan['custom_width'] > 0 && ($plan['custom_width'] != $plan['calculated_width'] || $plan['custom_height'] != $plan['calculated_height'])) : ?>
                            <div style="padding: 8px 15px; background: #fef7e7; border-radius: 4px;">
                                <strong>Vlastní rozměry:</strong> <?php echo (int) $plan['custom_width']; ?> × <?php echo (int) $plan['custom_height']; ?> cm
                            </div>
                            <?php endif; ?>
                            <div style="padding: 8px 15px; background: #f0f0f1; border-radius: 4px;">
                                <strong>Umístění:</strong> <?php echo $plan['location'] === 'outdoor' ? '🌳 Exteriér' : '🏠 Interiér'; ?>
                            </div>
                        </div>
                        
                        <!-- Grid tabulka -->
                        <div style="overflow-x: auto;">
                            <table class="widefat" style="width: auto; border-collapse: collapse; text-align: center;">
                                <thead>
                                    <tr>
                                        <th style="width: 40px; background: #f9f9f9;"></th>
                                        <?php foreach ($columns as $col) : ?>
                                        <th style="width: 100px; background: #f9f9f9; font-weight: 600;"><?php echo $col; ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($rows as $row) : ?>
                                    <tr>
                                        <td style="background: #f9f9f9; font-weight: 600;"><?php echo $row; ?></td>
                                        <?php foreach ($columns as $col) : 
                                            $pos = $col . $row;
                                            $cell = isset($workspace[$pos]) ? $workspace[$pos] : null;
                                            $has_desk = false;
                                            $cell_title = '';
                                            $cell_type = '';
                                            $cell_image = '';
                                            $cell_rotation = 0;
                                            
                                            if ($cell) {
                                                if (isset($cell['desk']) && is_array($cell['desk'])) {
                                                    $has_desk = !empty($cell['desk']['id']);
                                                    $cell_title = $cell['desk']['title'] ?? '';
                                                    $cell_type = $cell['desk']['type'] ?? '';
                                                    $cell_image = $cell['desk']['image'] ?? '';
                                                } elseif (isset($cell['id']) && $cell['id']) {
                                                    $has_desk = true;
                                                    $p = wc_get_product($cell['id']);
                                                    if ($p) {
                                                        $cell_title = $p->get_name();
                                                        $cell_type = get_post_meta($cell['id'], '_blockids_type', true);
                                                        $cell_image = wp_get_attachment_url($p->get_image_id());
                                                    }
                                                }
                                                $cell_rotation = $cell['rotation'] ?? 0;
                                            }
                                            
                                            // Barvy pro typy
                                            $type_colors = array(
                                                'rectangle' => '#e3f2fd',
                                                'triangle' => '#fff3e0',
                                                'blackboard' => '#f3e5f5',
                                            );
                                            $type_icons = array(
                                                'rectangle' => '⬜',
                                                'triangle' => '🔺',
                                                'blackboard' => '📝',
                                            );
                                            $bg = $has_desk ? ($type_colors[$cell_type] ?? '#e8f5e9') : '#fafafa';
                                            $border = $has_desk ? '2px solid #90caf9' : '1px dashed #ddd';
                                        ?>
                                        <td style="width: 100px; height: 80px; background: <?php echo $bg; ?>; border: <?php echo $border; ?>; vertical-align: middle; position: relative;">
                                            <?php if ($has_desk) : ?>
                                                <?php if ($cell_image) : ?>
                                                <img src="<?php echo esc_url($cell_image); ?>" style="max-width: 60px; max-height: 45px; display: block; margin: 0 auto 3px; <?php echo $cell_rotation ? 'transform: rotate(' . (int)$cell_rotation . 'deg);' : ''; ?>">
                                                <?php else : ?>
                                                <span style="font-size: 24px;"><?php echo $type_icons[$cell_type] ?? '⬜'; ?></span><br>
                                                <?php endif; ?>
                                                <span style="font-size: 10px; color: #555; display: block; line-height: 1.2;">
                                                    <?php echo esc_html(mb_strimwidth($cell_title, 0, 20, '…')); ?>
                                                </span>
                                                <?php if ($cell_rotation) : ?>
                                                <span style="position: absolute; top: 2px; right: 4px; font-size: 9px; color: #999;">↻<?php echo $cell_rotation; ?>°</span>
                                                <?php endif; ?>
                                            <?php else : ?>
                                                <span style="color: #ccc; font-size: 11px;"><?php echo $pos; ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <?php endforeach; ?>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Legenda -->
                        <div style="margin-top: 10px; display: flex; gap: 15px; flex-wrap: wrap;">
                            <span style="padding: 3px 10px; background: #e3f2fd; border-radius: 3px; font-size: 12px;">⬜ Obdélník</span>
                            <span style="padding: 3px 10px; background: #fff3e0; border-radius: 3px; font-size: 12px;">🔺 Trojúhelník</span>
                            <span style="padding: 3px 10px; background: #f3e5f5; border-radius: 3px; font-size: 12px;">📝 Tabule</span>
                            <span style="padding: 3px 10px; background: #fafafa; border: 1px dashed #ddd; border-radius: 3px; font-size: 12px;">Prázdné pole</span>
                        </div>
                    </div>
                </div>
                
                <!-- PRAVÝ SLOUPEC: Komponenty + cena -->
                <div style="width: 380px; min-width: 320px;">
                    
                    <!-- POUŽITÉ DESKY -->
                    <div class="postbox" style="padding: 15px;">
                        <h3 style="margin-top:0;">🪵 Desky (<?php echo array_sum(array_column($desk_prices, 'count')); ?> ks)</h3>
                        <table class="widefat striped" style="margin: 0;">
                            <thead>
                                <tr>
                                    <th>Deska</th>
                                    <th style="text-align:right;">Ks</th>
                                    <th style="text-align:right;">Cena/ks</th>
                                    <th style="text-align:right;">Celkem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($desk_prices as $d) : ?>
                                <tr>
                                    <td>
                                        <?php echo esc_html($d['title']); ?>
                                        <small style="color:#999;">(<?php echo $d['type']; ?>)</small>
                                    </td>
                                    <td style="text-align:right;"><?php echo $d['count']; ?>×</td>
                                    <td style="text-align:right;"><?php echo number_format($d['price'], 0, ',', ' '); ?> Kč</td>
                                    <td style="text-align:right;"><strong><?php echo number_format($d['price'] * $d['count'], 0, ',', ' '); ?> Kč</strong></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($desk_prices)) : ?>
                                <tr><td colspan="4" style="color:#999;">Žádné desky</td></tr>
                                <?php endif; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3"><strong>Desky celkem</strong></td>
                                    <td style="text-align:right;"><strong><?php echo number_format($desk_total, 0, ',', ' '); ?> Kč</strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <!-- CHYTY -->
                    <div class="postbox" style="padding: 15px;">
                        <h3 style="margin-top:0;">🤏 Chyty</h3>
                        <?php if ($grip) : ?>
                        <table class="widefat" style="margin:0;">
                            <tr>
                                <td>
                                    <?php if ($grip['image']) : ?>
                                    <img src="<?php echo esc_url($grip['image']); ?>" style="max-width:40px; vertical-align:middle; margin-right:8px;">
                                    <?php endif; ?>
                                    <?php echo esc_html($grip['title']); ?>
                                </td>
                                <td style="text-align:right;"><?php echo $grip['quantity']; ?>× <?php echo number_format($grip['price'], 0, ',', ' '); ?> Kč</td>
                                <td style="text-align:right;"><strong><?php echo number_format($grip_total, 0, ',', ' '); ?> Kč</strong></td>
                            </tr>
                        </table>
                        <?php else : ?>
                        <p style="color:#999; margin:0;">Bez chytů</p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- MATRACE -->
                    <div class="postbox" style="padding: 15px;">
                        <h3 style="margin-top:0;">🟦 Matrace</h3>
                        <?php if ($mattress) : ?>
                        <table class="widefat" style="margin:0;">
                            <tr>
                                <td>
                                    <?php if ($mattress['image']) : ?>
                                    <img src="<?php echo esc_url($mattress['image']); ?>" style="max-width:40px; vertical-align:middle; margin-right:8px;">
                                    <?php endif; ?>
                                    <?php echo esc_html($mattress['title']); ?>
                                </td>
                                <td style="text-align:right;"><?php echo $mattress['quantity']; ?>× <?php echo number_format($mattress['price'], 0, ',', ' '); ?> Kč</td>
                                <td style="text-align:right;"><strong><?php echo number_format($mattress_total, 0, ',', ' '); ?> Kč</strong></td>
                            </tr>
                        </table>
                        <?php else : ?>
                        <p style="color:#999; margin:0;">Bez matrace</p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- CENOVÝ ROZKLAD -->
                    <div class="postbox" style="padding: 15px; background: #f9f9f9;">
                        <h3 style="margin-top:0;">💰 Celková cena</h3>
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr>
                                <td style="padding: 5px 0;">Desky</td>
                                <td style="text-align: right; padding: 5px 0;"><?php echo number_format($desk_total, 0, ',', ' '); ?> Kč</td>
                            </tr>
                            <tr>
                                <td style="padding: 5px 0;">Chyty</td>
                                <td style="text-align: right; padding: 5px 0;"><?php echo number_format($grip_total, 0, ',', ' '); ?> Kč</td>
                            </tr>
                            <tr>
                                <td style="padding: 5px 0;">Matrace</td>
                                <td style="text-align: right; padding: 5px 0;"><?php echo number_format($mattress_total, 0, ',', ' '); ?> Kč</td>
                            </tr>
                            <tr>
                                <td style="padding: 5px 0;">Design konfigurace (10%)</td>
                                <td style="text-align: right; padding: 5px 0;"><?php echo number_format($design_config, 0, ',', ' '); ?> Kč</td>
                            </tr>
                            <?php if ($custom_surcharge > 0) : ?>
                            <tr>
                                <td style="padding: 5px 0;">Příplatek za vlastní rozměry (10%)</td>
                                <td style="text-align: right; padding: 5px 0;"><?php echo number_format($custom_surcharge, 0, ',', ' '); ?> Kč</td>
                            </tr>
                            <?php endif; ?>
                            <tr style="border-top: 2px solid #333;">
                                <td style="padding: 10px 0;"><strong style="font-size: 16px;">CELKEM</strong></td>
                                <td style="text-align: right; padding: 10px 0;">
                                    <strong style="font-size: 18px; color: #2e7d32;">
                                        <?php echo number_format($stored_total ?: $calculated_total, 0, ',', ' '); ?> Kč
                                    </strong>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- TECHNICKÉ INFO -->
                    <div class="postbox" style="padding: 15px;">
                        <h3 style="margin-top:0;">⚙️ Technické údaje</h3>
                        <table class="form-table" style="margin:0;">
                            <tr><th style="width:120px; padding:5px 0;">Kód návrhu</th><td style="padding:5px 0;"><code><?php echo esc_html($hash); ?></code></td></tr>
                            <tr><th style="padding:5px 0;">ID v databázi</th><td style="padding:5px 0;"><?php echo (int) $plan['id']; ?></td></tr>
                            <tr><th style="padding:5px 0;">Vytvořeno</th><td style="padding:5px 0;"><?php echo esc_html($plan['created_at']); ?></td></tr>
                            <tr><th style="padding:5px 0;">Poslední úprava</th><td style="padding:5px 0;"><?php echo esc_html($plan['updated_at']); ?></td></tr>
                        </table>
                    </div>
                    
                </div>
            </div>
        </div>
        <?php
    }
    
    // =========================================================================
    // SEZNAM NÁVRHŮ
    // =========================================================================
    
    public function render_plans_list() {
        if (!current_user_can('manage_woocommerce')) {
            wp_die('Nemáte oprávnění.');
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'blockids_plans';
        
        // Filtr podle stavu
        $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        
        $where = '';
        if ($status_filter) {
            $where = $wpdb->prepare(" WHERE status = %s", $status_filter);
        }
        
        $plans = $wpdb->get_results(
            "SELECT p.*, u.display_name, u.user_email 
             FROM $table p 
             LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID 
             $where 
             ORDER BY p.updated_at DESC 
             LIMIT 100",
            ARRAY_A
        );
        
        $statuses = array(
            '' => 'Vše',
            'draft' => '✏️ Rozpracované',
            'confirmed' => '✅ Potvrzené',
            'in_cart' => '🛒 V košíku',
            'ordered' => '📦 Objednané',
            'completed' => '✔️ Dokončené',
        );
        
        $status_colors = array(
            'draft' => '#dba617',
            'confirmed' => '#0073aa',
            'in_cart' => '#e65100',
            'ordered' => '#2e7d32',
            'completed' => '#1b5e20',
        );
        
        ?>
        <div class="wrap">
            <h1>📋 Návrhy lezeckých stěn</h1>
            
            <!-- Filtr -->
            <div style="margin: 15px 0;">
                <?php foreach ($statuses as $key => $label) : ?>
                <a href="<?php echo admin_url('admin.php?page=blockids-plans-list' . ($key ? '&status=' . $key : '')); ?>" 
                   class="button <?php echo $status_filter === $key ? 'button-primary' : ''; ?>"
                   style="margin-right: 5px;">
                    <?php echo $label; ?>
                </a>
                <?php endforeach; ?>
            </div>
            
            <table class="widefat striped" style="margin-top: 10px;">
                <thead>
                    <tr>
                        <th style="width:40px;">ID</th>
                        <th>Název</th>
                        <th>Zákazník</th>
                        <th>Umístění</th>
                        <th>Rozměry</th>
                        <th style="text-align:right;">Cena</th>
                        <th>Stav</th>
                        <th>Poslední úprava</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($plans)) : ?>
                    <tr><td colspan="9" style="text-align:center; color:#999; padding: 20px;">Žádné návrhy<?php echo $status_filter ? ' v tomto stavu' : ''; ?>.</td></tr>
                    <?php endif; ?>
                    
                    <?php foreach ($plans as $p) : ?>
                    <tr>
                        <td><?php echo (int) $p['id']; ?></td>
                        <td><strong><?php echo esc_html($p['title'] ?: 'Bez názvu'); ?></strong></td>
                        <td>
                            <?php echo esc_html($p['display_name'] ?: 'ID ' . $p['user_id']); ?>
                            <?php if ($p['user_email']) : ?>
                            <br><small style="color:#999;"><?php echo esc_html($p['user_email']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $p['location'] === 'outdoor' ? '🌳 Ext.' : '🏠 Int.'; ?></td>
                        <td><?php echo (int) $p['calculated_width']; ?> × <?php echo (int) $p['calculated_height']; ?> cm</td>
                        <td style="text-align:right;">
                            <?php echo $p['total_price'] ? number_format((float) $p['total_price'], 0, ',', ' ') . ' Kč' : '—'; ?>
                        </td>
                        <td>
                            <span style="display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 12px; color: <?php echo $status_colors[$p['status']] ?? '#666'; ?>; background: <?php echo ($status_colors[$p['status']] ?? '#666') . '15'; ?>; font-weight: 600;">
                                <?php 
                                    $labels = array('draft' => 'Rozpracováno', 'confirmed' => 'Potvrzeno', 'in_cart' => 'V košíku', 'ordered' => 'Objednáno', 'completed' => 'Dokončeno');
                                    echo $labels[$p['status']] ?? $p['status'];
                                ?>
                            </span>
                        </td>
                        <td><?php echo esc_html(date('d.m.Y H:i', strtotime($p['updated_at']))); ?></td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=blockids-plan-detail&hash=' . $p['access_hash']); ?>" class="button button-small">
                                Zobrazit
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    // =========================================================================
    // SLOUPEC V OBJEDNÁVKÁCH
    // =========================================================================
    
    public function add_order_column($columns) {
        $new_columns = array();
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'order_total') {
                $new_columns['blockids_plan'] = '🧗 Stěna';
            }
        }
        return $new_columns;
    }
    
    /**
     * Klasické objednávky (post type)
     */
    public function render_order_column($column, $post_id) {
        if ($column !== 'blockids_plan') return;
        
        $order = wc_get_order($post_id);
        if (!$order) return;
        
        $this->render_order_column_content($order);
    }
    
    /**
     * HPOS objednávky
     */
    public function render_order_column_hpos($column, $order) {
        if ($column !== 'blockids_plan') return;
        
        if (is_numeric($order)) {
            $order = wc_get_order($order);
        }
        if (!$order) return;
        
        $this->render_order_column_content($order);
    }
    
    private function render_order_column_content($order) {
        foreach ($order->get_items() as $item) {
            $plan = $item->get_meta('_blockids_plan');
            if ($plan && isset($plan['access_hash'])) {
                $url = admin_url('admin.php?page=blockids-plan-detail&hash=' . $plan['access_hash']);
                echo '<a href="' . esc_url($url) . '" class="button button-small" title="Zobrazit detail návrhu">📐 Detail</a>';
                return;
            }
        }
        echo '<span style="color:#ccc;">—</span>';
    }
}

new BLOCKids_Configurator_Plan_Detail();