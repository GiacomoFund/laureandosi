<?php
/**
 * Snippet: Laureandosi - Home (UC1, UC2, UC3)
 * Inserire questo codice nello snippet "Home" del plugin Insert PHP Code Snippet.
 * Nella pagina WordPress, usare lo shortcode generato dal plugin.
 */

if (session_status() === PHP_SESSION_NONE) { session_start(); }

$base_path = get_stylesheet_directory() . '/laureandosi/';

if (!file_exists($base_path . 'index.php')) {
    echo '<div style="color:red;font-family:monospace">Applicazione non trovata: ' . esc_html($base_path . 'index.php') . '</div>';
    return;
}

// Cattura l'output della pagina originale
ob_start();
include $base_path . 'index.php';
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

// --- 4) Fix form action per WordPress ---
$ajaxUrl = admin_url('admin-ajax.php');

$body = str_replace('action="generaProspetti.php"', 'action="' . esc_attr($ajaxUrl) . '?action=laureandosi_genera"', $body);

// --- 5) Fix URL AJAX: usa admin-ajax.php di WordPress ---

$scripts = str_replace(
    "fetch('generaProspetti.php'",
    "fetch('" . esc_js($ajaxUrl) . "?action=laureandosi_genera'",
    $scripts
);
$scripts = str_replace(
    "fetch('inviaProspetti.php'",
    "fetch('" . esc_js($ajaxUrl) . "?action=laureandosi_invia'",
    $scripts
);

// --- 6) Fix URL Visualizza Prospetti (PDF) ---
$pdfBaseUrl = $ajaxUrl . '?action=laureandosi_pdf';
$body = preg_replace(
    '#href="([^"]*)/laureandosi/data/pdf_generati/([^/]*)/([^"]*)"#',
    'href="' . esc_attr($pdfBaseUrl) . '&cdl=$2&file=$3"',
    $body
);

// Fix anche l'aggiornamento dinamico JS del link PDF
$scripts = str_replace(
    "link.href = '/laureandosi/data/pdf_generati/'",
    "link.href = '" . esc_js($pdfBaseUrl) . "&cdl='",
    $scripts
);
// Aggiorna anche il suffisso del percorso PDF nel JS
$scripts = str_replace(
    " + data.cdlShort + '/prospettoCommissione.pdf'",
    " + data.cdlShort + '&file=prospettoCommissione.pdf'",
    $scripts
);

// --- Output dentro WordPress ---
echo '<div class="laureandosi-app">';
echo '<style>' . $css . '</style>';
echo $body;
echo $scripts;
echo '</div>';