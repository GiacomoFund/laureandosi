<?php
/**
 * Classe Entity: Configurazione
 * Gestisce i parametri di configurazione per ogni Corso di Laurea.
 * Comprende formula di calcolo, filtri esami e lista esami informatici.
 *
 * Attributi:
 *  - formula: Formula per il calcolo del voto di laurea
 *  - valoreLode: Valore numerico da assegnare alla lode
 *  - esamiInformatici: Lista dei nomi degli esami informatici
 *  - esamiDaTogliere: Lista esami esclusi dal prospetto
 *  - esamiNonMedia: Lista esami visualizzati ma esclusi dalla media
 *  - parametriMail: Oggetto e testo della mail per il CdL
 *
 * Operazioni:
 *  + caricaConfigurazione(corsoDiLaurea): void
 *  + getEsamiInformatici(): Array
 *  + getFiltriEsami(): Array
 *  + getFormula(): String
 *  + getParametriSimulazione(): Array
 *  + getParametriMail(): Array
 *  + valutaFormula(formula, variabili): Float  [statico, sicuro]
 *  + salvaConfigurazione(): void
 */
class Configurazione {

    private $corsoDiLaurea;
    private $cdlShort;
    private $formula;
    private $valoreLode;
    private $cfuRichiesti;
    private $isInformatica;
    private $esamiInformatici;
    private $esamiDaTogliere;
    private $esamiNonMedia;
    private $parametriMail;
    private $parametriSimulazione;
    private $pediceCommissione;

    private $configPath;
    private $esamiInfoPath;

    /**
     * Costruttore: carica la configurazione per il CdL specificato.
     *
     * @param string $corsoDiLaurea Nome del Corso di Laurea
     */
    public function __construct($corsoDiLaurea) {
        $this->configPath    = __DIR__ . '/../config/formule_laurea.json';
        $this->esamiInfoPath = __DIR__ . '/../config/esami_informatici.json';
        $this->corsoDiLaurea = $corsoDiLaurea;
        $this->caricaConfigurazione($corsoDiLaurea);
    }

    /**
     * Carica la configurazione dal file JSON per il CdL indicato.
     *
     * @param  string $corsoDiLaurea Nome del Corso di Laurea
     * @throws RuntimeException Se il file non e' leggibile
     * @throws InvalidArgumentException Se il CdL non e' presente nel file
     */
    public function caricaConfigurazione($corsoDiLaurea) {
        $json = file_get_contents($this->configPath);
        if ($json === false) {
            throw new RuntimeException("Impossibile leggere il file di configurazione.");
        }

        $config = json_decode($json, true);
        if (!isset($config[$corsoDiLaurea])) {
            throw new InvalidArgumentException(
                "Corso di Laurea non trovato nella configurazione: " . $corsoDiLaurea
            );
        }

        $cdl = $config[$corsoDiLaurea];

        $this->cdlShort          = $cdl['cdl_short']         ?? '';
        $this->formula           = $cdl['formula']           ?? '';
        $this->cfuRichiesti      = $cdl['cfuRichiesti']      ?? 0;
        $this->valoreLode        = $cdl['valoreLode']        ?? 33;
        $this->isInformatica     = $cdl['informatica']       ?? false;
        $this->esamiDaTogliere   = $cdl['esamiDaTogliere']   ?? [];
        $this->esamiNonMedia     = $cdl['esamiNonMedia']     ?? [];
        $this->pediceCommissione = $cdl['pediceCommissione'] ?? '';

        $this->parametriSimulazione = [
            'Tmin'  => $cdl['Tmin']  ?? 0,
            'Tmax'  => $cdl['Tmax']  ?? 0,
            'Tstep' => $cdl['Tstep'] ?? 0,
            'Cmin'  => $cdl['Cmin']  ?? 0,
            'Cmax'  => $cdl['Cmax']  ?? 0,
            'Cstep' => $cdl['Cstep'] ?? 0,
        ];

        $this->parametriMail = [
            'oggetto' => $cdl['oggettoMail'] ?? '',
            'testo'   => $cdl['testoMail']   ?? '',
        ];

        // Carica esami informatici (file separato, condiviso fra tutti i CdL)
        $this->esamiInformatici = [];
        if (file_exists($this->esamiInfoPath)) {
            $jsonInfo   = file_get_contents($this->esamiInfoPath);
            $esamiInfo  = json_decode($jsonInfo, true);
            $this->esamiInformatici = $esamiInfo['nomi_esami'] ?? [];
        }
    }

    // ===================== Getters =====================

    public function getCorsoDiLaurea()       { return $this->corsoDiLaurea; }
    public function getCdlShort()            { return $this->cdlShort; }
    public function getFormula()             { return $this->formula; }
    public function getValoreLode()          { return $this->valoreLode; }
    public function getCfuRichiesti()        { return $this->cfuRichiesti; }
    public function isInformatica()          { return $this->isInformatica; }
    public function getEsamiInformatici()    { return $this->esamiInformatici; }
    public function getEsamiDaTogliere()     { return $this->esamiDaTogliere; }
    public function getEsamiNonMedia()       { return $this->esamiNonMedia; }
    public function getPediceCommissione()   { return $this->pediceCommissione; }
    public function getParametriSimulazione(){ return $this->parametriSimulazione; }
    public function getParametriMail()       { return $this->parametriMail; }

    /**
     * Restituisce i filtri esami aggregati.
     *
     * @return array ['daTogliere' => [...], 'nonMedia' => [...]]
     */
    public function getFiltriEsami() {
        return [
            'daTogliere' => $this->esamiDaTogliere,
            'nonMedia'   => $this->esamiNonMedia,
        ];
    }

    // ===================== Setters (pannello configurazione) =====================

    public function setFormula($formula)               { $this->formula = $formula; }
    public function setValoreLode($valore)              { $this->valoreLode = floatval($valore); }
    public function setCfuRichiesti($cfu)               { $this->cfuRichiesti = intval($cfu); }
    public function setEsamiInformatici($esami)          { $this->esamiInformatici = $esami; }
    public function setEsamiDaTogliere($esami)           { $this->esamiDaTogliere = $esami; }
    public function setEsamiNonMedia($esami)             { $this->esamiNonMedia = $esami; }
    public function setOggettoMail($oggetto)             { $this->parametriMail['oggetto'] = $oggetto; }
    public function setTestoMail($testo)                 { $this->parametriMail['testo'] = $testo; }
    public function setPediceCommissione($pedice)        { $this->pediceCommissione = $pedice; }
    
    public function setParametriSimulazione($Tmin, $Tmax, $Tstep, $Cmin, $Cmax, $Cstep) {
        $this->parametriSimulazione = [
            'Tmin'  => intval($Tmin),
            'Tmax'  => intval($Tmax),
            'Tstep' => intval($Tstep),
            'Cmin'  => intval($Cmin),
            'Cmax'  => intval($Cmax),
            'Cstep' => intval($Cstep),
        ];
    }

    // ===================== Salvataggio configurazione =====================

    /**
     * Salva la configurazione corrente nel file JSON.
     */
    public function salvaConfigurazione() {
        // Leggi configurazione completa
        $json   = file_get_contents($this->configPath);
        $config = json_decode($json, true);

        // Aggiorna la sezione del CdL corrente
        $config[$this->corsoDiLaurea]['formula']            = $this->formula;
        $config[$this->corsoDiLaurea]['cfuRichiesti']       = $this->cfuRichiesti;
        $config[$this->corsoDiLaurea]['valoreLode']         = $this->valoreLode;
        $config[$this->corsoDiLaurea]['informatica']        = $this->isInformatica;
        $config[$this->corsoDiLaurea]['esamiDaTogliere']    = $this->esamiDaTogliere;
        $config[$this->corsoDiLaurea]['esamiNonMedia']      = $this->esamiNonMedia;
        $config[$this->corsoDiLaurea]['Tmin']               = $this->parametriSimulazione['Tmin'];
        $config[$this->corsoDiLaurea]['Tmax']               = $this->parametriSimulazione['Tmax'];
        $config[$this->corsoDiLaurea]['Tstep']              = $this->parametriSimulazione['Tstep'];
        $config[$this->corsoDiLaurea]['Cmin']               = $this->parametriSimulazione['Cmin'];
        $config[$this->corsoDiLaurea]['Cmax']               = $this->parametriSimulazione['Cmax'];
        $config[$this->corsoDiLaurea]['Cstep']              = $this->parametriSimulazione['Cstep'];
        $config[$this->corsoDiLaurea]['oggettoMail']        = $this->parametriMail['oggetto'];
        $config[$this->corsoDiLaurea]['testoMail']          = $this->parametriMail['testo'];
        $config[$this->corsoDiLaurea]['pediceCommissione']  = $this->pediceCommissione;

        file_put_contents(
            $this->configPath,
            json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        // Salva esami informatici (file separato)
        $esamiData = ['nomi_esami' => $this->esamiInformatici];
        file_put_contents(
            $this->esamiInfoPath,
            json_encode($esamiData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    // ===================== Valutatore di formule sicuro =====================

    /**
     * Valuta in modo sicuro un'espressione matematica senza usare eval().
     * Utilizza un parser ricorsivo discendente che supporta:
     *   numeri (interi e decimali), +, -, *, /, parentesi.
     *
     * @param  string $formula   La formula (es. "$M * 3 + 18 + $T + $C")
     * @param  array  $variabili Associazione nome => valore (es. ['M'=>24, 'T'=>0, 'C'=>3])
     * @return float  Il risultato della valutazione
     * @throws InvalidArgumentException Se la formula contiene caratteri non ammessi
     */
    public static function valutaFormula($formula, $variabili) {
        // Sostituisci le variabili con i rispettivi valori numerici
        // IMPORTANTE: ordina per lunghezza decrescente per evitare sostituzioni parziali (es. $C dentro $CFU)
        uksort($variabili, function($a, $b) {
            return strlen($b) - strlen($a);
        });
        
        foreach ($variabili as $nome => $valore) {
            $formula = str_replace('$' . $nome, strval(floatval($valore)), $formula);
        }

        // Rimuovi spazi
        $formula = preg_replace('/\s+/', '', $formula);

        // Verifica che la formula contenga solo caratteri ammessi
        if (!preg_match('/^[\d\.\+\-\*\/\(\)]+$/', $formula)) {
            throw new InvalidArgumentException(
                "La formula contiene caratteri non ammessi: " . $formula
            );
        }

        // Valuta con parser ricorsivo discendente
        $pos    = 0;
        $result = self::parseExpression($formula, $pos);

        if ($pos < strlen($formula)) {
            throw new InvalidArgumentException(
                "Caratteri inattesi nella formula alla posizione " . $pos
            );
        }

        return $result;
    }

    // ---- Parser ricorsivo discendente per espressioni aritmetiche ----
    // Grammatica:
    //   expression = term (('+' | '-') term)*
    //   term       = factor (('*' | '/') factor)*
    //   factor     = ['-'] (NUMBER | '(' expression ')')

    private static function parseExpression(&$formula, &$pos) {
        $result = self::parseTerm($formula, $pos);

        while ($pos < strlen($formula) &&
               ($formula[$pos] === '+' || $formula[$pos] === '-')) {
            $op = $formula[$pos];
            $pos++;
            $term = self::parseTerm($formula, $pos);
            $result = ($op === '+') ? $result + $term : $result - $term;
        }

        return $result;
    }

    private static function parseTerm(&$formula, &$pos) {
        $result = self::parseFactor($formula, $pos);

        while ($pos < strlen($formula) &&
               ($formula[$pos] === '*' || $formula[$pos] === '/')) {
            $op = $formula[$pos];
            $pos++;
            $factor = self::parseFactor($formula, $pos);

            if ($op === '*') {
                $result *= $factor;
            } else {
                if ($factor == 0) {
                    throw new InvalidArgumentException("Divisione per zero nella formula.");
                }
                $result /= $factor;
            }
        }

        return $result;
    }

    private static function parseFactor(&$formula, &$pos) {
        // Gestione segno negativo unario
        $negative = false;
        if ($pos < strlen($formula) && $formula[$pos] === '-') {
            $negative = true;
            $pos++;
        }

        $result = 0;

        if ($pos < strlen($formula) && $formula[$pos] === '(') {
            // Sotto-espressione fra parentesi
            $pos++; // salta '('
            $result = self::parseExpression($formula, $pos);
            if ($pos >= strlen($formula) || $formula[$pos] !== ')') {
                throw new InvalidArgumentException("Parentesi non chiusa nella formula.");
            }
            $pos++; // salta ')'
        } else {
            // Numero
            $result = self::parseNumber($formula, $pos);
        }

        return $negative ? -$result : $result;
    }

    private static function parseNumber(&$formula, &$pos) {
        $start = $pos;
        while ($pos < strlen($formula) &&
               (ctype_digit($formula[$pos]) || $formula[$pos] === '.')) {
            $pos++;
        }

        if ($start === $pos) {
            throw new InvalidArgumentException(
                "Numero atteso alla posizione " . $pos . " nella formula."
            );
        }

        return floatval(substr($formula, $start, $pos - $start));
    }
}
