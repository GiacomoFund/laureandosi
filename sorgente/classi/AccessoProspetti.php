<?php
/**
 * Classe Boundary: AccessoProspetti
 * Gestisce i percorsi dei file PDF generati sul server.
 */
class AccessoProspetti {

    private $basePath;
    private $baseUrl;
    private $cdlShort;

    public function __construct($cdlShort = '') {
        $this->cdlShort = $cdlShort;
        $app = require __DIR__ . '/../config/app.php';
        $sottocartella = $cdlShort === '' ? '' : $cdlShort . '/';
        $this->basePath = $app['runtime_dir'] . '/pdf_generati/' . $sottocartella;
        $this->baseUrl = $app['base_url'] . '/data/pdf_generati/' . $sottocartella;
    }

    public static function getPercorsoDatiInvio() {
        $app = require __DIR__ . '/../config/app.php';
        return $app['runtime_dir'] . '/dati_invio.json';
    }

    /**
     * Restituisce il percorso relativo (URL) del prospetto commissione.
     *
     * @return string Percorso relativo per il browser
     */
    public function getPercorsoCommissione() {
        return $this->baseUrl . 'prospettoCommissione.pdf';
    }

    /**
     * Restituisce il percorso assoluto del prospetto commissione.
     *
     * @return string Percorso assoluto su filesystem
     */
    public function getPercorsoCommissioneAssoluto() {
        return $this->basePath . 'prospettoCommissione.pdf';
    }

    /**
     * Restituisce il percorso relativo (URL) del prospetto del laureando.
     *
     * @param  string $matricola Numero di matricola
     * @return string Percorso relativo per il browser
     */
    public function getPercorsoLaureando($matricola) {
        return $this->baseUrl . $matricola . '-prospetto.pdf';
    }

    /**
     * Restituisce il percorso assoluto del prospetto del laureando.
     *
     * @param  string $matricola Numero di matricola
     * @return string Percorso assoluto su filesystem
     */
    public function getPercorsoLaureandoAssoluto($matricola) {
        return $this->basePath . $matricola . '-prospetto.pdf';
    }

    /**
     * Restituisce la lista dei percorsi di tutti i PDF generati.
     *
     * @return array Lista di percorsi assoluti dei file PDF
     */
    public function getPercorsiPDF() {
        $files = glob($this->basePath . '*.pdf');
        return ($files !== false) ? $files : [];
    }

    /**
     * Verifica se esiste il prospetto della commissione.
     *
     * @return bool True se il file esiste
     */
    public function esisteProspettoCommissione() {
        return file_exists($this->basePath . 'prospettoCommissione.pdf');
    }

    /**
     * Verifica se esiste il prospetto di un laureando.
     *
     * @param  string $matricola Numero di matricola
     * @return bool   True se il file esiste
     */
    public function esisteProspettoLaureando($matricola) {
        return file_exists($this->basePath . $matricola . '-prospetto.pdf');
    }

    /**
     * Restituisce il percorso assoluto della cartella PDF.
     *
     * @return string Percorso della cartella
     */
    public function getCartellaOutput() {
        return $this->basePath;
    }
}
