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
            require_once __DIR__ . '/classi/GestoreProspetti.php';
            
            $cdl = trim($_POST['cdl'] ?? '');
            $dataLaurea = trim($_POST['data_laurea'] ?? '');
            $matricoleRaw = trim($_POST['matricole'] ?? '');
            
            if (empty($cdl) || empty($dataLaurea) || empty($matricoleRaw)) {
                throw new InvalidArgumentException("Parametri mancanti: cdl, data_laurea, matricole.");
            }
            
            // Parsing matricole
            $matricole = preg_split('/[\s,]+/', $matricoleRaw, -1, PREG_SPLIT_NO_EMPTY);
            $matricoleValide = array_filter($matricole, fn($m) => preg_match('/^\d+$/', trim($m)));
            
            if (empty($matricoleValide)) {
                throw new InvalidArgumentException("Nessuna matricola valida.");
            }
            
            $gestore = new GestoreProspetti($matricoleValide, $cdl, $dataLaurea);
            $gestore->generaProspettiLaureandi();
            $gestore->generaProspettoCommissione();
            $gestore->salvaDatiPerInvio();
            
            $generati = $gestore->getProspettiGenerati();
            $errori = $gestore->getErrori();
            
            $messaggio = "Prospetti generati: " . count($generati) . " su " . count($matricoleValide);
            if (!empty($errori)) {
                $messaggio .= ". Errori: " . count($errori);
            }
            
            echo json_encode([
                'successo' => true,
                'messaggio' => $messaggio,
                'generati' => count($generati),
                'errori' => $errori
            ]);
            break;
        
        // ===== INVIA PROSPETTI (UC3) =====
        case 'invia_prospetti':
            require_once __DIR__ . '/classi/GestoreInviiEmail.php';
            
            $gestore = new GestoreInviiEmail();
            $risultati = $gestore->inviaProspetti();
            
            $inviati = count(array_filter($risultati, fn($r) => $r['esito'] === 'OK'));
            $errori = count($risultati) - $inviati;
            
            echo json_encode([
                'successo' => ($errori === 0),
                'messaggio' => "Prospetti inviati: $inviati su " . count($risultati),
                'inviati' => $inviati,
                'errori' => $errori,
                'dettagli' => $risultati
            ]);
            break;
        
        // ===== VISUALIZZA PROSPETTI (UC2) =====
        case 'visualizza_prospetti':
            require_once __DIR__ . '/classi/AccessoProspetti.php';
            require_once __DIR__ . '/classi/Configurazione.php';
            
            try {
                // Validazione: verifica che dati_invio.json esista e sia valido
                $datiInvioPath = __DIR__ . '/data/dati_invio.json';
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
                
                $percorsi = $accesso->getPercorsiPDF();
                
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
            } catch (Exception $e) {
                echo json_encode([
                    'successo' => false,
                    'messaggio' => $e->getMessage()
                ]);
            }
            break;
        
        // ===== SALVA CONFIGURAZIONE =====
        case 'salva_configurazione':
            require_once __DIR__ . '/classi/Configurazione.php';
            
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
            $cartellaBase = __DIR__ . '/data/pdf_generati/';
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
            $datiInvioPath = __DIR__ . '/data/dati_invio.json';
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
    
} catch (Exception $e) {
    echo json_encode([
        'successo' => false,
        'errore' => $e->getMessage()
    ]);
}
