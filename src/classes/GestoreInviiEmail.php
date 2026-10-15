<?php
require_once __DIR__ . '/AccessoProspetti.php';
require_once __DIR__ . '/GestioneCarrieraStudente.php';
require_once __DIR__ . '/Configurazione.php';

require_once __DIR__ . '/../lib/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../lib/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/../lib/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

/**
 * Classe Control: GestoreInviiEmail
 * Gestisce l'invio dei prospetti via email ai laureandi.
 * Utilizza PHPMailer per la costruzione e l'invio delle email con allegati.
 */
class GestoreInviiEmail {

    private $matricole;
    private $corsoDiLaurea;
    private $dataLaurea;
    private $configurazione;
    private $mailer;

    /**
     * Costruttore: carica i dati dell'invio dal file generato da GestoreProspetti.
     * Se non vengono forniti parametri, li legge dal file dati_invio.json.
     *
     * @param array|null  $matricole     Array di matricole (opzionale)
     * @param string|null $corsoDiLaurea Nome del CdL (opzionale)
     * @param string|null $dataLaurea    Data appello (opzionale)
     */
    public function __construct($matricole = null, $corsoDiLaurea = null, $dataLaurea = null) {
        if ($matricole !== null && $corsoDiLaurea !== null) {
            $this->matricole     = $matricole;
            $this->corsoDiLaurea = $corsoDiLaurea;
            $this->dataLaurea    = $dataLaurea ?? '';
        } else {
            // Carica da file dati_invio.json
            $percorso = AccessoProspetti::getPercorsoDatiInvio();
            if (!file_exists($percorso)) {
                throw new RuntimeException(
                    "File dati invio non trovato. Generare prima i prospetti."
                );
            }
            $dati = json_decode(file_get_contents($percorso), true);
            $this->matricole     = $dati['matricole']     ?? [];
            $this->corsoDiLaurea = $dati['corsoDiLaurea'] ?? '';
            $this->dataLaurea    = $dati['dataLaurea']    ?? '';
        }

        if (!empty($this->corsoDiLaurea)) {
            $this->configurazione = new Configurazione($this->corsoDiLaurea);
        }

        // Configura PHPMailer una sola volta e lo riusa per tutti gli invii
        $this->mailer = new PHPMailer(true);
        $this->configuraMailer();
    }

    /**
     * Configura l'istanza PHPMailer con i parametri di base.
     * Usa isMail() per l'invio tramite la funzione mail() di PHP.
     */
    private function configuraMailer() {
        $this->mailer->isMail();
        $this->mailer->From     = 'no-reply-laureandosi@ing.unipi.it';
        $this->mailer->FromName = 'Laureandosi - DII UniPi';
        $this->mailer->CharSet  = 'UTF-8';
        $this->mailer->isHTML(false);
    }

    /**
     * Invia i prospetti via email ai laureandi.
     * Rispetta il rate limiting di 1 mail ogni 3 secondi.
     *
     * @return array Lista di risultati per ogni matricola
     */
    public function inviaProspetti() {
        $cdlShort   = $this->configurazione->getCdlShort();
        $accesso    = new AccessoProspetti($cdlShort);

        // Validazione: verifica che esista almeno il prospetto della commissione
        if (!$accesso->esisteProspettoCommissione()) {
            throw new RuntimeException(
                "Nessun prospetto della commissione trovato. Generare i prospetti prima di inviarli."
            );
        }

        $gcs        = new GestioneCarrieraStudente();
        $params     = $this->configurazione->getParametriMail();
        $risultati  = [];

        foreach ($this->matricole as $indice => $matricola) {
            try {
                // Recupera email dal sistema esterno
                $anagrafica = $gcs->prelevaAnagrafica($matricola);
                $email = $anagrafica['Entries']['Entry']['email_ate'] ?? '';

                if (empty($email)) {
                    $risultati[] = [
                        'matricola' => $matricola,
                        'esito'     => 'ERRORE',
                        'messaggio' => 'Email mancante'
                    ];
                    continue;
                }

                // Verifica che il prospetto esista
                $percorsoPDF = $accesso->getPercorsoLaureandoAssoluto($matricola);
                if (!file_exists($percorsoPDF)) {
                    $risultati[] = [
                        'matricola' => $matricola,
                        'esito'     => 'ERRORE',
                        'messaggio' => 'Prospetto non trovato'
                    ];
                    continue;
                }

                // Invia email tramite PHPMailer
                $esito = $this->inviaEmail(
                    $email,
                    $params['oggetto'],
                    $params['testo'],
                    $percorsoPDF
                );

                $risultati[] = [
                    'matricola' => $matricola,
                    'esito'     => $esito ? 'OK' : 'ERRORE',
                    'messaggio' => $esito ? 'Email inviata' : 'Errore nell\'invio'
                ];

            } catch (Exception $e) {
                $risultati[] = [
                    'matricola' => $matricola,
                    'esito'     => 'ERRORE',
                    'messaggio' => $e->getMessage()
                ];
            }

            // Rate limiting: 3 secondi tra un invio e l'altro
            if ($indice < count($this->matricole) - 1) {
                sleep(3);
            }
        }

        return $risultati;
    }

    /**
     * Invia una singola email con il prospetto allegato tramite PHPMailer.
     *
     * @param  string $destinatario Indirizzo email del laureando
     * @param  string $oggetto      Oggetto della mail
     * @param  string $testo        Corpo della mail
     * @param  string $allegato     Percorso del file PDF da allegare
     * @return bool   True se l'invio ha avuto successo
     */
    private function inviaEmail($destinatario, $oggetto, $testo, $allegato) {
        // Validazione input
        if (!filter_var($destinatario, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        try {
            $this->mailer->Subject = $oggetto;
            $this->mailer->Body    = $testo;
            $this->mailer->addAddress($destinatario);

            if (file_exists($allegato)) {
                $this->mailer->addAttachment($allegato);
            }

            $this->mailer->send();
            return true;

        } catch (PHPMailerException $e) {
            error_log("Errore invio email a '$destinatario': " . $e->getMessage());
            return false;
        } finally {
            // Pulisci destinatari e allegati per il prossimo invio
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
        }
    }
}
