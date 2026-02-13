# BLOCKids Konfigurátor Integration - WordPress Plugin

**Verze:** 1.0.0  
**Autor:** Aleš  
**Datum:** 10. února 2026

---

## 📦 CO TENTO PLUGIN DĚLÁ

Propojuje konfigurátor lezeckých stěn BLOCKids (https://configurator.blockids.eu) s WooCommerce eshopem.

**Hlavní funkce:**
- ✅ REST API endpointy pro konfigurátor
- ✅ JWT autentizace uživatelů
- ✅ Ukládání návrhů do databáze
- ✅ Automatické přidání návrhu do košíku WooCommerce
- ✅ Výpočet ceny podle pravidel BLOCKids
- ✅ Zobrazení konfigurace v objednávce

---

## 🚀 INSTALACE

### 1. Nahraj plugin na server

**Lokálně:**
1. Zkopíruj celou složku `blockids-configurator/` do `wp-content/plugins/`
2. Zabal do ZIPu
3. Nahraj na server přes FTP nebo admin panel WP

**Nebo přímo na serveru:**
1. Přes FTP nahraj složku do `/wp-content/plugins/`

### 2. Aktivuj plugin

1. Jdi do WordPress Admin → Pluginy
2. Najdi "BLOCKids Konfigurátor Integration"
3. Klikni na "Aktivovat"

### 3. Vytvoř kategorie produktů

Jdi do WooCommerce → Produkty → Kategorie a vytvoř:
- **gripy** (slug: `gripy`)
- **matrace** (slug: `matrace`)
- **desky** (slug: `desky`)

### 4. Přidej produkty

Vytvoř WooCommerce produkty a přiřaď jim správné kategorie:
- Lezecké chyty → kategorie "gripy"
- Dopadové matrace → kategorie "matrace"
- Panely/desky → kategorie "desky"

**Důležité:**
- Každý produkt musí mít nastavenou cenu (s DPH)
- Doporučuji přidat obrázky

---

## ⚙️ NASTAVENÍ

### 1. Základní nastavení

Jdi do WordPress Admin → **BLOCKids** (v menu)

**Nastavení:**
- **URL Konfiguratoru:** `https://configurator.blockids.eu`
- **API Base URL:** Automaticky `https://tvoje-domena.cz/wp-json/blockids/v1`
- **JWT Secret Key:** Automaticky vygenerovaný (nech tam)
- **JWT Token Expiration:** `3600` (1 hodina)

### 2. Konfigurátor - úprava .env

Musíš upravit `.env` soubor konfiguratoru:

```bash
API_BASE_PATH="https://tvoje-domena.cz/wp-json/blockids/"
API_BASE_VERSION="v1"

SESSION_SEAL_PASSWORD="[ponech původní nebo vygeneruj nový]"

NEXT_PUBLIC_URL_REDIRECT_PATH_CS="https://tvoje-domena.cz/cs/kosik"
NEXT_PUBLIC_URL_REDIRECT_PATH_EN="https://tvoje-domena.cz/en/cart"
NEXT_PUBLIC_URL_REDIRECT_PATH_DE="https://tvoje-domena.cz/de/warenkorb"
```

**POZOR:** Změň `tvoje-domena.cz` na skutečnou URL eshopu!

---

## 🧪 TESTOVÁNÍ

### 1. Test JWT tokenu

V admin panelu (BLOCKids → Nastavení) najdeš:
- **Test JWT Token** - zkopíruj si ho
- **Link do konfiguratoru** - klikni pro otevření konfiguratoru s tokenem

### 2. Test API endpointů

Otevři v prohlížeči:
```
https://tvoje-domena.cz/wp-json/blockids/v1/grips/cs
https://tvoje-domena.cz/wp-json/blockids/v1/mattresses/cs
https://tvoje-domena.cz/wp-json/blockids/v1/desks/cs
```

Měl by se zobrazit JSON seznam produktů.

### 3. Test kompletního flow

1. Přihlaš se jako zákazník v eshopu
2. Otevři konfigurátor (přes link v admin panelu)
3. Nakonfiguruj lezeckou stěnu
4. Klikni "Přidat do košíku"
5. Měl by se přesměrovat zpět na eshop
6. V košíku by měl být produkt "Vlastní lezecká stěna" s detaily

---

## 📊 JAK TO FUNGUJE

### Flow:

```
1. PŘIHLÁŠENÍ
   Zákazník se přihlásí v eshopu
   ↓
   WordPress vygeneruje JWT token
   
2. KONFIGURÁTOR
   Zákazník klikne "Nakonfigurovat stěnu"
   ↓
   Přesměruje se na: configurator.blockids.eu/cs/sso?token=XXX
   ↓
   Konfigurátor zavolá: GET /wp-json/blockids/v1/customers/me/{token}
   ↓
   WordPress validuje token a vrátí user data
   ↓
   Zákazník nakonfiguruje stěnu
   
3. PŘIDÁNÍ DO KOŠÍKU
   Zákazník klikne "Přidat do košíku"
   ↓
   Konfigurátor zavolá: POST /wp-json/blockids/v1/plans/confirm/cs/{token}/{hash}
   ↓
   WordPress uloží návrh s status "confirmed"
   ↓
   Přesměruje zpět na: blockids.eu/cs/kosik?plan=abc123
   
4. WORDPRESS ZPRACOVÁNÍ
   WordPress zachytí ?plan=abc123
   ↓
   Stáhne detail plánu z databáze
   ↓
   Vytvoří WooCommerce produkt s návrhem
   ↓
   Přidá do košíku
   
5. OBJEDNÁVKA
   Zákazník dokončí objednávku
   ↓
   Konfigurace se uloží do order meta
   ↓
   Zobrazí se v detailech objednávky
```

---

## 🗄️ DATABÁZOVÁ TABULKA

Plugin vytváří tabulku `wp_blockids_plans`:

```sql
id                 - ID plánu
user_id            - ID uživatele
access_hash        - Unikátní hash (pro URL)
title              - Název návrhu
status             - draft / confirmed
location           - indoor / outdoor
orientation        - horizontal / vertical
calculated_width   - Vypočítaná šířka
calculated_height  - Vypočítaná výška
custom_width       - Vlastní šířka
custom_height      - Vlastní výška
grip_id            - ID produktu (chyt)
grip_quantity      - Počet chytů
mattress_id        - ID produktu (matrace)
mattress_quantity  - Počet matrací
workspace          - JSON (grid A1-F2)
plan_data          - JSON (kompletní data)
total_price        - Celková cena
created_at         - Datum vytvoření
updated_at         - Datum úpravy
```

---

## 💰 VÝPOČET CENY

Plugin počítá cenu podle vzorce od vývojářů BLOCKids:

```
CELKOVÁ CENA = desky + gripy + matrace + design config + custom rozměry

kde:
- desky = součet cen všech desek ve workspace
- gripy = cena gripu × počet
- matrace = cena matrace × počet
- design config = (desky + gripy) × 0.10 (vždy 10%)
- custom rozměry = desky × 0.10 (jen pokud custom < calculated)
```

Výpočet se provádí automaticky při uložení plánu v `includes/class-plans.php` → `calculate_price()`.

---

## 🔧 CO POTŘEBUJEŠ JEŠTĚ UDĚLAT

### 1. **Tlačítko "Nakonfigurovat stěnu" na webu**

Do šablony nebo page builderu přidej odkaz:

```php
<?php
$user_id = get_current_user_id();
if ($user_id) {
    $token = BLOCKids_Configurator_Auth::generate_token($user_id);
    $locale = substr(get_locale(), 0, 2);
    if (!in_array($locale, array('cs', 'en', 'de'))) {
        $locale = 'cs';
    }
    $configurator_url = get_option('blockids_configurator_url', 'https://configurator.blockids.eu');
    $url = $configurator_url . '/' . $locale . '/sso?token=' . $token;
    
    echo '<a href="' . esc_url($url) . '" class="button">';
    echo __('Nakonfigurovat lezeckou stěnu', 'blockids-configurator');
    echo '</a>';
} else {
    echo '<a href="' . wp_login_url() . '" class="button">';
    echo __('Přihlásit se pro konfiguraci', 'blockids-configurator');
    echo '</a>';
}
?>
```

### 2. **Nasazení konfiguratoru**

Pokud chceš hostovat konfigurátor sám:
1. Na serveru musíš mít Node.js (ověř: spusť `check-nodejs.bat` lokálně)
2. Nahraj složku `blockids.eu_configurator-development/` na server
3. Nastav `.env` soubor (viz sekce Nastavení výše)
4. Spusť:
   ```bash
   npm install
   npm run build
   npm start
   ```

**Nebo:**
Použij stávající konfigurátor na `https://configurator.blockids.eu/` a jen uprav jejich `.env`.

### 3. **Kontakt na "p. Kukuru"**

Musíš kontaktovat osobu, která spravuje API pro produkty (gripy, matrace, desky).
Od nich potřebuješ zjistit, jestli:
- Mají produkční API URL
- Nebo jestli budeme používat naše API (které jsem vytvořil)

---

## 🐛 DEBUGGING

### Logování

Přidej do `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Logy najdeš v `wp-content/debug.log`.

### Časté problémy

**1. "Invalid token"**
- Token vypršel (default 1 hodina)
- Špatný JWT secret key
- Konfigurátor volá špatnou URL

**2. "Plan not found"**
- AccessHash neexistuje v databázi
- Uživatel nemá oprávnění k plánu

**3. Produkty se nezobrazují v konfiguratoru**
- Zkontroluj kategorie (gripy, matrace, desky)
- Zkontroluj ceny produktů
- Zkontroluj API URL v .env konfiguratoru

---

## 📝 TODO PRO DOKONČENÍ

- [ ] Přidat tlačítko "Nakonfigurovat stěnu" na web
- [ ] Vytvořit kategorie: gripy, matrace, desky
- [ ] Přidat produkty do kategorií
- [ ] Upravit .env konfiguratoru
- [ ] Otestovat kompletní flow
- [ ] Kontaktovat p. Kukuru ohledně API

---

## 📞 PODPORA

Pokud něco nefunguje, kontaktuj mě s těmito informacemi:
- Verze pluginu (1.0.0)
- URL eshopu
- Chybová hláška
- Logy z debug.log

---

**Verze:** 1.0.0  
**Poslední update:** 10. 2. 2026
