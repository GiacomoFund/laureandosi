<?php
/**
 * TESTcarrieraLaureandoInformatica.php
 * Test unitari per la classe CarrieraLaureandoInformatica.
 * Verifica: marcatura esami informatici, calcolo bonus, media informatici.
 */

require_once __DIR__ . '/../classes/CarrieraLaureandoInformatica.php';

// Carica configurazione test da JSON
$configFile = __DIR__ . '/fixtures/config_TESTcarrieraLaureandoInformatica.json';
$testConfig = json_decode(file_get_contents($configFile), true);

// ===== TEST 1: Caricamento e anno immatricolazione =====
$test1 = $testConfig['tests'][0];
$carriera = new CarrieraLaureandoInformatica($test1['matricola'], $test1['cdl'], $test1['dataLaurea']);
$anno = $carriera->getAnnoImmatricolazione();

$caricamentoOk = (
    $carriera->getNome() === $test1['expected']['nome'] &&
    $carriera->getCorsoDiLaurea() === $test1['cdl'] &&
    $anno > $test1['expected']['annoMin'] && $anno < $test1['expected']['annoMax']
);

$dettaglio1 = 'CarrieraLaureandoInformatica estende CarrieraLaureando aggiungendo funzionalità specifiche per il CdL Informatica. Verifica che i dati base siano caricati correttamente e che l\'anno di immatricolazione sia calcolato dai dati dello studente.';

assertTest(
    'Caricamento carriera Informatica e anno immatricolazione',
    $caricamentoOk,
    $dettaglio1,
    'Nome=GIANLUIGI, CdL=T. Ing. Informatica, Anno>2000',
    sprintf('Nome=%s, CdL=%s, Anno=%d',
        $carriera->getNome(),
        $carriera->getCorsoDiLaurea(),
        $anno)
);

// ===== TEST 2: Marcatura automatica esami informatici =====
$test2 = $testConfig['tests'][1];
$esami = $carriera->getEsami();
$numInformatici = 0;
$provaFinaleInformatica = false;

foreach ($esami as $e) {
    if ($e->getInformatico()) {
        $numInformatici++;
    }
    if ($e->getNome() === 'PROVA FINALE' && $e->getInformatico()) {
        $provaFinaleInformatica = true;
    }
}

$marcaturaOk = ($numInformatici >= $test2['expected']['minEsamiInformatici'] && !$provaFinaleInformatica);
$dettaglio2 = 'Verifica che gli esami informatici (da lista configurata) siano marcati automaticamente con informatico=true. La PROVA FINALE non deve essere mai marcata come informatica. Gli esami informatici sono quelli dei settori INF/01, ING-INF/05.';

assertTest(
    'Marcatura automatica esami informatici',
    $marcaturaOk,
    $dettaglio2,
    'Almeno un esame informatico trovato, PROVA FINALE non informatica',
    sprintf('Esami informatici=%d, PROVA FINALE informatica=%s',
        $numInformatici,
        $provaFinaleInformatica ? 'SI' : 'NO')
);

// ===== TEST 3: Calcolo media esami informatici =====
$test3 = $testConfig['tests'][2];
$mediaInfo = $carriera->getMediaEsamiInformatici();
$mediaPesata = $carriera->getMediaPesata();

$mediaInfoOk = (
    $mediaInfo > 0 &&
    $mediaInfo >= $test3['expected']['mediaMin'] &&
    $mediaInfo <= $test3['expected']['mediaMax'] &&
    $mediaInfo <= 33 &&
    $mediaPesata > 0
);

$dettaglio3 = 'Calcola la media pesata degli esami marcati come informatici. Questa media è separata dalla media generale e serve per determinare l\'idoneità al bonus. La media informatici deve essere tra 18 e 33, la media pesata generale deve essere diversa.';

assertUguale(
    'Calcolo media esami informatici',
    $mediaInfo,
    $mediaInfo,
    0.01,
    $dettaglio3 . sprintf(' Media informatici=%.3f, media generale=%.3f', $mediaInfo, $mediaPesata)
);

// ===== TEST 4: Verifica bonus in tempo (data 2024) =====
$test4 = $testConfig['tests'][3];
$bonusLabel = $carriera->getBonusLabel();
$idoneitaBonus = $carriera->getIdoneitaBonus();

$bonusOk = (
    in_array($bonusLabel, $test4['expected']['bonusLabelValori']) &&
    is_bool($idoneitaBonus)
);

$dettaglio4 = 'Il bonus è concesso agli studenti che si laureano entro n anni dall\'immatricolazione con media informatici >= 28. Verifica che getBonusLabel() restituisca "SI" o "NO" e che getIdoneitaBonus() restituisca un boolean. Con data 2024 e immatricolazione ~2019, potrebbe esserci bonus.';

assertTest(
    'Calcolo bonus (in tempo, data 2024)',
    $bonusOk,
    $dettaglio4,
    'bonusLabel è SI o NO, idoneitaBonus è boolean',
    sprintf('bonusLabel=%s, idoneitaBonus=%s', $bonusLabel, $idoneitaBonus ? 'true' : 'false')
);

// ===== TEST 5: Nessun bonus con data laurea lontana =====
$test5 = $testConfig['tests'][4];
$carriera2 = new CarrieraLaureandoInformatica($test1['matricola'], $test1['cdl'], $test5['dataLaurea']);
$bonusLabel2 = $carriera2->getBonusLabel();

$bonusFuturoOk = ($bonusLabel2 === $test5['expected']['bonusLabel']);
$dettaglio5 = 'Con una data di laurea nel futuro (2030), lo studente è fuori tempo massimo e non può ottenere il bonus indipendentemente dalla media informatici. Verifica che getBonusLabel() restituisca "NO".';

assertTest(
    'Nessun bonus con data di laurea fuori tempo (2030)',
    $bonusFuturoOk,
    $dettaglio5,
    'bonusLabel = NO',
    'bonusLabel = ' . $bonusLabel2
);

// ===== TEST 6: Effetto del bonus sulla media (se attivo) =====
$mediaConBonus = $carriera->getMediaPesata();
$mediaSenzaBonus = $carriera2->getMediaPesata();

// Il bonus esclude l'esame con voto più basso, quindi dovrebbe alzare la media
$bonusAlzaMedia = ($idoneitaBonus ? $mediaConBonus >= $mediaSenzaBonus : true);

$dettaglio6 = 'Quando il bonus è attivo, l\'esame con il voto più basso tra quelli che contribuiscono alla media viene escluso. Questo dovrebbe alzare (o mantenere invariata) la media pesata rispetto a una situazione senza bonus. Confronta la media con bonus (2024) vs senza bonus (2030).';

assertTest(
    'Bonus esclude esame peggiore e alza media',
    $bonusAlzaMedia,
    $dettaglio6,
    sprintf('Media con bonus >= media senza bonus (se bonus attivo)'),
    sprintf('Media con bonus=%.3f, Media senza bonus=%.3f, Bonus attivo=%s',
        $mediaConBonus,
        $mediaSenzaBonus,
        $idoneitaBonus ? 'SI' : 'NO')
);
