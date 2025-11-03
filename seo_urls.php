<?php
/**************************************************************************
 *    SEO Friendly URLs dla 4images - Wariant PREMIUM                    *
 **************************************************************************/
if (!defined('ROOT_PATH')) {
  die("Security violation");
}

/**
 * Tworzy slug SEO z tekstu (konwersja na URL-friendly format)
 */
function make_seo_slug($text) {
    // Zamień polskie znaki
    $polish = array('ą','ć','ę','ł','ń','ó','ś','ź','ż','Ą','Ć','Ę','Ł','Ń','Ó','Ś','Ź','Ż');
    $latin  = array('a','c','e','l','n','o','s','z','z','A','C','E','L','N','O','S','Z','Z');
    $text = str_replace($polish, $latin, $text);
    
    // Zamień na małe litery
    $text = strtolower($text);
    
    // Usuń wszystkie znaki oprócz liter, cyfr, spacji i myślników
    $text = preg_replace('/[^a-z0-9\s\-]/', '', $text);
    
    // Zamień wielokrotne spacje/myślniki na pojedynczy myślnik
    $text = preg_replace('/[\s\-]+/', '-', $text);
    
    // Usuń myślniki z początku i końca
    $text = trim($text, '-');
    
    // Ogranicz długość (opcjonalnie)
    if (strlen($text) > 100) {
        $text = substr($text, 0, 100);
        $text = trim($text, '-');
    }
    
    return $text;
}

/**
 * Generuje URL dla kategorii: /kategoria/123/nazwa-kategorii
 */
function get_category_seo_url($cat_id, $cat_name) {
    global $site_sess;
    $slug = make_seo_slug($cat_name);
    $url = "kategoria/" . $cat_id . "/" . $slug;
    return $site_sess->url($url);
}

/**
 * Generuje URL dla obrazu: /tapeta/123/nazwa-tapety
 */
function get_image_seo_url($image_id, $image_name, $mode = "") {
    global $site_sess;
    $slug = make_seo_slug($image_name);
    $url = "tapeta/" . $image_id . "/" . $slug;
    
    // Zamiast dodawać ?mode= do URLa, zapisz w sesji
    if ($mode && $mode != "") {
        $site_sess->set_session_var("image_mode", $mode);
    }
    
    // NIE dodajemy mode do URLa!
    
    return $site_sess->url($url);
}

/**
 * Generuje URL dla wyszukiwania: /szukaj/fraza-wyszukiwania
 */
function get_search_seo_url($keywords = "") {
    global $site_sess;
    
    if ($keywords && $keywords != "") {
        $slug = make_seo_slug($keywords);
        $url = "szukaj/" . $slug;
    } else {
        $url = "szukaj";
    }
    
    return $site_sess->url($url);
}

/**
 * Dekoduje URL wyszukiwania z powrotem na słowa kluczowe
 */
function decode_search_url($slug) {
    // Zamień myślniki na spacje
    $keywords = str_replace('-', ' ', $slug);
    return trim($keywords);
}
?>