<?php
/**
 * TESTprospettoPDFLaureando.php
 * Test unitari per la classe ProspettoPDFLaureando.
 * Verifica: generazione PDF, salvataggio su filesystem, libreria fpdf184.
 */

require_once __DIR__ . '/../classes/ProspettoPDFLaureando.php';
require_once __DIR__ . '/../classes/AccessoProspetti.php';
require_once __DIR__ . '/../classes/Configurazione.php';

// Carica configurazione test da JSON
$configFile = __DIR__ . '/fixtures/config_TESTprospettoPDFLaureando.json';
$testConfig = json_decode(file_get_contents($configFile), true);

// ===== TEST 1: Creazione oggetto e generazione PDF =====
$test1 = $testConfig['tests'][0];
$cdl1 = $test1['cdl'];
$config1 = new Configurazione($cdl1);
$prospetto = new ProspettoPDFLaureando($test1['matricola'], $cdl1, $test1['dataLaurea']);
$pdf = $prospetto->creaPDFLaureando();

$creazioneOk = (
    $prospetto !== null &&
    $pdf !== null &&
    $pdf instanceof $test1['expected']['classePDF']
);

$dettaglio1 = 'Crea un oggetto ProspettoPDFLaureando per la matricola 123456 e genera il PDF usando libreria fpdf184. Verifica che l\'oggetto PDF sia creato correttamente e sia un\'istanza della classe FPDF. Il PDF contiene la carriera completa dello studente con esami, media, voto previsto.';

assertTest(
    'Creazione oggetto ProspettoPDFLaureando e generazione PDF',
    $creazioneOk,
    $dettaglio1,
    'Oggetto ProspettoPDFLaureando creato, PDF è istanza FPDF',
    sprintf('Prospetto=%s, PDF=%s',
        $prospetto !== null ? 'OK' : 'NULL',
        ($pdf instanceof FPDF) ? 'FPDF' : (is_object($pdf) ? get_class($pdf) : 'NULL'))
);

// ===== TEST 2: Salvataggio file PDF su filesystem =====
$test2 = $testConfig['tests'][1];
$prospetto->salvaProspetto();
$accesso = new AccessoProspetti($config1->getCdlShort());
$percorso = $accesso->getPercorsoLaureandoAssoluto($test1['matricola']);
$fileEsiste = file_exists($percorso);
$dimensione = $fileEsiste ? filesize($percorso) : 0;

$salvataggiook = ($fileEsiste && $dimensione > $test2['expected']['minDimensione']);

$dettaglio2 = 'Salva il PDF generato nel filesystem nella cartella data/pdf_generati/<CdLShort>/ con nome prospetto_<matricola>.pdf. Verifica che il file sia stato creato e che abbia dimensione > 100 bytes (non vuoto). Il percorso è determinato da AccessoProspetti.';

assertTest(
    'Salvataggio file PDF su filesystem',
    $salvataggiook,
    $dettaglio2,
    sprintf('File creato in %s, dimensione > 100 bytes', $percorso),
    sprintf('File esiste=%s, dimensione=%d bytes',
        $fileEsiste ? 'SI' : 'NO',
        $dimensione)
);

$test3 = $testConfig['tests'][2];
try {
    $cdl2 = $test3['cdl'];
    $config2 = new Configurazione($cdl2);
    $prospetto2 = new ProspettoPDFLaureando($test3['matricola'], $cdl2, $test3['dataLaurea']);
    $prospetto2->salvaProspetto();
    $accesso2 = new AccessoProspetti($config2->getCdlShort());
    $percorso2 = $accesso2->getPercorsoLaureandoAssoluto($test3['matricola']);
    $file2Esiste = file_exists($percorso2);
    $dimensione2 = $file2Esiste ? filesize($percorso2) : 0;

    $multiStudenteOk = ($file2Esiste && $dimensione2 > $test3['expected']['minDimensione']);

    $dettaglio3 = 'Verifica che la classe possa generare PDF per studenti diversi in sequenza senza conflitti. Genera un secondo PDF per la matricola 234567 (CdL Biomedica). I file devono essere salvati in cartelle separate per ogni CdL.';

    assertTest(
        'Generazione multipla PDF (robustezza)',
        $multiStudenteOk,
        $dettaglio3,
        sprintf('File 234567 creato, dimensione > 100 bytes'),
        sprintf('File esiste=%s, dimensione=%d bytes, percorso=%s',
            $file2Esiste ? 'SI' : 'NO',
            $dimensione2,
            $percorso2)
    );
} catch (Exception $e) {
    assertTest(
        'Generazione multipla PDF (robustezza)',
        false,
        'Eccezione durante generazione secondo PDF',
        'Nessuna eccezione',
        'Eccezione: ' . $e->getMessage()
    );
}
