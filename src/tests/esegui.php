<?php
/** Runner condiviso dalla pagina web e dal comando CLI. */
$runtimePrecedente = getenv('LAUREANDOSI_RUNTIME_DIR');
$logPrecedente = ini_get('error_log');
$runtimeTest = sys_get_temp_dir() . '/laureandosi-test-' . bin2hex(random_bytes(8));
if (!mkdir($runtimeTest, 0700)) {
    throw new RuntimeException('Impossibile creare la directory temporanea dei test.');
}
putenv('LAUREANDOSI_RUNTIME_DIR=' . $runtimeTest);
ini_set('error_log', $runtimeTest . '/error.log');
set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// ===== Framework di test minimale =====
// Dichiarazione global necessaria per compatibilita' con WordPress
// (il plugin Insert PHP Code Snippet esegue il codice in scope locale)
global $_TEST_TOTALI, $_TEST_OK, $_TEST_FAIL, $_TEST_COUNTER;
$_TEST_TOTALI = 0;
$_TEST_OK = 0;
$_TEST_FAIL = 0;
$_TEST_COUNTER = 0; // Counter per ID univoci

/**
 * Verifica una condizione e stampa il risultato con dettagli espandibili.
 *
 * @param string $descrizione Titolo breve del test
 * @param bool   $condizione  Risultato del test
 * @param string $dettaglioCompleto Descrizione completa di cosa testa
 * @param mixed  $atteso Output o valore atteso
 * @param mixed  $ricevuto Output o valore ricevuto
 */
if (!function_exists('assertTest')) {
    function assertTest($descrizione, $condizione, $dettaglioCompleto = '', $atteso = '', $ricevuto = '') {
        global $_TEST_TOTALI, $_TEST_OK, $_TEST_FAIL, $_TEST_COUNTER;
        $_TEST_TOTALI++;
        $_TEST_COUNTER++;

        $testId = "test-" . $_TEST_COUNTER;
        $classe = $condizione ? 'test-ok' : 'test-fail';
        $stato = $condizione ? 'OK' : 'FALLITO';

        if ($condizione) {
            $_TEST_OK++;
        } else {
            $_TEST_FAIL++;
        }

        echo '<div class="' . $classe . '" onclick="toggleDetails(\'' . $testId . '\')">';
        echo $stato . ': ' . htmlspecialchars($descrizione);
        echo '</div>';

        // Dettagli espandibili
        if (!empty($dettaglioCompleto) || !empty($atteso) || !empty($ricevuto)) {
            echo '<div id="' . $testId . '" class="test-dettagli">';

            if (!empty($dettaglioCompleto)) {
                echo '<div><strong>Descrizione:</strong> ' . htmlspecialchars($dettaglioCompleto) . '</div>';
            }

            if ($atteso !== '') {
                $attesoStr = is_array($atteso) || is_object($atteso) ? json_encode($atteso) : (string)$atteso;
                echo '<div><strong>Output atteso:</strong> ' . htmlspecialchars($attesoStr) . '</div>';
            }

            if ($ricevuto !== '') {
                $ricevutoStr = is_array($ricevuto) || is_object($ricevuto) ? json_encode($ricevuto) : (string)$ricevuto;
                echo '<div><strong>Output ricevuto:</strong> ' . htmlspecialchars($ricevutoStr) . '</div>';
            }

            echo '</div>';
        }
    }
}

/**
 * Verifica uguaglianza con tolleranza per float.
 */
if (!function_exists('assertUguale')) {
    function assertUguale($descrizione, $atteso, $ottenuto, $tolleranza = 0.01, $dettaglioCompleto = '') {
        if (is_float($atteso) || is_float($ottenuto)) {
            $ok = abs($atteso - $ottenuto) < $tolleranza;
        } else {
            $ok = ($atteso === $ottenuto);
        }

        assertTest($descrizione, $ok, $dettaglioCompleto, $atteso, $ottenuto);
    }
}

try {
    // ===== Esecuzione test =====
    $fileTest = [
        'TESTEsameLaureando'                => 'TESTesameLaureando.php',
        'TESTGestioneCarrieraStudente'      => 'TESTgestioneCarrieraStudente.php',
        'TESTConfigurazione'                => 'TESTconfigurazione.php',
        'TESTCarrieraLaureando'             => 'TESTcarrieraLaureando.php',
        'TESTCarrieraLaureandoInformatica'  => 'TESTcarrieraLaureandoInformatica.php',
        'TESTAccessoProspetti'              => 'TESTaccessoProspetti.php',
        'TESTProspettoPDFLaureando'         => 'TESTprospettoPDFLaureando.php',
        'TESTProspettoPDFCommissione'       => 'TESTprospettoPDFCommissione.php',
        'TESTGenerazioneProspetti'         => 'TESTgenerazioneProspetti.php',
        'TESTPercorsi'                     => 'TESTpercorsi.php',
    ];
    if (PHP_SAPI === 'cli') {
        $fileTest['TESTEndpoint'] = 'TESTendpoint.php';
    }

    foreach ($fileTest as $nome => $percorso) {
        $fullPath = __DIR__ . '/' . $percorso;
        echo '<div class="card">';
        echo '<h2>' . htmlspecialchars($nome) . '</h2>';

        if (file_exists($fullPath)) {
            try {
                $totaliPrimaDelFile = $_TEST_TOTALI;
                include $fullPath;
                if ($_TEST_TOTALI === $totaliPrimaDelFile) {
                    assertTest($nome . ': nessuna verifica eseguita', false);
                }
            } catch (Throwable $e) {
                $_TEST_FAIL++;
                $_TEST_TOTALI++;
                echo '<div class="test-errore">ECCEZIONE: '
                    . htmlspecialchars($e->getMessage())
                    . ' in ' . htmlspecialchars(basename($e->getFile()))
                    . ':' . $e->getLine() . '</div>';
            }
        } else {
            $_TEST_FAIL++;
            $_TEST_TOTALI++;
            echo '<div class="test-errore">File non trovato: ' . htmlspecialchars($percorso) . '</div>';
        }

        echo '</div>';
    }

    // Riepilogo finale
    global $_TEST_TOTALI, $_TEST_OK, $_TEST_FAIL;
    $classeRiepilogo = ($_TEST_FAIL === 0) ? 'riepilogo-ok' : 'riepilogo-fail';
    echo '<div class="riepilogo ' . $classeRiepilogo . '">';
    echo "Riepilogo: {$_TEST_OK}/{$_TEST_TOTALI} superati, {$_TEST_FAIL} falliti.";
    echo '</div>';

} finally {
    restore_error_handler();
    ini_set('error_log', $logPrecedente);
    putenv($runtimePrecedente === false ? 'LAUREANDOSI_RUNTIME_DIR' : 'LAUREANDOSI_RUNTIME_DIR=' . $runtimePrecedente);
    $risultatiTemporanei = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($runtimeTest, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($risultatiTemporanei as $risultatoTemporaneo) {
        if ($risultatoTemporaneo->isDir()) {
            rmdir($risultatoTemporaneo->getPathname());
        } else {
            unlink($risultatoTemporaneo->getPathname());
        }
    }
    rmdir($runtimeTest);
}
