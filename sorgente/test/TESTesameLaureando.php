<?php
/**
 * TESTesameLaureando.php
 * Test unitari per la classe EsameLaureando.
 * Verifica: costruttore, getter, setter, gestione voti speciali.
 */

require_once __DIR__ . '/../classi/EsameLaureando.php';

// Carica configurazione test da JSON
$configFile = __DIR__ . '/fixtures/config_TESTesameLaureando.json';
$testConfig = json_decode(file_get_contents($configFile), true);

// ===== TEST 1: Costruttore e getter completi =====
$test1 = $testConfig['tests'][0];
$e1 = $test1['esame'];
$esame = new EsameLaureando($e1['nome'], $e1['codice'], $e1['voto'], $e1['cfu'], $e1['data'], $e1['faMedia'], $e1['informatico']);

$costruttoreOk = (
    $esame->getNome() === $e1['nome'] &&
    $esame->getCodice() === $e1['codice'] &&
    $esame->getVoto() === $e1['voto'] &&
    $esame->getCfu() === $e1['cfu'] &&
    $esame->getData() === $e1['data'] &&
    $esame->getFaMedia() === $e1['faMedia'] &&
    $esame->getInformatico() === $e1['informatico']
);

$dettaglio1 = 'Verifica che il costruttore inizializzi correttamente tutti gli attributi: nome, codice, voto, CFU, data, faMedia e informatico. Usa un esame standard con voto 28.';

assertTest(
    'Costruttore EsameLaureando con tutti i parametri',
    $costruttoreOk,
    $dettaglio1,
    'Tutti i valori corrispondono ai parametri passati al costruttore',
    $costruttoreOk ? 'Tutti i valori corretti' : 'Alcuni valori non corrispondono'
);

// ===== TEST 2: Setter e modifica attributi =====
$test2 = $testConfig['tests'][1];
$mod = $test2['modifiche'];
$esame->setFaMedia($mod['faMedia']);
$esame->setInformatico($mod['informatico']);
$esame->setVoto($mod['voto']);

$setterOk = (
    $esame->getFaMedia() === $mod['faMedia'] &&
    $esame->getInformatico() === $mod['informatico'] &&
    $esame->getVoto() === $mod['voto']
);

$dettaglio2 = 'Verifica che i metodi setter modifichino correttamente gli attributi dell\'oggetto. Testa setFaMedia(false), setInformatico(true) e setVoto(30).';

assertTest(
    'Setter modificano correttamente gli attributi',
    $setterOk,
    $dettaglio2,
    'faMedia=false, informatico=true, voto=30',
    sprintf('faMedia=%s, informatico=%s, voto=%d',
        $esame->getFaMedia() ? 'true' : 'false',
        $esame->getInformatico() ? 'true' : 'false',
        $esame->getVoto())
);

// ===== TEST 3: Gestione idoneità (voto 0) =====
$test3 = $testConfig['tests'][2];
$e3 = $test3['esame'];
$esameIdon = new EsameLaureando($e3['nome'], $e3['codice'], $e3['voto'], $e3['cfu'], $e3['data'], $e3['faMedia'], $e3['informatico']);

$idoneitaOk = (
    $esameIdon->getVoto() === $e3['voto'] &&
    $esameIdon->getFaMedia() === $e3['faMedia']
);

$dettaglio3 = 'Verifica la corretta gestione delle idoneità (esami passati senza voto). Il voto deve essere 0 e l\'esame non deve contribuire alla media.';

assertTest(
    'Gestione corretta delle idoneità (voto=0)',
    $idoneitaOk,
    $dettaglio3,
    'voto=0, faMedia=false',
    sprintf('voto=%d, faMedia=%s',
        $esameIdon->getVoto(),
        $esameIdon->getFaMedia() ? 'true' : 'false')
);

// ===== TEST 4: Gestione lode (voto 33) e attributo informatico =====
$test4 = $testConfig['tests'][3];
$e4 = $test4['esame'];
$esameLode = new EsameLaureando($e4['nome'], $e4['codice'], $e4['voto'], $e4['cfu'], $e4['data'], $e4['faMedia'], $e4['informatico']);

$lodeOk = (
    $esameLode->getVoto() === $e4['voto'] &&
    $esameLode->getFaMedia() === $e4['faMedia'] &&
    $esameLode->getInformatico() === $e4['informatico']
);

$dettaglio4 = 'Verifica che gli esami con lode (voto 33, equivalente a 30 e lode) siano gestiti correttamente e che l\'attributo informatico sia settato per esami del settore INF/01.';

assertTest(
    'Gestione lode (voto=33) e marcatura esami informatici',
    $lodeOk,
    $dettaglio4,
    'voto=33, faMedia=true, informatico=true',
    sprintf('voto=%d, faMedia=%s, informatico=%s',
        $esameLode->getVoto(),
        $esameLode->getFaMedia() ? 'true' : 'false',
        $esameLode->getInformatico() ? 'true' : 'false')
);

?>
