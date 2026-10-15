<?php
require_once __DIR__ . '/CarrieraLaureando.php';

/**
 * Classe Entity: CarrieraLaureandoInformatica
 * Estende CarrieraLaureando con logica specifica per Ing. Informatica.
 *
 * Super-classe: CarrieraLaureando
 *
 * Attributi:
 *  + idoneitaBonus: Boolean
 *  - annoImmatricolazione: Integer
 *
 * Operazioni:
 *  + verificaIdoneitaBonus(): Boolean
 *  + selezionaEsameDaEscludere(): void
 *  + calcolaMediaInformatici(): Float
 */
class CarrieraLaureandoInformatica extends CarrieraLaureando {

    private $idoneitaBonus;
    private $annoImmatricolazione;
    private $mediaEsamiInformatici;

    /**
     * Costruttore: costruisce la carriera informatica e applica
     * bonus e marcatura esami informatici.
     *
     * @param string $matricola     Numero di matricola
     * @param string $corsoDiLaurea Nome del CdL
     * @param string $dataLaurea    Data dell'appello (formato YYYY-MM-DD)
     * @param Configurazione|null $configurazione Configurazione gia' caricata (opzionale)
     */
    public function __construct($matricola, $corsoDiLaurea, $dataLaurea, $configurazione = null) {
        parent::__construct($matricola, $corsoDiLaurea, $dataLaurea, $configurazione);

        $this->idoneitaBonus = false;

        // Recupera anno immatricolazione dai dati già prelevati dal padre
        if (isset($this->carrieraRaw['Esami']['Esame'][0]['ANNO_IMM'])) {
            $this->annoImmatricolazione = intval($this->carrieraRaw['Esami']['Esame'][0]['ANNO_IMM']);
        } else {
            $this->annoImmatricolazione = 0;
        }

        // Verifica idoneita' al bonus e applicalo se idoneo
        $this->verificaIdoneitaBonus();
        if ($this->idoneitaBonus) {
            $this->selezionaEsameDaEscludere();
        }

        // Marca esami informatici
        $esamiInformatici = $this->configurazione->getEsamiInformatici();
        foreach ($this->esami as $esame) {
            if (in_array($esame->getNome(), $esamiInformatici)) {
                $esame->setInformatico(true);
            }
        }

        // Ricalcola media dopo eventuale esclusione dal bonus
        $this->calcolaMediaPesata();

        // Calcola media specifica esami informatici
        $this->mediaEsamiInformatici = $this->calcolaMediaInformatici();
    }

    /**
     * Verifica se il laureando ha diritto al bonus informatica.
     * Il bonus si applica se lo studente si laurea entro 4 anni
     * dall'immatricolazione (entro il 1 maggio del quarto anno).
     *
     * @return bool True se il laureando ha diritto al bonus
     */
    public function verificaIdoneitaBonus() {
        if ($this->annoImmatricolazione <= 0 || empty($this->dataLaurea)) {
            $this->idoneitaBonus = false;
            return false;
        }

        $fineBonus = ($this->annoImmatricolazione + 4) . '-05-01';
        $this->idoneitaBonus = ($this->dataLaurea < $fineBonus);
        return $this->idoneitaBonus;
    }

    /**
     * Seleziona l'esame con il voto piu' basso tra quelli che fanno media
     * e lo esclude dal calcolo della media (bonus informatica).
     */
    public function selezionaEsameDaEscludere() {
        $votoMinimo   = PHP_INT_MAX;
        $indiceMinimo = -1;

        foreach ($this->esami as $indice => $esame) {
            if ($esame->getFaMedia() && $esame->getVoto() < $votoMinimo) {
                $votoMinimo   = $esame->getVoto();
                $indiceMinimo = $indice;
            }
        }

        if ($indiceMinimo >= 0) {
            $this->esami[$indiceMinimo]->setFaMedia(false);
        }
    }

    /**
     * Calcola la media pesata degli esami informatici.
     * Considera solo gli esami marcati come informatici e che fanno media.
     *
     * @return float Media pesata esami informatici, arrotondata a 3 cifre
     */
    public function calcolaMediaInformatici() {
        $sommaVotoCfu = 0;
        $sommaCfu     = 0;

        foreach ($this->esami as $esame) {
            if ($esame->getInformatico() && $esame->getFaMedia()) {
                $sommaVotoCfu += $esame->getVoto() * $esame->getCfu();
                $sommaCfu     += $esame->getCfu();
            }
        }

        if ($sommaCfu === 0) {
            return 0.0;
        }

        return round($sommaVotoCfu / $sommaCfu, 3);
    }

    // ===================== Getters =====================

    public function getIdoneitaBonus()          { return $this->idoneitaBonus; }
    public function getBonusLabel()             { return $this->idoneitaBonus ? 'SI' : 'NO'; }
    public function getAnnoImmatricolazione()   { return $this->annoImmatricolazione; }
    public function getMediaEsamiInformatici()  { return $this->mediaEsamiInformatici; }
}
