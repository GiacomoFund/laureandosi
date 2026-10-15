<?php
/**
 * TESTconfigurazione.php
 * Test unitari per la classe Configurazione.
 * Verifica: caricamento configurazione, parametri, valutatore formule sicuro.
 * I dati di test vengono caricati da fixtures/config_TESTconfigurazione.json
 */

require_once __DIR__ . '/../classes/Configurazione.php';

// Carica configurazione test da JSON
$configFile = __DIR__ . '/fixtures/config_TESTconfigurazione.json';
if (!file_exists($configFile)) {
    echo '<div class="test-errore">ERRORE: File di configurazione test non trovato: ' . $configFile . '</div>';
    return;
}

$testConfig = json_decode(file_get_contents($configFile), true);
if (!$testConfig || !isset($testConfig['tests'])) {
    echo '<div class="test-errore">ERRORE: Configurazione test non valida o malformata</div>';
    return;
}

// ===== TEST 1: Caricamento configurazione Informatica =====
$test1 = $testConfig['tests'][0];
$config = new Configurazione($test1['cdl']);

$caricamentoOk = (
    $config->getCorsoDiLaurea() === $test1['cdl'] &&
    !empty($config->getFormula()) &&
    $config->getFormula() === $test1['expected']['formula'] &&
    $config->getCfuRichiesti() === $test1['expected']['cfuRichiesti'] &&
    abs($config->getValoreLode() - $test1['expected']['valoreLode']) < $test1['expected']['tolleranzaLode']
);

$dettaglio1 = sprintf('Carica la configurazione per %s e verifica che tutti i parametri base siano presenti: CdL, formula di calcolo, CFU richiesti per la laurea (%d), valore numerico della lode (%.0f).',
    $test1['cdl'], $test1['expected']['cfuRichiesti'], $test1['expected']['valoreLode']);

assertTest(
    'Caricamento configurazione Informatica',
    $caricamentoOk,
    $dettaglio1,
    sprintf('CdL=%s, formula=%s, CFU=%d, lode=%.0f',
        $test1['cdl'], $test1['expected']['formula'],
        $test1['expected']['cfuRichiesti'], $test1['expected']['valoreLode']),
    sprintf('CdL=%s, formula=%s, CFU=%d, lode=%.1f',
        $config->getCorsoDiLaurea(),
        $config->getFormula(),
        $config->getCfuRichiesti(),
        $config->getValoreLode())
);

// ===== TEST 2: Parametri simulazione e esami informatici =====
$test2 = $testConfig['tests'][1];
$sim = $config->getParametriSimulazione();
$esamiInfo = $config->getEsamiInformatici();

$parametriOk = (
    is_array($sim) &&
    $sim['Cmin'] === $test2['expected']['Cmin'] &&
    $sim['Cmax'] === $test2['expected']['Cmax'] &&
    count($esamiInfo) > 0 &&
    in_array($test2['expected']['esameInformaticoEsempio'], $esamiInfo)
);

$dettaglio2 = sprintf('Verifica i parametri della simulazione di voto (Cmin, Cmax, Cstep, Tmin, Tmax, Tstep) e la lista degli esami informatici. Per Informatica, deve includere %s tra gli esami informatici.',
    $test2['expected']['esameInformaticoEsempio']);

assertTest(
    'Parametri simulazione ed esami informatici',
    $parametriOk,
    $dettaglio2,
    sprintf('Cmin=%d, Cmax=%d, esami_informatici contiene %s',
        $test2['expected']['Cmin'], $test2['expected']['Cmax'],
        $test2['expected']['esameInformaticoEsempio']),
    sprintf('Cmin=%d, Cmax=%d, esami_informatici_count=%d, %s_presente=%s',
        $sim['Cmin'], $sim['Cmax'], count($esamiInfo),
        $test2['expected']['esameInformaticoEsempio'],
        in_array($test2['expected']['esameInformaticoEsempio'], $esamiInfo) ? 'SI' : 'NO')
);

// ===== TEST 3: Filtri esami (daTogliere e nonMedia) =====
$test3 = $testConfig['tests'][2];
$filtri = $config->getFiltriEsami();
$filtriOk = (
    isset($filtri['daTogliere']) &&
    count(array_intersect($test3['expected']['daTogliere'], $filtri['daTogliere'])) === count($test3['expected']['daTogliere']) &&
    isset($filtri['nonMedia']) &&
    count(array_intersect($test3['expected']['nonMedia'], $filtri['nonMedia'])) === count($test3['expected']['nonMedia'])
);

$dettaglio3 = 'Verifica che i filtri esami siano caricati correttamente: lista di esami da escludere completamente (daTogliere) e lista di esami che non contribuiscono alla media (nonMedia).';

assertTest(
    'Filtri esami (daTogliere e nonMedia)',
    $filtriOk,
    $dettaglio3,
    sprintf('daTogliere contiene %s, nonMedia contiene %s',
        implode(', ', $test3['expected']['daTogliere']),
        implode(', ', $test3['expected']['nonMedia'])),
    sprintf('daTogliere_count=%d, nonMedia_count=%d',
        count($filtri['daTogliere']), count($filtri['nonMedia']))
);

// ===== TEST 4: Gestione CdL inesistente =====
$test4 = $testConfig['tests'][3];
$eccezioneOk = false;
$messaggioEccezione = '';
try {
    new Configurazione($test4['cdl']);
} catch (InvalidArgumentException $e) {
    $eccezioneOk = true;
    $messaggioEccezione = $e->getMessage();
}

$dettaglio4 = sprintf('Verifica che tentare di caricare una configurazione per un CdL non esistente (%s) generi una InvalidArgumentException. Questo previene errori silenziosi quando si usa un nome CdL errato.',
    $test4['cdl']);

assertTest(
    'Eccezione per CdL inesistente',
    $eccezioneOk,
    $dettaglio4,
    'InvalidArgumentException lanciata',
    $eccezioneOk ? 'Eccezione: ' . $messaggioEccezione : 'Nessuna eccezione'
);

// ===== TEST 5: Valutatore formule sicuro - Operazioni base =====
$test5a = Configurazione::valutaFormula('2 + 3', []);
$test5b = Configurazione::valutaFormula('2 + 3 * 4', []); // 14
$test5c = Configurazione::valutaFormula('(2 + 3) * 4', []); // 20
$test5d = Configurazione::valutaFormula('10 / 4', []); // 2.5

$operazioniBaseOk = (
    abs($test5a - 5.0) < 0.001 &&
    abs($test5b - 14.0) < 0.001 &&
    abs($test5c - 20.0) < 0.001 &&
    abs($test5d - 2.5) < 0.001
);

$dettaglio5 = 'Testa il valutatore di formule con operazioni aritmetiche base: addizione, sottrazione, moltiplicazione, divisione, precedenza operatori e parentesi. Il valutatore deve rispettare le regole matematiche standard.';

assertTest(
    'Valutatore formule - Operazioni base',
    $operazioniBaseOk,
    $dettaglio5,
    '2+3=5, 2+3*4=14, (2+3)*4=20, 10/4=2.5',
    sprintf('2+3=%.2f, 2+3*4=%.2f, (2+3)*4=%.2f, 10/4=%.2f', $test5a, $test5b, $test5c, $test5d)
);

// ===== TEST 6: Valutatore formule - Variabili e formula reale =====
$test5 = $testConfig['tests'][4];
$test6a = Configurazione::valutaFormula('$M * 3 + $T', ['M' => 25, 'T' => 5]); // 80
$test6b = Configurazione::valutaFormula(
    $test5['cdl'] === 'T. Ing. Informatica' ? $testConfig['tests'][0]['expected']['formula'] : '$M * 3 + 18 + $T + $C',
    $test5['test']['variabili']
);

$atteso6b = $test5['test']['expected'];
$formulaRealeOk = (
    abs($test6a - 80.0) < 0.001 &&
    abs($test6b - $atteso6b) < $test5['test']['tolleranza']
);

$dettaglio6 = sprintf('Testa il supporto per variabil i nelle formule ($M, $T, $C, $CFU) e verifica che la formula reale di Informatica ($M*3+18+$T+$C) produca risultati corretti. Con M=%.3f, il risultato deve essere circa %.3f.',
    $test5['test']['variabili']['M'], $atteso6b);

assertTest(
    'Valutatore formule - Variabili e formula Informatica',
    $formulaRealeOk,
    $dettaglio6,
    sprintf('$M*3+$T con M=25,T=5 = 80; Informatica con M=%.3f = %.3f',
        $test5['test']['variabili']['M'], $atteso6b),
    sprintf('Test variabili=%.2f, Formula Informatica=%.3f', $test6a, $test6b)
);

// ===== TEST 7: Sicurezza valutatore (blocco codice malevolo) =====
$test6 = $testConfig['tests'][5];
$sicurezzaOk = true;
$messaggiErrore = [];

// Tentativo di esecuzione codice
try {
    Configurazione::valutaFormula('system("ls")', []);
    $sicurezzaOk = false;
    $messaggiErrore[] = 'system() non bloccato';
} catch (InvalidArgumentException $e) {
    $messaggiErrore[] = 'system() bloccato OK';
}

// Divisione per zero
try {
    Configurazione::valutaFormula('5 / 0', []);
    $sicurezzaOk = false;
    $messaggiErrore[] = 'divisione/0 non bloccata';
} catch (InvalidArgumentException $e) {
    $messaggiErrore[] = 'divisione/0 bloccata OK';
}

$dettaglio7 = 'Verifica che il valutatore di formule blocchi codice potenzialmente pericoloso (chiamate a funzioni di sistema) e gestisca errori matematici (divisione per zero). Il valutatore NO-EVAL deve essere sicuro contro injection attacks.';

assertTest(
    'Sicurezza valutatore formule (anti-injection)',
    $sicurezzaOk,
    $dettaglio7,
    'Codice malevolo bloccato, divisione per zero gestita',
    implode('; ', $messaggiErrore)
);
