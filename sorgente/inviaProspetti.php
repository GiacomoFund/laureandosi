<?php
/**
 * inviaProspetti.php
 * Endpoint per l'invio dei prospetti via email (UC3).
 * Riceve una richiesta POST e restituisce una risposta JSON.
 *
 * Parametri POST (opzionali):
 *  - matricole[]: Array di matricole selezionate. Se assente, invia a tutte.
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

require_once __DIR__ . '/classi/GestoreInviiEmail.php';

try {
    // Se l'utente ha selezionato matricole specifiche, filtra
    $matricoleSelezionate = isset($_POST['matricole']) ? $_POST['matricole'] : null;

    if ($matricoleSelezionate !== null && !is_array($matricoleSelezionate)) {
        throw new InvalidArgumentException('Il parametro matricole deve essere un array.');
    }

    if ($matricoleSelezionate !== null) {
        // Valida: solo cifre
        $matricoleSelezionate = array_filter($matricoleSelezionate, function($m) {
            return is_string($m) && preg_match('/^\d+$/', trim($m));
        });
        $matricoleSelezionate = array_values($matricoleSelezionate);

        if (empty($matricoleSelezionate)) {
            throw new InvalidArgumentException("Nessuna matricola valida selezionata.");
        }

        // Carica CdL e data dal file dati_invio.json
        $percorsoDati = AccessoProspetti::getPercorsoDatiInvio();
        if (!file_exists($percorsoDati)) {
            throw new RuntimeException("File dati invio non trovato. Generare prima i prospetti.");
        }
        $dati = json_decode(file_get_contents($percorsoDati), true);

        $gestore = new GestoreInviiEmail(
            $matricoleSelezionate,
            $dati['corsoDiLaurea'] ?? '',
            $dati['dataLaurea'] ?? ''
        );
    } else {
        // Nessuna selezione: invia a tutte le matricole
        $gestore = new GestoreInviiEmail();
    }

    $risultati = $gestore->inviaProspetti();

    $inviati = 0;
    $errori  = 0;
    $dettagli = [];

    foreach ($risultati as $r) {
        if ($r['esito'] === 'OK') {
            $inviati++;
        } else {
            $errori++;
        }
        $dettagli[] = $r;
    }

    $messaggio = "Prospetti inviati: " . $inviati . " su " . count($risultati) . ".";
    if ($errori > 0) {
        $messaggio .= " Errori: " . $errori . ".";
    }

    echo json_encode([
        'successo'  => ($errori === 0),
        'messaggio' => $messaggio,
        'inviati'   => $inviati,
        'errori'    => $errori,
        'dettagli'  => $dettagli
    ]);

} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'successo'  => false,
        'messaggio' => $e->getMessage(),
        'dettagli'  => []
    ]);
}
