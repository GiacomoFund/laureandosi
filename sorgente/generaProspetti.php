<?php
/**
 * generaProspetti.php
 * Endpoint per la generazione dei prospetti (UC1).
 * Riceve i dati via POST e restituisce una risposta JSON.
 *
 * Parametri POST:
 *  - cdl: Corso di Laurea selezionato
 *  - data_laurea: Data dell'appello di laurea
 *  - matricole: Sequenza di matricole (separate da spazi, virgole o a capo)
 */

// Solo richieste POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'successo' => false,
        'messaggio' => 'Metodo non consentito. Utilizzare POST.'
    ]);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/classi/GestoreProspetti.php';
require_once __DIR__ . '/classi/Configurazione.php';

try {
    foreach (['cdl', 'data_laurea', 'matricole'] as $campo) {
        if (isset($_POST[$campo]) && !is_string($_POST[$campo])) {
            throw new InvalidArgumentException('Parametro non valido: ' . $campo);
        }
    }
    // ===== Validazione input =====
    $cdl = isset($_POST['cdl']) ? trim($_POST['cdl']) : '';
    $dataLaurea = isset($_POST['data_laurea']) ? trim($_POST['data_laurea']) : '';
    $matricoleRaw = isset($_POST['matricole']) ? trim($_POST['matricole']) : '';

    if (empty($cdl)) {
        throw new InvalidArgumentException("Selezionare un Corso di Laurea.");
    }

    if (empty($dataLaurea)) {
        throw new InvalidArgumentException("Inserire la data di laurea.");
    }

    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $dataLaurea, $partiData) ||
        !checkdate((int)$partiData[2], (int)$partiData[3], (int)$partiData[1])) {
        throw new InvalidArgumentException('Data non valida. Usare YYYY-MM-DD.');
    }

    if (empty($matricoleRaw)) {
        throw new InvalidArgumentException("Inserire almeno una matricola.");
    }

    // Parsing matricole: accetta spazi, virgole, a capo come separatori
    $matricole = preg_split('/[\s,]+/', $matricoleRaw, -1, PREG_SPLIT_NO_EMPTY);

    // Valida ogni matricola (solo cifre)
    $matricoleValide = [];
    foreach ($matricole as $m) {
        $m = trim($m);
        if (preg_match('/^\d+$/', $m)) {
            $matricoleValide[] = $m;
        }
    }

    if (empty($matricoleValide)) {
        throw new InvalidArgumentException("Nessuna matricola valida inserita.");
    }

    $matricoleValide = array_values(array_unique($matricoleValide));

    // ===== Generazione prospetti =====
    $gestore = new GestoreProspetti($matricoleValide, $cdl, $dataLaurea);
    $gestore->generaProspettiLaureandi();
    if (empty($gestore->getProspettiGenerati())) {
        throw new RuntimeException('Nessun prospetto generato. Verificare le matricole inserite.');
    }
    $gestore->generaProspettoCommissione();
    $gestore->salvaDatiPerInvio();

    // Costruisci messaggio di risposta
    $generati = $gestore->getProspettiGenerati();
    $errori   = $gestore->getErrori();

    $messaggio = "Prospetti creati: " . count($generati) . " su " . count($matricoleValide) . ".";
    if (!empty($errori)) {
        $messaggio .= " Errori: ";
        foreach ($errori as $err) {
            $messaggio .= $err['matricola'] . " (" . $err['errore'] . "); ";
        }
    }

    // Restituisci cdlShort per aggiornamento dinamico del link Visualizza
    $config = new Configurazione($cdl);
    $cdlShort = $config->getCdlShort();

    echo json_encode([
        'successo'   => true,
        'messaggio'  => $messaggio,
        'generati'   => count($generati),
        'matricole'  => $generati,
        'cdlShort'   => $cdlShort,
        'urlCommissione' => (new AccessoProspetti($cdlShort))->getPercorsoCommissione(),
        'errori'     => $errori
    ]);

} catch (Throwable $e) {
    echo json_encode([
        'successo'  => false,
        'messaggio' => $e->getMessage()
    ]);
}
