<?php

/**
 * Automatický scanner SVG overlays pro gripy
 * 
 * Skenuje složku s SVG soubory a parsuje jejich názvy na overlay objekty
 * pro konfigurátor. Nevyžaduje žádné ruční zadávání overlays v adminu.
 * 
 * KONVENCE NÁZVŮ SOUBORŮ:
 *   {panel_prefix}-{barva}[-180][-stupy]-{číslo}.svg
 * 
 * PANEL PREFIXES:
 *   h-deska  → rectangle     + horizontal
 *   v-deska  → rectangle     + vertical
 *   htl      → triangle_left + horizontal
 *   htr      → triangle_top  + horizontal
 *   vtl      → triangle_left + vertical
 *   vtr      → triangle_top  + vertical
 * 
 * MODIFIKÁTORY:
 *   -180     → rotation: 180  (bez toho = rotation: 0)
 *   -stupy   → inputs: true   (řada 1 konfiguratoru)
 * 
 * PŘÍKLADY:
 *   h-deska-blue-01.svg          → rectangle, horizontal, rotation: 0,   inputs: false
 *   h-deska-blue-stupy-01.svg    → rectangle, horizontal, rotation: 0,   inputs: true
 *   htl-blue-180-stupy-03.svg    → triangle_left, horizontal, rotation: 180, inputs: true
 *   vtr-mint-purple-180-02.svg   → triangle_top,  vertical,   rotation: 180, inputs: false
 * 
 * VÝSTUP (overlay objekt pro konfigurátor):
 *   { id, type, orientation, rotation, inputs, image }
 *   kde image = data:image/svg+xml;base64,... (kvůli Next.js omezením)
 * 
 * CACHING:
 *   Výsledky jsou cachovány v WordPress transients (1 hodina).
 *   Cache se automaticky promaže při uploadu nového souboru přes FTP
 *   nebo manuálně tlačítkem v adminu.
 * 
 * VERZE: 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class BLOCKids_Grip_Scanner
{

    /**
     * Název option kde je uložena cesta ke složce s grips SVG
     */
    const OPTION_FOLDER = 'blockids_grips_folder';

    /**
     * Prefix pro transient cache
     */
    const CACHE_PREFIX = 'blockids_grips_';

    /**
     * Doba platnosti cache (v sekundách)
     */
    const CACHE_TTL = HOUR_IN_SECONDS;

    /**
     * Mapování prefixu souboru → typ desky + orientace
     * (musí odpovídat typům v konfigurátoru)
     */
    private static $panel_map = array(
        'h-deska' => array('type' => 'rectangle',      'orientation' => 'horizontal'),
        'v-deska' => array('type' => 'rectangle',      'orientation' => 'vertical'),
        'htl'     => array('type' => 'triangle_left',  'orientation' => 'horizontal'),
        'htr'     => array('type' => 'triangle_top',   'orientation' => 'horizontal'),
        'vtl'     => array('type' => 'triangle_left',  'orientation' => 'vertical'),
        'vtr'     => array('type' => 'triangle_top',   'orientation' => 'vertical'),
    );

    // =========================================================================
    // VEŘEJNÉ API
    // =========================================================================

    /**
     * Vrátí pole overlay objektů pro danou barvu.
     * 
     * Používá se v class-api.php místo čtení _blockids_overlays z product meta.
     * Výsledek je cachován v transient.
     * 
     * @param  string $color  Barva gripu (např. "blue", "orange", "mint-purple")
     * @return array          Pole overlay objektů připravených pro konfigurátor
     */
    public static function get_overlays_for_color($color)
    {
        if (empty($color)) {
            return array();
        }

        $color = sanitize_text_field($color);

        // Zkusit cache
        $cache_key = self::CACHE_PREFIX . md5($color);
        $cached    = get_transient($cache_key);
        if ($cached !== false) {
            return $cached;
        }

        // Skenovat složku
        $overlays = self::scan_folder($color);

        // Uložit do cache
        set_transient($cache_key, $overlays, self::CACHE_TTL);

        return $overlays;
    }

    /**
     * Vrátí absolutní cestu ke složce s SVG soubory.
     * Nastavuje se v BLOCKids admin → záložka Gripy.
     * 
     * @return string  Absolutní cesta (bez trailing slash)
     */
    public static function get_grips_folder()
    {
        $folder = get_option(self::OPTION_FOLDER, '');

        // Výchozí: wp-content/uploads/blockids-grips
        if (empty($folder)) {
            $upload_dir = wp_upload_dir();
            $folder     = $upload_dir['basedir'] . '/blockids-grips';
        }

        return rtrim($folder, '/\\');
    }

    /**
     * Vrátí URL složky s SVG soubory (pro zobrazení v adminu).
     * 
     * @return string  URL složky
     */
    public static function get_grips_folder_url()
    {
        $folder     = self::get_grips_folder();
        $upload_dir = wp_upload_dir();

        return str_replace(
            $upload_dir['basedir'],
            $upload_dir['baseurl'],
            $folder
        );
    }

    /**
     * Promaže cache pro danou barvu (nebo všechny barvy).
     * 
     * @param string|null $color  Barva k promázání, null = promáže vše
     */
    public static function clear_cache($color = null)
    {
        if ($color !== null) {
            delete_transient(self::CACHE_PREFIX . md5(sanitize_text_field($color)));
        } else {
            // Promázat cache pro všechny barvy - najdeme všechny transients s prefixem
            global $wpdb;
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                    '_transient_' . self::CACHE_PREFIX . '%'
                )
            );
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                    '_transient_timeout_' . self::CACHE_PREFIX . '%'
                )
            );
        }
    }

    /**
     * Vrátí seznam barev nalezených ve složce (podle názvů souborů).
     * Užitečné pro admin dropdown výběru barvy.
     * 
     * @return string[]  Pole unikátních barev (např. ["blue", "orange", "mint-purple"])
     */
    public static function get_available_colors()
    {
        $folder = self::get_grips_folder();
        if (!is_dir($folder)) {
            return array();
        }

        $files  = glob($folder . '/*.svg');
        $colors = array();

        if ($files) {
            foreach ($files as $file) {
                $parsed = self::parse_filename(basename($file, '.svg'));
                if ($parsed && !in_array($parsed['color'], $colors)) {
                    $colors[] = $parsed['color'];
                }
            }
        }

        sort($colors);
        return $colors;
    }

    /**
     * Diagnostika - vrátí seznam všech rozpoznaných souborů ve složce.
     * Používá se na admin stránce pro ladění.
     * 
     * @return array[]  Pole parsovaných souborů (každý má klíče file, parsed/error)
     */
    public static function get_diagnostics()
    {
        $folder = self::get_grips_folder();
        $result = array();

        if (!is_dir($folder)) {
            return array('error' => 'Složka neexistuje: ' . $folder);
        }

        $files = glob($folder . '/*.svg') ?: array();
        foreach ($files as $file) {
            $basename = basename($file, '.svg');
            $parsed   = self::parse_filename($basename);
            $result[] = array(
                'file'   => basename($file),
                'parsed' => $parsed ?: null,
                'error'  => $parsed ? null : 'Neparsovatelný název souboru',
            );
        }

        return $result;
    }

    // =========================================================================
    // INTERNÍ LOGIKA
    // =========================================================================

    /**
     * Skenuje složku, parsuje soubory a vrátí overlays pro danou barvu.
     * 
     * @param  string $color  Barva gripu
     * @return array          Pole overlay objektů
     */
    private static function scan_folder($color)
    {
        $folder = self::get_grips_folder();

        if (!is_dir($folder)) {
            return array();
        }

        $files    = glob($folder . '/*.svg') ?: array();
        $overlays = array();

        foreach ($files as $file_path) {
            $filename = basename($file_path, '.svg');
            $parsed   = self::parse_filename($filename);

            // Přeskočit neparsovatelné nebo jiné barvy
            if (!$parsed || $parsed['color'] !== $color) {
                continue;
            }

            // Převést SVG na base64 data URI
            // (Next.js <Image> blokuje SVG z externích URL)
            $image_data_uri = self::svg_file_to_data_uri($file_path);
            if (empty($image_data_uri)) {
                continue; // Soubor nejde načíst → přeskočit
            }

            $overlays[] = array(
                'id'          => 'auto_' . $filename,
                'type'        => $parsed['type'],
                'orientation' => $parsed['orientation'],
                'rotation'    => $parsed['rotation'],
                'inputs'      => $parsed['inputs'],
                'image'       => $image_data_uri,
                // Interní metadata pro ladění (konfigurátor ignoruje neznámé klíče)
                '_source'     => $filename,
                '_number'     => $parsed['number'],
            );
        }

        return $overlays;
    }

    /**
     * Parsuje název souboru SVG na metadata overlay.
     * 
     * Regex vzor: {prefix}-{barva}[-180][-stupy]-{číslo}
     * 
     * @param  string      $filename  Název souboru BEZ přípony
     * @return array|null             Pole metadat nebo null pokud neparsovatelný
     */
    public static function parse_filename($filename)
    {
        // Sestavit alternativy prefixů seřazené od nejdelšího (aby "h-deska" matchoval dřív než "h")
        $prefixes        = array_keys(self::$panel_map);
        usort($prefixes, fn($a, $b) => strlen($b) - strlen($a));
        $prefixes_regex  = implode('|', array_map('preg_quote', $prefixes));

        // Vzor:
        //   ^({prefix})-   → panel prefix
        //   (.+?)           → barva (non-greedy, může obsahovat pomlčky jako "mint-purple")
        //   (?:-(180))?    → volitelně -180
        //   (?:-(stupy))?  → volitelně -stupy
        //   -(\d+)$         → číslo varianty
        $pattern = '/^(' . $prefixes_regex . ')-(.+?)(?:-(180))?(?:-(stupy))?-(\d+)$/';

        if (!preg_match($pattern, $filename, $m)) {
            return null;
        }

        $prefix = $m[1];
        $color  = $m[2];
        $rot    = $m[3]; // "180" nebo ""
        $stupy  = $m[4]; // "stupy" nebo ""
        $number = (int) $m[5];

        if (!isset(self::$panel_map[$prefix])) {
            return null;
        }

        return array(
            'prefix'      => $prefix,
            'color'       => $color,
            'type'        => self::$panel_map[$prefix]['type'],
            'orientation' => self::$panel_map[$prefix]['orientation'],
            'rotation'    => $rot === '180' ? 180 : 0,
            'inputs'      => $stupy === 'stupy',
            'number'      => $number,
        );
    }

    /**
     * Načte SVG soubor z disku a převede na base64 data URI.
     * 
     * Stejná logika jako svg_to_data_uri() v class-api.php,
     * ale pracuje přímo s cestou (ne URL).
     * 
     * @param  string $file_path  Absolutní cesta k SVG souboru
     * @return string             data:image/svg+xml;base64,... nebo ""
     */
    private static function svg_file_to_data_uri($file_path)
    {
        if (!file_exists($file_path) || !is_readable($file_path)) {
            return '';
        }

        // Limit 512 KB
        if (filesize($file_path) > 524288) {
            return '';
        }

        $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        if (!in_array($extension, array('svg', 'svgz'))) {
            return '';
        }

        $content = @file_get_contents($file_path);
        if ($content === false) {
            return '';
        }

        return 'data:image/svg+xml;base64,' . base64_encode($content);
    }
}