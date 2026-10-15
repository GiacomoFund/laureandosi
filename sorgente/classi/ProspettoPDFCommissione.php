<?php
require_once __DIR__ . '/ProspettoConSimulazione.php';
require_once __DIR__ . '/AccessoProspetti.php';
require_once __DIR__ . '/Configurazione.php';
require_once __DIR__ . '/../lib/fpdf184/fpdf.php';

/**
 * Classe Control: ProspettoPDFCommissione
 * Genera il PDF riepilogativo per la commissione di laurea.
 *
 * Attributi:
 *  + prospettoCommissione: FPDF
 *  + carriere: Array<CarrieraLaureando>
 *  + corsoDiLaurea: String
 *
 * Operazioni:
 *  + creaPDFCommissione(): FPDF
 */
class ProspettoPDFCommissione {

    private $matricole;
    private $corsoDiLaurea;
    private $dataLaurea;
    private $configurazione;
    private $carriere;
    private $simulazioni;

    /**
     * Costruttore.
     * Riceve matricole e (opzionalmente) carriere già caricate da GestoreProspetti.
     * Crea internamente gli oggetti ProspettoConSimulazione.
     *
     * @param array  $matricole     Array di numeri di matricola
     * @param string $corsoDiLaurea Nome del CdL
     * @param string $dataLaurea    Data appello di laurea
     * @param Configurazione|null $configurazione Configurazione condivisa (opzionale)
     * @param array  $carriere      Array di CarrieraLaureando già caricate (opzionale)
     */
    public function __construct(array $matricole, $corsoDiLaurea, $dataLaurea, $configurazione = null, array $carriere = []) {
        $this->matricole      = $matricole;
        $this->corsoDiLaurea  = $corsoDiLaurea;
        $this->dataLaurea     = $dataLaurea;
        $this->configurazione = $configurazione ?: new Configurazione($corsoDiLaurea);
        $this->carriere       = $carriere;
        $this->simulazioni    = [];

        // Crea internamente i ProspettoConSimulazione, riutilizzando le carriere se fornite
        foreach ($this->matricole as $i => $matricola) {
            $carriera = isset($this->carriere[$i]) ? $this->carriere[$i] : null;
            $this->simulazioni[] = new ProspettoConSimulazione(
                $matricola, $corsoDiLaurea, $dataLaurea, $this->configurazione, $carriera
            );
        }
    }

    /**
     * Genera il PDF completo per la commissione.
     * Contiene:
     *  - Prima pagina con lista laureandi
     *  - Pagine successive con carriera + simulazione per ogni laureando
     *  - Pedice esplicativo per la commissione
     *
     * @return FPDF Oggetto PDF generato
     */
    public function creaPDFCommissione() {
        $pdf        = new FPDF();
        $fontFamily = 'Helvetica';

        if (empty($this->simulazioni)) {
            return $pdf;
        }

        // ===== Prima pagina: lista laureandi =====
        $pdf->AddPage();
        $pdf->SetFont($fontFamily, '', 14);
        $cdlLabel = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $this->corsoDiLaurea);
        $pdf->Cell(0, 6, $cdlLabel, 0, 1, 'C');
        $pdf->Ln(2);

        $pdf->SetFont($fontFamily, 'B', 16);
        $pdf->Cell(0, 6, 'LISTA LAUREANDI', 0, 1, 'C');
        $pdf->Ln(2);

        // Data appello
        $pdf->SetFont($fontFamily, '', 10);
        $pdf->Cell(0, 5, 'Data appello: ' . $this->dataLaurea, 0, 1, 'C');
        $pdf->Ln(3);

        // Header tabella
        $width  = 190 / 4;
        $height = 5;
        $pdf->SetFont($fontFamily, 'B', 12);
        $pdf->Cell($width, $height, 'COGNOME',     1, 0, 'C');
        $pdf->Cell($width, $height, 'NOME',        1, 0, 'C');
        $pdf->Cell($width, $height, 'CDL',         1, 0, 'C');
        $pdf->Cell($width, $height, 'VOTO LAUREA', 1, 1, 'C');

        // Righe laureandi
        foreach ($this->simulazioni as $sim) {
            $sim->generaRigaCommissione($pdf);
        }

        // ===== Pagine successive: carriera + simulazione per ogni laureando =====
        foreach ($this->simulazioni as $sim) {
            $sim->aggiungiPDFConSimulazione($pdf);
        }

        // Salva il PDF
        $cdlShort = $this->configurazione->getCdlShort();
        $accesso   = new AccessoProspetti($cdlShort);
        $cartella  = $accesso->getCartellaOutput();
        if (!is_dir($cartella)) {
            mkdir($cartella, 0755, true);
        }
        $pdf->Output('F', $cartella . 'prospettoCommissione.pdf');

        return $pdf;
    }

    /**
     * Restituisce le carriere dei laureandi.
     *
     * @return array Lista di CarrieraLaureando
     */
    public function getCarriere() {
        return $this->carriere;
    }
}
