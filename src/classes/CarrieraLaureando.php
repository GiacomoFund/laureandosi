<?php
require_once __DIR__ . '/EsameLaureando.php';
require_once __DIR__ . '/GestioneCarrieraStudente.php';
require_once __DIR__ . '/Configurazione.php';

/**
 * Classe Entity: CarrieraLaureando
 * Costruisce la carriera del laureando e calcola medie e crediti.
 *
 * Sotto-classi: CarrieraLaureandoInformatica
 */
class CarrieraLaureando {

    protected $nome;
    protected $cognome;
    protected $email;
    protected $matricola;
    protected $esami;
    protected $corsoDiLaurea;
    protected $dataLaurea;
    protected $configurazione;
    protected $carrieraRaw;       // Dati grezzi della carriera (per sottoclassi)

    private $mediaPesata;

    /**
     * Costruisce la carriera del laureando prelevando i dati
     * da GestioneCarrieraStudente e applicando i filtri da Configurazione.
     *
     * @param string $matricola    Numero di matricola
     * @param string $corsoDiLaurea Nome del CdL
     * @param string $dataLaurea   Data dell'appello (formato YYYY-MM-DD)
     * @param Configurazione|null $configurazione Configurazione gia' caricata (opzionale)
     */
    public function __construct($matricola, $corsoDiLaurea, $dataLaurea = '', $configurazione = null) {
        $this->matricola     = trim(strval($matricola));
        $this->corsoDiLaurea = $corsoDiLaurea;
        $this->dataLaurea    = $dataLaurea;
        $this->esami         = [];

        // Riusa configurazione passata o ne crea una nuova
        $this->configurazione = $configurazione ?? new Configurazione($corsoDiLaurea);

        // Preleva dati dal sistema esterno
        $gcs        = new GestioneCarrieraStudente();
        $anagrafica = $gcs->prelevaAnagrafica($matricola);
        $carriera   = $gcs->prelevaCarriera($matricola);
        $this->carrieraRaw = $carriera;  // Salva per le sottoclassi

        // Imposta dati anagrafici
        $this->nome    = $anagrafica['Entries']['Entry']['nome']      ?? '';
        $this->cognome = $anagrafica['Entries']['Entry']['cognome']   ?? '';
        $this->email   = $anagrafica['Entries']['Entry']['email_ate'] ?? '';

        // Costruisci lista esami applicando filtri
        $this->costruisciEsami($carriera);

        // Calcola media pesata
        $this->mediaPesata = $this->calcolaMediaPesata();
    }

    /**
     * Costruisce la lista degli esami dal JSON della carriera,
     * applicando i filtri dalla Configurazione.
     *
     * @param array $carriera Dati della carriera (dal JSON)
     */
    protected function costruisciEsami($carriera) {
        $esamiDaTogliere = $this->configurazione->getEsamiDaTogliere();
        $esamiNonMedia   = $this->configurazione->getEsamiNonMedia();
        $valoreLode      = $this->configurazione->getValoreLode();

        if (!isset($carriera['Esami']['Esame']) ||
            !is_array($carriera['Esami']['Esame'])) {
            return;
        }

        foreach ($carriera['Esami']['Esame'] as $esameRaw) {
            // Estrai campi con gestione del formato {"@nil":"true"}
            $nome   = (isset($esameRaw['DES']) && is_string($esameRaw['DES']))
                      ? trim($esameRaw['DES']) : '';
            $codice = (isset($esameRaw['COD']) && is_string($esameRaw['COD']))
                      ? trim($esameRaw['COD']) : '';
            $data   = (isset($esameRaw['DATA_ESAME']) && is_string($esameRaw['DATA_ESAME']))
                      ? trim($esameRaw['DATA_ESAME']) : '';

            // Salta esami senza nome o da escludere
            if (empty($nome) || in_array($nome, $esamiDaTogliere)) {
                continue;
            }

            // Gestione voto
            $votoRaw = $esameRaw['VOTO'] ?? null;
            $voto    = $this->parseVoto($votoRaw, $valoreLode);

            // CFU
            $cfuRaw = $esameRaw['PESO'] ?? 0;
            if (!is_numeric($cfuRaw) || intval($cfuRaw) <= 0) {
                continue; // salta esami senza CFU validi
            }
            $cfu = intval($cfuRaw);

            // Determina se l'esame fa media
            $faMedia = true;
            if ($voto === 0 || in_array($nome, $esamiNonMedia)) {
                $faMedia = false;
            }

            $esame = new EsameLaureando($nome, $codice, $voto, $cfu, $data, $faMedia, false);
            $this->esami[] = $esame;
        }
    }

    /**
     * Converte il voto grezzo in un intero, gestendo "30 e lode" e trim.
     *
     * @param  mixed $votoRaw    Voto dal JSON (stringa, null, int)
     * @param  float $valoreLode Valore numerico della lode
     * @return int   Voto convertito
     */
    protected function parseVoto($votoRaw, $valoreLode) {
        if ($votoRaw === null || is_array($votoRaw) || $votoRaw === 0 || $votoRaw === '0') {
            return 0;
        }

        $voto = trim(strval($votoRaw));

        if (empty($voto)) {
            return 0;
        }

        // Gestione "30 e lode" con confronto robusto
        if (stripos($voto, 'lode') !== false) {
            return intval($valoreLode);
        }

        return intval($voto);
    }

    /**
     * Applica il filtro esami dalla configurazione.
     */
    public function applicaFiltroEsami() {
        $filtri      = $this->configurazione->getFiltriEsami();
        $daTogliere  = $filtri['daTogliere'];
        $nonMedia    = $filtri['nonMedia'];

        // Rimuovi esami da escludere
        $this->esami = array_values(array_filter(
            $this->esami,
            function ($esame) use ($daTogliere) {
                return !in_array($esame->getNome(), $daTogliere);
            }
        ));

        // Aggiorna flag faMedia
        foreach ($this->esami as $esame) {
            if (in_array($esame->getNome(), $nonMedia)) {
                $esame->setFaMedia(false);
            }
        }
    }

    /**
     * Calcola la media pesata degli esami che fanno media.
     * Arrotondata a 3 cifre decimali.
     *
     * @return float Media pesata
     */
    public function calcolaMediaPesata() {
        $sommaVotoCfu = 0;
        $sommaCfu     = 0;

        foreach ($this->esami as $esame) {
            if ($esame->getFaMedia()) {
                $sommaVotoCfu += $esame->getVoto() * $esame->getCfu();
                $sommaCfu     += $esame->getCfu();
            }
        }

        if ($sommaCfu === 0) {
            return 0.0;
        }

        $this->mediaPesata = round($sommaVotoCfu / $sommaCfu, 3);
        return $this->mediaPesata;
    }

    /**
     * Calcola i crediti curricolari conseguiti.
     * Esclude PROVA FINALE e LIBERA SCELTA PER RICONOSCIMENTI.
     *
     * @return int Crediti conseguiti
     */
    public function calcolaCreditiConseguiti() {
        $crediti = 0;
        foreach ($this->esami as $esame) {
            $nome = $esame->getNome();
            if ($nome !== 'PROVA FINALE' &&
                $nome !== 'LIBERA SCELTA PER RICONOSCIMENTI') {
                $crediti += $esame->getCfu();
            }
        }
        return $crediti;
    }

    /**
     * Calcola i crediti che fanno media.
     *
     * @return int Crediti che contribuiscono al calcolo della media
     */
    public function calcolaCreditiMedia() {
        $crediti = 0;
        foreach ($this->esami as $esame) {
            if ($esame->getFaMedia()) {
                $crediti += $esame->getCfu();
            }
        }
        return $crediti;
    }

    /**
     * Calcola il voto di laurea usando la formula configurata.
     * Utilizza il valutatore sicuro di Configurazione (no eval).
     *
     * @param  float $T Voto della tesi
     * @param  float $C Voto della commissione
     * @return float Voto di laurea calcolato
     */
    public function calcolaVotoLaurea($T = 0, $C = 0) {
        $variabili = [
            'M'   => $this->mediaPesata,
            'T'   => $T,
            'C'   => $C,
            'CFU' => $this->calcolaCreditiMedia(),
        ];

        return Configurazione::valutaFormula(
            $this->configurazione->getFormula(),
            $variabili
        );
    }

    // ===================== Getters =====================

    public function getNome()           { return $this->nome; }
    public function getCognome()        { return $this->cognome; }
    public function getEmail()          { return $this->email; }
    public function getMatricola()      { return $this->matricola; }
    public function getEsami()          { return $this->esami; }
    public function getCorsoDiLaurea()  { return $this->corsoDiLaurea; }
    public function getDataLaurea()     { return $this->dataLaurea; }
    public function getConfigurazione() { return $this->configurazione; }
    public function getMediaPesata()    { return $this->mediaPesata; }
    public function getFormula()        { return $this->configurazione->getFormula(); }
}
