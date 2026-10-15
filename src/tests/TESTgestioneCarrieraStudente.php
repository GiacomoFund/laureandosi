<?php
/**
 * TESTgestioneCarrieraStudente.php
 * Test unitari per la classe GestioneCarrieraStudente.
 * Verifica: accesso ai dati studenti (anagrafica ed esami) da file JSON.
 */

require_once __DIR__ . '/../classes/GestioneCarrieraStudente.php';

// Carica configurazione test da JSON
$configFile = __DIR__ . '/fixtures/config_TESTgestioneCarrieraStudente.json';
$testConfig = json_decode(file_get_contents($configFile), true);

$gcs = new GestioneCarrieraStudente();

// ===== TEST 1: Caricamento anagrafica e carriera studente 123456 =====
$test1 = $testConfig['tests'][0];
$anagrafica = $gcs->prelevaAnagrafica($test1['matricola']);
$carriera = $gcs->prelevaCarriera($test1['matricola']);

$datiOk = (
    $anagrafica !== null &&
    ($anagrafica['Entries']['Entry']['nome'] ?? '') === $test1['expected']['nome'] &&
    ($anagrafica['Entries']['Entry']['cognome'] ?? '') === $test1['expected']['cognome'] &&
    $carriera !== null &&
    isset($carriera['Esami']['Esame']) &&
    is_array($carriera['Esami']['Esame']) &&
    count($carriera['Esami']['Esame']) >= $test1['expected']['minEsami']
);

$dettaglio1 = 'Carica i dati anagrafici e la carriera completa di uno studente dal file system. Verifica che nome, cognome siano corretti e che la carriera contenga almeno 20 esami. I dati provengono da file JSON separati (123456_anagrafica.json e 123456_esami.json).';

assertTest(
    'Caricamento anagrafica e carriera studente 123456',
    $datiOk,
    $dettaglio1,
    'Nome=GIANLUIGI, Cognome=DONNARUMMA, Esami>=20',
    sprintf('Nome=%s, Cognome=%s, Esami=%d',
        $anagrafica['Entries']['Entry']['nome'] ?? '',
        $anagrafica['Entries']['Entry']['cognome'] ?? '',
        isset($carriera['Esami']['Esame']) ? count($carriera['Esami']['Esame']) : 0)
);

// ===== TEST 2: Caricamento multiplo studenti (robustezza) =====
$test2 = $testConfig['tests'][1];
try {
    $anagrafica2 = $gcs->prelevaAnagrafica($test2['matricola']);
    $nome2 = $anagrafica2['Entries']['Entry']['nome'] ?? '';

    $multiStudenteOk = ($nome2 === $test2['expected']['nome']);

    $dettaglio2 = 'Verifica che la classe possa gestire richieste sequenziali per studenti diversi senza mantenere stato tra le chiamate. Carica la matricola 234567 dopo aver già caricato la 123456.';

    assertTest(
        'Caricamento sequenziale di più studenti',
        $multiStudenteOk,
        $dettaglio2,
        'Nome studente 234567 = ALESSANDRO',
        'Nome = ' . $nome2
    );
} catch (Exception $e) {
    assertTest(
        'Caricamento sequenziale di più studenti',
        false,
        'Eccezione nel caricamento di un secondo studente',
        'Nessuna eccezione',
        'Eccezione: ' . $e->getMessage()
    );
}

// ===== TEST 3: Gestione errori - matricola non valida =====
$test3 = $testConfig['tests'][2];
$eccezioniOk = true;
$messaggi = [];

// Matricola non numerica
try {
    $gcs->prelevaAnagrafica($test3['matricolaInvalida']);
    $eccezioniOk = false;
    $messaggi[] = 'Matricola non numerica non bloccata';
} catch (InvalidArgumentException $e) {
    $messaggi[] = 'Matricola invalida bloccata: ' . $e->getMessage();
}

// Matricola inesistente
try {
    $gcs->prelevaAnagrafica($test3['matricolaInesistente']);
    $eccezioniOk = false;
    $messaggi[] = 'Matricola inesistente non bloccata';
} catch (RuntimeException $e) {
    $messaggi[] = 'Matricola inesistente bloccata: ' . $e->getMessage();
}

$dettaglio3 = 'Verifica che la classe gestisca correttamente errori di input: matricola non numerica deve lanciare InvalidArgumentException, matricola inesistente deve lanciare RuntimeException. Questo previene errori silenziosi e comportamenti indefiniti.';

assertTest(
    'Gestione errori input (matricola invalida/inesistente)',
    $eccezioniOk,
    $dettaglio3,
    'InvalidArgumentException per input invalido, RuntimeException per matricola non trovata',
    implode('; ', $messaggi)
);
