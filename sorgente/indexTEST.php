<?php
/**
 * indexTEST.php
 * Pagina per l'esecuzione dei test unitari.
 * Esegue tutti i test nella directory TEST/ e mostra i risultati.
 */
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laureandosi - Test</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f6f8;
            color: #333;
        }
        header {
            background-color: #1a3e5c;
            color: white;
            padding: 1.5em;
            text-align: center;
        }
        header h1 { font-size: 1.5em; }
        main { max-width: 860px; margin: 2em auto; padding: 0 1em; }
        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            padding: 1.5em;
            margin-bottom: 1.5em;
        }
        .card h2 {
            font-size: 1.1em;
            margin-bottom: 1em;
            color: #1a3e5c;
            border-bottom: 2px solid #e0e4e8;
            padding-bottom: 0.5em;
        }
        .test-ok {
            background: #d4edda; color: #155724;
            padding: 0.4em 0.8em; margin: 0.3em 0;
            border-radius: 4px; font-family: monospace; font-size: 0.9em;
            cursor: pointer;
            transition: background 0.2s;
        }
        .test-ok:hover {
            background: #c3e6cb;
        }
        .test-fail {
            background: #f8d7da; color: #721c24;
            padding: 0.4em 0.8em; margin: 0.3em 0;
            border-radius: 4px; font-family: monospace; font-size: 0.9em;
            cursor: pointer;
            transition: background 0.2s;
        }
        .test-fail:hover {
            background: #f5c6cb;
        }
        .test-errore {
            background: #fff3cd; color: #856404;
            padding: 0.4em 0.8em; margin: 0.3em 0;
            border-radius: 4px; font-family: monospace; font-size: 0.9em;
            cursor: pointer;
        }
        .test-dettagli {
            display: none;
            margin-top: 0.5em;
            padding: 0.8em;
            background: #f8f9fa;
            border-left: 3px solid #6c757d;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            font-size: 0.85em;
            line-height: 1.6;
        }
        .test-dettagli.visible {
            display: block;
        }
        .test-dettagli strong {
            color: #495057;
        }
        .riepilogo {
            font-size: 1.1em;
            font-weight: bold;
            padding: 1em;
            border-radius: 5px;
            margin-top: 1em;
        }
        .riepilogo-ok {
            background: #d4edda; color: #155724;
            border: 1px solid #c3e6cb;
        }
        .riepilogo-fail {
            background: #f8d7da; color: #721c24;
            border: 1px solid #f5c6cb;
        }
        footer { text-align: center; padding: 1.5em; color: #888; font-size: 0.85em; }
    </style>
</head>
<body>

<header>
    <h1>Test Laureandosi</h1>
</header>

<main>

<?php

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
} // end if !function_exists('assertTest')

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
} // end if !function_exists('assertUguale')

// ===== Esecuzione test =====
$fileTest = [
    'TESTEsameLaureando'                => 'test/TESTesameLaureando.php',
    'TESTGestioneCarrieraStudente'      => 'test/TESTgestioneCarrieraStudente.php',
    'TESTConfigurazione'                => 'test/TESTconfigurazione.php',
    'TESTCarrieraLaureando'             => 'test/TESTcarrieraLaureando.php',
    'TESTCarrieraLaureandoInformatica'  => 'test/TESTcarrieraLaureandoInformatica.php',
    'TESTAccessoProspetti'              => 'test/TESTaccessoProspetti.php',
    'TESTProspettoPDFLaureando'         => 'test/TESTprospettoPDFLaureando.php',
    'TESTProspettoPDFCommissione'       => 'test/TESTprospettoPDFCommissione.php',
];

foreach ($fileTest as $nome => $percorso) {
    $fullPath = __DIR__ . '/' . $percorso;
    echo '<div class="card">';
    echo '<h2>' . htmlspecialchars($nome) . '</h2>';

    if (file_exists($fullPath)) {
        try {
            include $fullPath;
        } catch (Throwable $e) {
            echo '<div class="test-errore">ECCEZIONE: '
                . htmlspecialchars($e->getMessage())
                . ' in ' . htmlspecialchars(basename($e->getFile()))
                . ':' . $e->getLine() . '</div>';
        }
    } else {
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

?>

</main>

<script>
function toggleDetails(testId) {
    const detailsDiv = document.getElementById(testId);
    if (detailsDiv) {
        detailsDiv.classList.toggle('visible');
    }
}
</script>

<footer>
    Laureandosi &copy; DII - Universit&agrave; di Pisa
</footer>

</body>
</html>
