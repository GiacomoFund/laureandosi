<?php
/** Esecuzione da terminale, senza server web o invio di email. */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

ob_start();
require __DIR__ . '/esegui.php';
$reportTest = ob_get_clean();
echo sprintf("Test: %d/%d superati, %d falliti.\n", $_TEST_OK, $_TEST_TOTALI, $_TEST_FAIL);
if ($_TEST_FAIL > 0) {
    echo html_entity_decode(strip_tags(str_replace('</div>', "</div>\n", $reportTest)), ENT_QUOTES, 'UTF-8');
}
exit($_TEST_FAIL > 0 ? 1 : 0);
