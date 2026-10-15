<?php
require_once __DIR__ . '/ProspettoPDFLaureando.php';
require_once __DIR__ . '/ProspettoPDFCommissione.php';
require_once __DIR__ . '/AccessoProspetti.php';
require_once __DIR__ . '/Configurazione.php';

/**
 * Classe Control: GestoreProspetti
 * Coordina la generazione di tutti i prospetti per un appello di laurea.
 *
 * Attributi:
 *  - prospettiGenerati: Lista dei prospetti PDF generati
 *
 * Operazioni:
 *  + generaProspettiLaureandi(): void
 *  + generaProspettoCommissione(): void
 */
class GestoreProspetti {

    private $matricole;
    private $corsoDiLaurea;
    private $dataLaurea;
    private $prospettiGenerati;
    private $errori;
    private $configurazione;
    private $prospettiLaureandi;

    /**
     * Costruttore.
     * Crea la Configurazione una sola volta e la condivide con tutti gli oggetti.
     *
     * @param array  $matricole     Array di numeri di matricola
     * @param string $corsoDiLaurea Nome del CdL
     * @param string $dataLaurea    Data appello di laurea
     */
    public function __construct(array $matricole, $corsoDiLaurea, $dataLaurea) {
        $this->matricole           = $matricole;
        $this->corsoDiLaurea       = $corsoDiLaurea;
        $this->dataLaurea          = $dataLaurea;
        $this->prospettiGenerati   = [];
        $this->errori              = [];
        $this->configurazione      = new Configurazione($corsoDiLaurea);
        $this->prospettiLaureandi  = [];
    }

    /**
     * Genera i prospetti PDF individuali per ogni laureando.
     * Usa ProspettoPDFLaureando (solo carriera, senza simulazione).
     * Gli oggetti vengono conservati per riutilizzare la carriera caricata
     * nella successiva generazione del prospetto commissione.
     */
    public function generaProspettiLaureandi() {
        foreach ($this->matricole as $matricola) {
            try {
                $prospetto = new ProspettoPDFLaureando(
                    $matricola, $this->corsoDiLaurea, $this->dataLaurea, $this->configurazione
                );
                $prospetto->salvaProspetto();
                $this->prospettiGenerati[]  = $matricola;
                $this->prospettiLaureandi[] = $prospetto;
            } catch (Exception $e) {
                $this->errori[] = [
                    'matricola' => $matricola,
                    'errore'    => $e->getMessage()
                ];
            }
        }
    }

    /**
     * Genera il prospetto PDF unico per la commissione.
     * Passa le carriere già caricate a ProspettoPDFCommissione,
     * che internamente crea i ProspettoConSimulazione.
     */
    public function generaProspettoCommissione() {
        // Raccoglie matricole e carriere dai ProspettoPDFLaureando già creati
        $matricole = [];
        $carriere  = [];
        foreach ($this->prospettiLaureandi as $laureando) {
            $matricole[] = $laureando->getMatricola();
            $carriere[]  = $laureando->getCarriera();
        }

        $commissione = new ProspettoPDFCommissione(
            $matricole, $this->corsoDiLaurea, $this->dataLaurea, $this->configurazione, $carriere
        );
        $commissione->creaPDFCommissione();
    }

    /**
     * Salva i dati dell'appello corrente per l'invio email successivo.
     * Sostituisce i vecchi file ausiliario.json, ausiliario2.json, ausiliario3.json
     * con un unico file strutturato (eliminazione ridondanza, problema #6).
     */
    public function salvaDatiPerInvio() {
        $datiInvio = [
            'matricole'     => $this->matricole,
            'corsoDiLaurea' => $this->corsoDiLaurea,
            'dataLaurea'    => $this->dataLaurea
        ];
        $percorso = __DIR__ . '/../data/dati_invio.json';
        file_put_contents(
            $percorso,
            json_encode($datiInvio, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    /**
     * Restituisce la lista delle matricole per cui il prospetto e' stato generato.
     *
     * @return array
     */
    public function getProspettiGenerati() {
        return $this->prospettiGenerati;
    }

    /**
     * Restituisce la lista degli errori verificatisi durante la generazione.
     *
     * @return array
     */
    public function getErrori() {
        return $this->errori;
    }
}
