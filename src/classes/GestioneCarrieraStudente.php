<?php
/**
 * Classe Boundary: GestioneCarrieraStudente
 * Interfaccia verso il sistema di gestione carriere di ateneo.
 * Simula l'accesso al sistema esterno tramite file JSON locali.
 */
class GestioneCarrieraStudente {

    private $dataPath;

    public function __construct() {
        $this->dataPath = __DIR__ . '/../data/';
    }

    /**
     * Preleva l'anagrafica dello studente dal sistema esterno.
     *
     * @param  string $matricola Numero di matricola
     * @return array  Dati anagrafici dello studente
     * @throws RuntimeException Se il file non viene trovato o non e' valido
     * @throws InvalidArgumentException Se la matricola non e' valida
     */
    public function prelevaAnagrafica($matricola) {
        $matricola = $this->validaMatricola($matricola);
        $percorso = $this->dataPath . $matricola . '_anagrafica.json';

        if (!file_exists($percorso)) {
            throw new RuntimeException(
                "File anagrafica non trovato per la matricola: " . $matricola
            );
        }

        $json = file_get_contents($percorso);
        if ($json === false) {
            throw new RuntimeException(
                "Impossibile leggere l'anagrafica per la matricola: " . $matricola
            );
        }

        $dati = json_decode($json, true);
        if ($dati === null) {
            throw new RuntimeException(
                "Formato JSON anagrafica non valido per la matricola: " . $matricola
            );
        }

        return $dati;
    }

    /**
     * Preleva la carriera dello studente dal sistema esterno.
     *
     * @param  string $matricola Numero di matricola
     * @return array  Dati della carriera (lista esami)
     * @throws RuntimeException Se il file non viene trovato o non e' valido
     * @throws InvalidArgumentException Se la matricola non e' valida
     */
    public function prelevaCarriera($matricola) {
        $matricola = $this->validaMatricola($matricola);
        $percorso = $this->dataPath . $matricola . '_esami.json';

        if (!file_exists($percorso)) {
            throw new RuntimeException(
                "File carriera non trovato per la matricola: " . $matricola
            );
        }

        $json = file_get_contents($percorso);
        if ($json === false) {
            throw new RuntimeException(
                "Impossibile leggere la carriera per la matricola: " . $matricola
            );
        }

        $dati = json_decode($json, true);
        if ($dati === null) {
            throw new RuntimeException(
                "Formato JSON carriera non valido per la matricola: " . $matricola
            );
        }

        return $dati;
    }

    /**
     * Valida il formato della matricola (solo cifre).
     *
     * @param  mixed  $matricola
     * @return string Matricola validata e pulita
     * @throws InvalidArgumentException Se il formato non e' valido
     */
    private function validaMatricola($matricola) {
        $matricola = trim(strval($matricola));
        if (!preg_match('/^\d+$/', $matricola)) {
            throw new InvalidArgumentException(
                "Matricola non valida: " . $matricola
            );
        }
        return $matricola;
    }
}
