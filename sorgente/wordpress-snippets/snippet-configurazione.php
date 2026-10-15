<?php
/**
 * Snippet: Laureandosi - Configurazione
 * Inserire questo codice nello snippet "Configurazione" del plugin Insert PHP Code Snippet.
 */

if (session_status() === PHP_SESSION_NONE) { session_start(); }

$base_path = get_stylesheet_directory() . '/laureandosi/';

if (!file_exists($base_path . 'indexCONF.php')) {
    echo '<div style="color:red;font-family:monospace">Pagina di configurazione non trovata: ' . esc_html($base_path . 'indexCONF.php') . '</div>';
    return;
}

// Cattura l'output della pagina originale
ob_start();
include $base_path . 'indexCONF.php';
$content = ob_get_clean();

// --- 1) Estrai il CSS dai tag <style> ---
$css = '';
if (preg_match_all('/<style[^>]*>(.*?)<\/style>/si', $content, $cssMatches)) {
    foreach ($cssMatches[1] as $block) {
        $css .= $block . "\n";
    }
}

// --- 2) Estrai il contenuto del <body> ---
$body = $content;
if (preg_match('/<body[^>]*>(.*)<\/body>/si', $content, $bodyMatch)) {
    $body = $bodyMatch[1];
}

// --- 3) Estrai i <script> dal body ---
$scripts = '';
if (preg_match_all('/<script[^>]*>.*?<\/script>/si', $body, $scriptMatches)) {
    foreach ($scriptMatches[0] as $tag) {
        $scripts .= $tag . "\n";
    }
    $body = preg_replace('/<script[^>]*>.*?<\/script>/si', '', $body);
}

// --- 4) Fix action dei form POST ---
// I form di configurazione fanno POST a indexCONF.php (la pagina stessa)
// In WordPress devono restare sulla stessa pagina
$body = str_replace('action="indexCONF.php"', 'action="/configurazione/"', $body);

// --- 5) Fix URL AJAX per eliminazione dati ---
$ajaxUrl = admin_url('admin-ajax.php');
$scripts = str_replace(
    "fetch('API.php'",
    "fetch('" . esc_js($ajaxUrl) . "?action=laureandosi_api'",
    $scripts
);

// --- Output dentro WordPress ---
echo '<div class="laureandosi-app">';
echo '<style>' . $css . '</style>';
echo $body;
echo $scripts;
echo '</div>';
?>
