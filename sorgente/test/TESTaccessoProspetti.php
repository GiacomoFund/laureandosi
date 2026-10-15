<?php
/**
 * TESTaccessoProspetti.php
 * Test unitari per la classe AccessoProspetti.
 * Verifica: gestione percorsi PDF, nomi file, percorsi assoluti.
 */

require_once __DIR__ . '/../classi/AccessoProspetti.php';

// Carica configurazione test da JSON
$configFile = __DIR__ . '/fixtures/config_TESTaccessoProspetti.json';
$testConfig = json_decode(file_get_contents($configFile), true);

// ===== TEST 1: Percorsi PDF commissione e laureando =====
$test1 = $testConfig['tests'][0];
$accesso = new AccessoProspetti();
$percorsoComm = $accesso->getPercorsoCommissione();
$percorsoLaur = $accesso->getPercorsoLaureando($test1['matricola']);

$percorsiOk = (
    !empty($percorsoComm) &&
    strpos($percorsoComm, $test1['expected']['contiene_commissione']) !== false &&
    strpos($percorsoComm, $test1['expected']['contiene_pdf']) !== false &&
    !empty($percorsoLaur) &&
    strpos($percorsoLaur, $test1['matricola']) !== false &&
    strpos($percorsoLaur, $test1['expected']['contiene_pdf']) !== false
);

$dettaglio1 = 'La classe AccessoProspetti gestisce i percorsi dei file PDF generati. Verifica che i metodi getPercorsoCommissione() e getPercorsoLaureando() restituiscano percorsi validi contenenti i nomi file corretti e l\'estensione.pdf.';

assertTest(
    'Percorsi PDF commissione e laureando',
    $percorsiOk,
    $dettaglio1,
    'Percorso commissione contiene "prospettoCommissione.pdf", percorso laureando contiene matricola',
    sprintf('Commissione=%s, Laureando=%s', $percorsoComm, $percorsoLaur)
);

// ===== TEST 2: Percorso assoluto e cartella output =====
$test2 = $testConfig['tests'][1];
$percorsoAss = $accesso->getPercorsoCommissioneAssoluto();
$cartella = $accesso->getCartellaOutput();

$percorsoAssolutoOk = (
    !empty($percorsoAss) &&
    (strpos($percorsoAss, $test2['expected']['contiene_data'] . '/' . $test2['expected']['contiene_pdf_generati']) !== false ||
     strpos($percorsoAss, $test2['expected']['contiene_data'] . DIRECTORY_SEPARATOR . $test2['expected']['contiene_pdf_generati']) !== false) &&
    !empty($cartella) &&
    (strpos($cartella, $test2['expected']['contiene_data']) !== false)
);

$dettaglio2 = 'Verifica che getPercorsoCommissioneAssoluto() restituisca un percorso assoluto contenente la struttura "data/pdf_generati" e che getCartellaOutput() restituisca la cartella base dove salvare i PDF. Il percorso assoluto è necessario per salvare i file sul filesystem.';

assertTest(
    'Percorso assoluto e cartella output',
    $percorsoAssolutoOk,
    $dettaglio2,
    'Percorso assoluto contiene "data/pdf_generati", cartella output valida',
    sprintf('Assoluto=%s, Cartella=%s', $percorsoAss, $cartella)
);

// ===== TEST 3: Lista percorsi e formato slash =====
$test3 = $testConfig['tests'][2];
$percorsi = $accesso->getPercorsiPDF();
$formatoOk = (
    is_array($percorsi) &&
    ($test3['expected']['no_backslash'] ? strpos($percorsoComm, '\\') === false : true)
);

$dettaglio3 = 'Verifica che getPercorsiPDF() restituisca un array di percorsi e che i percorsi usino forward slash (/) invece di backslash (\) per compatibilità web. I percorsi relativi devono essere usabili direttamente in href HTML.';

assertTest(
    'Lista percorsi e formato slash corretto',
    $formatoOk,
    $dettaglio3,
    'getPercorsiPDF() è array, percorsi usano forward slash (/)',
    sprintf('Array=%s, Percorsi contengono backslash=%s',
        is_array($percorsi) ? 'SI' : 'NO',
        strpos($percorsoComm, '\\') !== false ? 'SI (ERRORE)' : 'NO (OK)')
);
