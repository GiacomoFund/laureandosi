<?php
/**
 * API.php
 * Endpoint centralizzato per tutte le operazioni del sistema Laureandosi.
 * Gestisce le richieste POST per generazione, invio e configurazione.
 *
 * Azioni supportate:
 *  - genera_prospetti: Genera prospetti per laureandi
 *  - invia_prospetti: Invia prospetti via email
 *  - salva_configurazione: Modifica configurazione CdL
 *  - visualizza_prospetti: Restituisce URL dei PDF generati
 */

header('Content-Type: application/json; charset=utf-8');

// Solo richieste POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'successo' => false,
        'errore' => 'Metodo non consentito. Utilizzare POST.'
    ]);
    exit;
}

// Leggi azione
$azione = $_POST['azione'] ?? '';

if (empty($azione)) {
    echo json_encode([
        'successo' => false,
        'errore' => 'Parametro "azione" mancante.'
    ]);
    exit;
}

try {
    switch ($azione) {

        // ===== GENERA PROSPETTI (UC1) =====
        case 'genera_prospetti':
            require __DIR__ . '/generaProspetti.php';
            break;

        case 'invia_prospetti':
            require __DIR__ . '/inviaProspetti.php';
            break;

        // ===== VISUALIZZA PROSPETTI (UC2) =====
        case 'visualizza_prospetti':
            require_once __DIR__ . '/classes/AccessoProspetti.php';
            require_once __DIR__ . '/classes/Configurazione.php';

            try {
                // Validazione: verifica che dati_invio.json esista e sia valido
                $datiInvioPath = AccessoProspetti::getPercorsoDatiInvio();
                if (!file_exists($datiInvioPath)) {
                    throw new RuntimeException("Nessun prospetto generato. Generare i prospetti prima di visualizzarli.");
                }

                $datiInvio = json_decode(file_get_contents($datiInvioPath), true);
                if (!$datiInvio || !isset($datiInvio['corsoDiLaurea'])) {
                    throw new RuntimeException("Dati invio corrotti. Rigenerare i prospetti.");
                }

                $config = new Configurazione($datiInvio['corsoDiLaurea']);
                $cdlShort = $config->getCdlShort();
                $accesso = new AccessoProspetti($cdlShort);

                // Verifica che il prospetto esista effettivamente
                if (!$accesso->esisteProspettoCommissione()) {
                    throw new RuntimeException("Nessun prospetto generato per il Corso di Laurea selezionato.");
                }

                $percorsi = array_map(
                    fn($file) => $accesso->getPercorsoLaureando(basename($file, '-prospetto.pdf')),
                    array_filter($accesso->getPercorsiPDF(), fn($file) => basename($file) !== 'prospettoCommissione.pdf')
                );
                $percorsi = array_values($percorsi);

                echo json_encode([
                    'successo' => true,
                    'prospetti' => [
                        'commissione' => [
                            'url' => $accesso->getPercorsoCommissione(),
                            'esiste' => true
                        ],
                        'laureandi' => $percorsi
                    ]
                ]);
            } catch (Throwable $e) {
                echo json_encode([
                    'successo' => false,
                    'messaggio' => $e->getMessage()
                ]);
            }
            break;

        // ===== SALVA CONFIGURAZIONE =====
        case 'salva_configurazione':
            require_once __DIR__ . '/classes/Configurazione.php';

            $cdl = trim($_POST['cdl'] ?? '');
            if (empty($cdl)) {
                throw new InvalidArgumentException("Parametro 'cdl' mancante.");
            }

            $config = new Configurazione($cdl);

            if (isset($_POST['formula'])) {
                $config->setFormula(trim($_POST['formula']));
            }
            if (isset($_POST['valore_lode'])) {
                $config->setValoreLode(floatval($_POST['valore_lode']));
            }
            if (isset($_POST['oggetto_mail'])) {
                $config->setOggettoMail(trim($_POST['oggetto_mail']));
            }
            if (isset($_POST['testo_mail'])) {
                $config->setTestoMail(trim($_POST['testo_mail']));
            }

            $config->salvaConfigurazione();

            echo json_encode([
                'successo' => true,
                'messaggio' => 'Configurazione salvata con successo.'
            ]);
            break;

        // ===== ELIMINA DATI GENERATI =====
        case 'elimina_dati':
            require_once __DIR__ . '/classes/AccessoProspetti.php';
            $cartellaBase = (new AccessoProspetti())->getCartellaOutput();
            $eliminati = 0;

            // Elimina tutti i PDF nelle sottocartelle CdL
            $sottocartelle = glob($cartellaBase . '*', GLOB_ONLYDIR);
            foreach ($sottocartelle as $cartella) {
                $files = glob($cartella . '/*.pdf');
                foreach ($files as $file) {
                    if (unlink($file)) {
                        $eliminati++;
                    }
                }
                // Rimuovi la sottocartella se vuota
                @rmdir($cartella);
            }

            // Elimina anche eventuali PDF nella cartella base
            $filesBase = glob($cartellaBase . '*.pdf');
            foreach ($filesBase as $file) {
                if (unlink($file)) {
                    $eliminati++;
                }
            }

            // Elimina il file dati_invio.json
            $datiInvioPath = AccessoProspetti::getPercorsoDatiInvio();
            if (file_exists($datiInvioPath)) {
                unlink($datiInvioPath);
            }

            echo json_encode([
                'successo' => true,
                'messaggio' => "Eliminati $eliminati file PDF e dati temporanei."
            ]);
            break;

        // ===== AZIONE NON RICONOSCIUTA =====
        default:
            echo json_encode([
                'successo' => false,
                'errore' => "Azione non riconosciuta: $azione"
            ]);
            break;
    }

} catch (Throwable $e) {
    echo json_encode([
        'successo' => false,
        'errore' => $e->getMessage()
    ]);
}
