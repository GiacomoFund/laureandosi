<?php
require_once __DIR__ . '/CarrieraLaureandoInformatica.php';
require_once __DIR__ . '/CarrieraLaureando.php';
require_once __DIR__ . '/AccessoProspetti.php';
require_once __DIR__ . '/../lib/fpdf184/fpdf.php';

/**
 * Classe Control: ProspettoPDFLaureando
 * Genera il PDF con il prospetto di laurea del singolo laureando.
 *
 * Sotto-classi: ProspettoConSimulazione
 *
 * Attributi:
 *  - prospettoLaureando: Oggetto FPDF contenente il documento PDF
 *
 * Operazioni:
 *  + creaPDFLaureando(): FPDF
 */
class ProspettoPDFLaureando {

    protected $carriera;        // CarrieraLaureando o CarrieraLaureandoInformatica
    protected $configurazione;  // Configurazione
    protected $matricola;
    protected $dataLaurea;

    /**
     * Costruttore: crea la carriera appropriata in base al CdL.
     *
     * @param string $matricola     Numero di matricola
     * @param string $corsoDiLaurea Nome del CdL
     * @param string $dataLaurea    Data appello di laurea
     * @param Configurazione|null      $configurazione Configurazione condivisa (opzionale)
     * @param CarrieraLaureando|null   $carriera       Carriera già caricata (opzionale, evita rileggere i JSON)
     */
    public function __construct($matricola, $corsoDiLaurea, $dataLaurea, $configurazione = null, $carriera = null) {
        $this->matricola  = trim(strval($matricola));
        $this->dataLaurea = $dataLaurea;

        if ($carriera !== null) {
            // Riutilizza carriera e configurazione già caricate
            $this->carriera       = $carriera;
            $this->configurazione = $carriera->getConfigurazione();
        } else {
            // Riutilizza Configurazione se fornita, altrimenti ne crea una nuova
            $this->configurazione = $configurazione ?: new Configurazione($corsoDiLaurea);

            // Crea la carriera appropriata (polimorfismo), passando la Configurazione
            if ($this->configurazione->isInformatica()) {
                $this->carriera = new CarrieraLaureandoInformatica(
                    $matricola, $corsoDiLaurea, $dataLaurea, $this->configurazione
                );
            } else {
                $this->carriera = new CarrieraLaureando(
                    $matricola, $corsoDiLaurea, $dataLaurea, $this->configurazione
                );
            }
        }
    }

    /**
     * Crea il PDF del prospetto del laureando.
     *
     * @return FPDF Oggetto PDF generato
     */
    public function creaPDFLaureando() {
        $pdf = new FPDF();
        $this->generaContenutoCarriera($pdf);
        return $pdf;
    }

    /**
     * Genera il contenuto della carriera su un oggetto PDF esistente.
     * Metodo condiviso con ProspettoConSimulazione per eliminare
     * la duplicazione di codice.
     *
     * @param FPDF $pdf Oggetto PDF su cui scrivere
     */
    protected function generaContenutoCarriera($pdf) {
        $fontFamily     = 'Helvetica';
        $isInformatica  = ($this->carriera instanceof CarrieraLaureandoInformatica);

        $pdf->AddPage();
        $pdf->SetFont($fontFamily, '', 16);

        // ----- Intestazione: CdL e titolo -----
        $pdf->Cell(0, 6, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $this->carriera->getCorsoDiLaurea()), 0, 1, 'C');
        $pdf->Cell(0, 8, 'CARRIERA E SIMULAZIONE DEL VOTO DI LAUREA', 0, 1, 'C');
        $pdf->Ln(2);

        // ----- Informazioni anagrafiche -----
        $pdf->SetFont($fontFamily, '', 9);
        $righe = "Matricola:                       " . $this->matricola .
            "\nNome:                            " . $this->carriera->getNome() .
            "\nCognome:                      "    . $this->carriera->getCognome() .
            "\nEmail:                             " . $this->carriera->getEmail() .
            "\nData:                              " . $this->dataLaurea;

        if ($isInformatica) {
            $righe .= "\nBonus:                            " . $this->carriera->getBonusLabel();
        }

        $pdf->MultiCell(0, 6, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $righe), 1, 'L');
        $pdf->Ln(3);

        // ----- Tabella esami -----
        $this->generaTabellaEsami($pdf, $isInformatica);

        // ----- Riepilogo -----
        $pdf->Ln(5);
        $pdf->SetFont($fontFamily, '', 9);

        $cfuRichiesti  = $this->carriera->getConfigurazione()->getCfuRichiesti();
        $cfuConseguiti = $this->carriera->calcolaCreditiConseguiti();
        $formulaRaw    = $this->carriera->getFormula();
        $formulaPulita = str_replace(['$M', '$T', '$C', '$CFU'], ['M', 'T', 'C', 'CFU'], $formulaRaw);
        $formulaPulita = str_replace(' ', '', $formulaPulita);

        $riepilogo  = "Media Pesata (M):                                                  " . $this->carriera->getMediaPesata() .
            "\nCrediti che fanno media (CFU):                             "   . $this->carriera->calcolaCreditiMedia() .
            "\nCrediti curriculari conseguiti:                                  " . $cfuConseguiti . "/" . $cfuRichiesti .
            "\nFormula calcolo voto di laurea:                               " . $formulaPulita;

        if ($isInformatica) {
            $riepilogo .= "\nMedia pesata esami INF:                                        " . $this->carriera->getMediaEsamiInformatici();
        }

        // Segnalazione CFU (C01)
        $cfuRichiesti  = $this->carriera->getConfigurazione()->getCfuRichiesti();
        $cfuConseguiti = $this->carriera->calcolaCreditiConseguiti();
        if ($cfuConseguiti != $cfuRichiesti && $cfuRichiesti > 0) {
            $differenza = $cfuConseguiti - $cfuRichiesti;
            if ($differenza > 0) {
                $riepilogo .= "\nCrediti sovrannumerari:                                          +" . $differenza;
            } else {
                $riepilogo .= "\nATTENZIONE - Crediti mancanti:                              " . $differenza;
            }
        }

        $pdf->MultiCell(0, 6, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $riepilogo), 1, 'L');
    }

    /**
     * Genera la tabella degli esami nel PDF.
     *
     * @param FPDF $pdf           Oggetto PDF
     * @param bool $isInformatica Se true, aggiunge colonna INF
     */
    protected function generaTabellaEsami($pdf, $isInformatica) {
        $fontFamily      = 'Helvetica';
        $larghezzaPiccola = 12;
        $altezzaHeader    = 5.5;

        if ($isInformatica) {
            $larghezzaPiccola -= 1;
            $larghezzaGrande = 190 - (4 * $larghezzaPiccola);
        } else {
            $larghezzaGrande = 190 - (3 * $larghezzaPiccola);
        }

        // Header tabella
        $pdf->SetFont($fontFamily, 'B', 8);
        $pdf->Cell($larghezzaGrande,  $altezzaHeader, 'ESAME', 1, 0, 'C');
        $pdf->Cell($larghezzaPiccola, $altezzaHeader, 'CFU',   1, 0, 'C');
        $pdf->Cell($larghezzaPiccola, $altezzaHeader, 'VOT',   1, 0, 'C');
        $pdf->Cell($larghezzaPiccola, $altezzaHeader, 'MED',   1, $isInformatica ? 0 : 1, 'C');
        if ($isInformatica) {
            $pdf->Cell($larghezzaPiccola, $altezzaHeader, 'INF', 1, 1, 'C');
        }

        // Righe esami
        $altezzaRiga = 4;
        $pdf->SetFont($fontFamily, '', 8);

        foreach ($this->carriera->getEsami() as $esame) {
            $nomeEsame = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $esame->getNome());
            $pdf->Cell($larghezzaGrande,  $altezzaRiga, $nomeEsame,          1, 0, 'L');
            $pdf->Cell($larghezzaPiccola, $altezzaRiga, $esame->getCfu(),    1, 0, 'C');
            $pdf->Cell($larghezzaPiccola, $altezzaRiga, $esame->getVoto(),   1, 0, 'C');

            $flagMedia = $esame->getFaMedia() ? 'X' : '';
            $pdf->Cell($larghezzaPiccola, $altezzaRiga, $flagMedia, 1, $isInformatica ? 0 : 1, 'C');

            if ($isInformatica) {
                $flagInfo = $esame->getInformatico() ? 'X' : '';
                $pdf->Cell($larghezzaPiccola, $altezzaRiga, $flagInfo, 1, 1, 'C');
            }
        }
    }

    /**
     * Salva il prospetto PDF individuale su file in sottocartella basata su cdl_short.
     */
    public function salvaProspetto() {
        $pdf      = $this->creaPDFLaureando();
        $cdlShort = $this->configurazione->getCdlShort();

        // Usa AccessoProspetti per centralizzare i percorsi
        $accesso  = new AccessoProspetti($cdlShort);
        $cartella = $accesso->getCartellaOutput();

        if (!is_dir($cartella)) {
            mkdir($cartella, 0755, true);
        }

        $nomeFile = $this->matricola . '-prospetto.pdf';
        $pdf->Output('F', $cartella . $nomeFile);
    }

    /**
     * Restituisce la matricola del laureando.
     *
     * @return string
     */
    public function getMatricola() {
        return $this->matricola;
    }

    /**
     * Restituisce l'oggetto CarrieraLaureando associato.
     *
     * @return CarrieraLaureando
     */
    public function getCarriera() {
        return $this->carriera;
    }
}
