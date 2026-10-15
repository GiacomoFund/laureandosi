<?php
/**
 * Snippet: Laureandosi - Test
 * Inserire questo codice nello snippet "Test" del plugin Insert PHP Code Snippet.
 */

if (session_status() === PHP_SESSION_NONE) { session_start(); }

$base_path = get_stylesheet_directory() . '/laureandosi/';

if (!file_exists($base_path . 'indexTEST.php')) {
    echo '<div style="color:red;font-family:monospace">Pagina test non trovata: ' . esc_html($base_path . 'indexTEST.php') . '</div>';
    return;
}

// Cattura l'output della pagina originale
ob_start();
include $base_path . 'indexTEST.php';
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

// --- 4) (nessun link di navigazione da correggere) ---

// --- Output dentro WordPress ---
echo '<div class="laureandosi-app">';
echo '<style>' . $css . '</style>';
echo $body;
echo $scripts;
echo '</div>';
?>
