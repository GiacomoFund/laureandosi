<?php
/**
 * TESTcarrieraLaureando.php
 * Test unitari per la classe CarrieraLaureando.
 * Verifica: caricamento dati, filtri esami, calcoli media e crediti, formula laurea.
 * I dati di test vengono caricati da fixtures/config_TESTcarrieraLaureando.json
 */

require_once __DIR__ . '/../classi/CarrieraLaureando.php';

// Carica configurazione test da JSON
$configFile = __DIR__ . '/fixtures/config_TESTcarrieraLaureando.json';
if (!file_exists($configFile)) {
    echo '<div class="test-errore">ERRORE: File di configurazione test non trovato: ' . $configFile . '</div>';
    return;
}

$testConfig = json_decode(file_get_contents($configFile), true);
if (!$testConfig || !isset($testConfig['tests'])) {
    echo '<div class="test-errore">ERRORE: Configurazione test non valida o malformata</div>';
    return;
}

// Test 1: usa il primo test della configurazione
$test1 = $testConfig['tests'][0];
$carriera = new CarrieraLaureando($test1['matricola'], $test1['cdl'], $test1['dataLaurea']);

// ===== TEST 1: Caricamento dati anagrafici e esami =====
$esami = $carriera->getEsami();
$anagraficaOk = (
    $carriera->getNome() === $test1['expected']['nome'] &&
    $carriera->getCognome() === $test1['expected']['cognome'] &&
    $carriera->getMatricola() === $test1['expected']['matricola'] &&
    count($esami) >= $test1['expected']['minEsami']
);

$dettaglio1 = 'Verifica che i dati anagrafici vengano caricati correttamente dal file JSON della matricola e che gli esami siano stati letti dal file degli esami. Controlla nome, cognome, matricola e presenza di almeno un esame.';

assertTest(
    'Caricamento dati anagrafici ed esami da file JSON',
    $anagraficaOk,
    $dettaglio1,
    sprintf('Nome=%s, Cognome=%s, Matricola=%s, Esami>=%d',
        $test1['expected']['nome'],
        $test1['expected']['cognome'],
        $test1['expected']['matricola'],
        $test1['expected']['minEsami']),
    sprintf('Nome=%s, Cognome=%s, Matricola=%s, Esami=%d',
        $carriera->getNome(),
        $carriera->getCognome(),
        $carriera->getMatricola(),
        count($esami))
);

// ===== TEST 2: Filtri esami (esclusioni e non-media) =====
$test2 = $testConfig['tests'][1];
$testValutazioneTrovato = false;
$linguaIngleseNonFaMedia = false;
$votiZeroNonFannoMedia = true;

foreach ($esami as $e) {
    if ($e->getNome() === 'TEST DI VALUTAZIONE DI INGEGNERIA') {
        $testValutazioneTrovato = true;
    }
    if ($e->getNome() === 'PROVA DI LINGUA INGLESE (B1)') {
        $linguaIngleseNonFaMedia = !$e->getFaMedia();
    }
    if ($e->getVoto() === 0 && $e->getFaMedia()) {
        $votiZeroNonFannoMedia = false;
    }
}

$filtriOk = ($testValutazioneTrovato === !$test2['expected']['testValutazioneEscluso']) &&
            ($linguaIngleseNonFaMedia === $test2['expected']['linguaIngleseNonFaMedia']) &&
            ($votiZeroNonFannoMedia === $test2['expected']['votiZeroEsclusi']);
$dettaglio2 = 'Verifica che i filtri configurati in Configurazione siano applicati correttamente: TEST DI VALUTAZIONE deve essere escluso completamente, PROVA DI LINGUA INGLESE (B1) non deve fare media (esame idoneità), esami con voto 0 non devono contribuire alla media.';

assertTest(
    'Applicazione corretta dei filtri esami',
    $filtriOk,
    $dettaglio2,
    'TEST VALUTAZIONE escluso, PROVA DI LINGUA INGLESE non fa media, voti 0 esclusi dalla media',
    sprintf('TEST VALUTAZIONE found=%s, LINGUA INGLESE fa media=%s, voti 0 esclusi=%s',
        $testValutazioneTrovato ? 'true' : 'false',
        $linguaIngleseNonFaMedia ? 'false' : 'true',
        $votiZeroNonFannoMedia ? 'true' : 'false')
);

// ===== TEST 3: Calcolo media pesata =====
$test3 = $testConfig['tests'][2];
$media = $carriera->getMediaPesata();
$mediaOk = ($media >= $test3['expected']['minMedia'] && $media <= $test3['expected']['maxMedia']);

$dettaglio3 = sprintf('Calcola la media pesata degli esami che contribuiscono alla media. Formula: somma(voto*CFU) / somma(CFU). Il valore atteso per la matricola %s è circa %.3f (con tolleranza ±%.2f).',
    $test3['matricola'], $test3['expected']['media'], $test3['expected']['tolleranza']);

assertUguale(
    'Calcolo media pesata degli esami',
    $test3['expected']['media'],
    $media,
    $test3['expected']['tolleranza'],
    $dettaglio3
);

// ===== TEST 4: Calcolo crediti conseguiti e crediti media =====
$test4 = $testConfig['tests'][3];
$crediti = $carriera->calcolaCreditiConseguiti();
$creditiMedia = $carriera->calcolaCreditiMedia();

$creditiOk = (
    $crediti > $test4['expected']['minCrediti'] &&
    ($test4['expected']['creditiMediaPositivi'] ? $creditiMedia > 0 : true) &&
    $creditiMedia <= $crediti
);

$dettaglio4 = sprintf('Verifica il calcolo dei CFU conseguiti (tutti gli esami superati) e dei CFU che contribuiscono alla media (esami con faMedia=true). Per la laurea triennale, i crediti dovrebbero essere circa 180 (minimo %d), mentre i crediti media dovrebbero escludere idoneità e prova finale.',
    $test4['expected']['minCrediti']);

assertTest(
    'Calcolo crediti conseguiti e crediti media',
    $creditiOk,
    $dettaglio4,
    sprintf('CFU_conseguiti>%d, CFU_media>0, CFU_media<=CFU_conseguiti', $test4['expected']['minCrediti']),
    sprintf('CFU_conseguiti=%d, CFU_media=%d', $crediti, $creditiMedia)
);

// ===== TEST 5: Calcolo voto di laurea con formula =====
$test5 = $testConfig['tests'][4];
$T = $test5['input']['T'];  // Voto tesi
$C = $test5['input']['C'];   // Voto commissione
$votoLaurea = $carriera->calcolaVotoLaurea($T, $C);
// Formula standard: M*3 + 18 + T + C
$atteso = round($media * 3 + 18 + $T + $C, 2);

$dettaglio5 = sprintf('Calcola il voto di laurea usando la formula configurata (M*3 + 18 + T + C). Con media=%.3f, T=%d, C=%d, il voto atteso è %.2f. Verifica che il metodo calcolaVotoLaurea applichi correttamente la formula.', $media, $T, $C, $atteso);

assertUguale(
    'Calcolo voto di laurea con formula',
    $atteso,
    $votoLaurea,
    $test5['expected']['tolleranza'],
    $dettaglio5
);

// ===== TEST 6: Caricamento secondo studente (test robustezza) =====
$test6 = $testConfig['tests'][5];
try {
    $carriera2 = new CarrieraLaureando($test6['matricola'], $test6['cdl'], $test6['dataLaurea']);
    $nome2 = $carriera2->getNome();
    $media2 = $carriera2->getMediaPesata();
    
    $secondoStudenteOk = (
        $nome2 === $test6['expected']['nome'] &&
        $media2 > $test6['expected']['minMedia'] &&
        $media2 < $test6['expected']['maxMedia']
    );
    
    $dettaglio6 = sprintf('Verifica la capacità di caricare più studenti diversi in sequenza. Testa la matricola %s (%s) per verificare che nome e media siano corretti e che la classe non mantenga stato tra istanze diverse.',
        $test6['matricola'], $test6['expected']['nome']);
    
    assertTest(
        'Caricamento multiplo studenti (robustezza)',
        $secondoStudenteOk,
        $dettaglio6,
        sprintf('Nome=%s, %d<media<%d', $test6['expected']['nome'], $test6['expected']['minMedia'], $test6['expected']['maxMedia']),
        sprintf('Nome=%s, media=%.3f', $nome2, $media2)
    );
} catch (Exception $e) {
    assertTest(
        'Caricamento multiplo studenti (robustezza)',
        false,
        'Eccezione nel caricamento di un secondo studente',
        'Nessuna eccezione',
        'Eccezione: ' . $e->getMessage()
    );
}
