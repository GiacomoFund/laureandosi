<?php
require_once __DIR__ . '/ProspettoPDFLaureando.php';

/**
 * Classe Control: ProspettoConSimulazione
 * Estende ProspettoPDFLaureando aggiungendo la tabella di simulazione del voto.
 *
 * Super-classe: ProspettoPDFLaureando
 */
class ProspettoConSimulazione extends ProspettoPDFLaureando {

    /**
     * Costruttore: delega al costruttore padre.
     *
     * @param string $matricola     Numero di matricola
     * @param string $corsoDiLaurea Nome del CdL
     * @param string $dataLaurea    Data appello di laurea
     * @param Configurazione|null    $configurazione Configurazione condivisa (opzionale)
     * @param CarrieraLaureando|null $carriera       Carriera già caricata (opzionale)
     */
    public function __construct($matricola, $corsoDiLaurea, $dataLaurea, $configurazione = null, $carriera = null) {
        parent::__construct($matricola, $corsoDiLaurea, $dataLaurea, $configurazione, $carriera);
    }

    /**
     * Aggiunge al PDF la carriera completa e la tabella di simulazione.
     * Se il PDF non viene fornito, ne crea uno nuovo.
     *
     * @param  FPDF|null $pdf Oggetto PDF esistente (opzionale)
     * @return FPDF      PDF con carriera e simulazione
     */
    public function aggiungiPDFConSimulazione($pdf = null) {
        if ($pdf === null) {
            $pdf = new FPDF();
        }

        // Genera carriera tramite il metodo del padre
        $this->generaContenutoCarriera($pdf);

        // Aggiunge la simulazione
        $this->generaContenutoSimulazione($pdf);

        return $pdf;
    }

    /**
     * Aggiunge la tabella di simulazione del voto al PDF.
     * Usa il valutatore sicuro di Configurazione (no eval).
     * Se tante righe (>6), le organizza in 2 colonne affiancate.
     *
     * @param FPDF $pdf Oggetto PDF su cui scrivere
     */
    protected function generaContenutoSimulazione($pdf) {
        $fontFamily = 'Helvetica';
        $config     = $this->carriera->getConfigurazione();
        $params     = $config->getParametriSimulazione();

        $tMin  = $params['Tmin'];
        $tMax  = $params['Tmax'];
        $tStep = $params['Tstep'];
        $cMin  = $params['Cmin'];
        $cMax  = $params['Cmax'];
        $cStep = $params['Cstep'];

        $M   = $this->carriera->getMediaPesata();
        $CFU = $this->carriera->calcolaCreditiMedia();
        $formula = $config->getFormula();  // Retrieve formula once, reuse in all loops

        $pdf->Ln(4);
        $pdf->SetFont($fontFamily, 'B', 10);
        $pdf->Cell(0, 5.5, 'SIMULAZIONE DI VOTO DI LAUREA', 1, 1, 'C');

        $colWidth = 190 / 4;  // Larghezza di una singola colonna
        $height = 4.5;
        $maxRowsPerColumn = 8;  // Soglia per passare a 2 colonne

        $pdf->SetFont($fontFamily, '', 9);

        // Tabella con variazione di C (voto commissione)
        if ($cMax != 0 && $cStep > 0) {
            // Calcola numero di righe
            $numRows = 0;
            for ($C = $cMin; $C <= $cMax; $C += $cStep) {
                $numRows++;
            }

            // Se tante righe, usa layout a 2 colonne affiancate
            $useDueColonne = $numRows > $maxRowsPerColumn;

            if ($useDueColonne) {
                // Header per 2 colonne affiancate
                $pdf->SetFont($fontFamily, 'B', 9);
                $pdf->Cell($colWidth, $height, 'VOTO COMMISSIONE (C)', 1, 0, 'C');
                $pdf->Cell($colWidth, $height, 'VOTO LAUREA',          1, 0, 'C');
                $pdf->Cell($colWidth, $height, 'VOTO COMMISSIONE (C)', 1, 0, 'C');
                $pdf->Cell($colWidth, $height, 'VOTO LAUREA',          1, 1, 'C');
                $pdf->SetFont($fontFamily, '', 9);

                // Raccogli dati
                $dati = [];
                $T = 0;
                for ($C = $cMin; $C <= $cMax; $C += $cStep) {
                    $variabili = ['M' => $M, 'T' => $T, 'C' => $C, 'CFU' => $CFU];
                    try {
                        $voto = Configurazione::valutaFormula($formula, $variabili);
                        $voto = round($voto, 3);
                    } catch (Exception $e) {
                        $voto = 'ERR';
                    }
                    $dati[] = ['C' => $C, 'voto' => $voto];
                }

                // Stampa in 2 colonne affiancate
                $metà = ceil(count($dati) / 2);
                for ($i = 0; $i < $metà; $i++) {
                    if ($i < count($dati)) {
                        // Colonna sinistra
                        $pdf->Cell($colWidth, $height, $dati[$i]['C'],    1, 0, 'C');

                        // Se non c'è dato a destra, chiudi la riga dopo il voto
                        if ($i + $metà >= count($dati)) {
                            $pdf->Cell($colWidth, $height, $dati[$i]['voto'], 1, 1, 'C');
                        } else {
                            // Altrimenti continua sulla stessa riga
                            $pdf->Cell($colWidth, $height, $dati[$i]['voto'], 1, 0, 'C');
                            // Colonna destra
                            $pdf->Cell($colWidth, $height, $dati[$i + $metà]['C'],    1, 0, 'C');
                            $pdf->Cell($colWidth, $height, $dati[$i + $metà]['voto'], 1, 1, 'C');
                        }
                    }
                }
            } else {
                // Una sola colonna (poche righe)
                $pdf->SetFont($fontFamily, 'B', 9);
                $pdf->Cell($colWidth * 2, $height, 'VOTO COMMISSIONE (C)', 1, 0, 'C');
                $pdf->Cell($colWidth * 2, $height, 'VOTO LAUREA',          1, 1, 'C');
                $pdf->SetFont($fontFamily, '', 9);

                $T = 0;
                for ($C = $cMin; $C <= $cMax; $C += $cStep) {
                    $variabili = ['M' => $M, 'T' => $T, 'C' => $C, 'CFU' => $CFU];
                    try {
                        $voto = Configurazione::valutaFormula($formula, $variabili);
                        $voto = round($voto, 3);
                    } catch (Exception $e) {
                        $voto = 'ERR';
                    }
                    $pdf->Cell($colWidth * 2, $height, $C,    1, 0, 'C');
                    $pdf->Cell($colWidth * 2, $height, $voto, 1, 1, 'C');
                }
            }
        }

        // Tabella con variazione di T (voto tesi)
        if ($tMin != 0 && $tStep > 0) {
            // Calcola numero di righe
            $numRows = 0;
            for ($T = $tMin; $T <= $tMax; $T += $tStep) {
                $numRows++;
            }

            // Se tante righe, usa layout a 2 colonne affiancate
            $useDueColonne = $numRows > $maxRowsPerColumn;

            if ($useDueColonne) {
                // Header per 2 colonne affiancate
                $pdf->SetFont($fontFamily, 'B', 9);
                $pdf->Cell($colWidth, $height, 'VOTO TESI (T)', 1, 0, 'C');
                $pdf->Cell($colWidth, $height, 'VOTO LAUREA',   1, 0, 'C');
                $pdf->Cell($colWidth, $height, 'VOTO TESI (T)', 1, 0, 'C');
                $pdf->Cell($colWidth, $height, 'VOTO LAUREA',   1, 1, 'C');
                $pdf->SetFont($fontFamily, '', 9);

                // Raccogli dati
                $dati = [];
                $C = 0;
                for ($T = $tMin; $T <= $tMax; $T += $tStep) {
                    $variabili = ['M' => $M, 'T' => $T, 'C' => $C, 'CFU' => $CFU];
                    try {
                        $voto = Configurazione::valutaFormula($formula, $variabili);
                        $voto = round($voto, 3);
                    } catch (Exception $e) {
                        $voto = 'ERR';
                    }
                    $dati[] = ['T' => $T, 'voto' => $voto];
                }

                // Stampa in 2 colonne affiancate
                $metà = ceil(count($dati) / 2);
                for ($i = 0; $i < $metà; $i++) {
                    if ($i < count($dati)) {
                        // Colonna sinistra
                        $pdf->Cell($colWidth, $height, $dati[$i]['T'],    1, 0, 'C');

                        // Se non c'è dato a destra, chiudi la riga dopo il voto
                        if ($i + $metà >= count($dati)) {
                            $pdf->Cell($colWidth, $height, $dati[$i]['voto'], 1, 1, 'C');
                        } else {
                            // Altrimenti continua sulla stessa riga
                            $pdf->Cell($colWidth, $height, $dati[$i]['voto'], 1, 0, 'C');
                            // Colonna destra
                            $pdf->Cell($colWidth, $height, $dati[$i + $metà]['T'],    1, 0, 'C');
                            $pdf->Cell($colWidth, $height, $dati[$i + $metà]['voto'], 1, 1, 'C');
                        }
                    }
                }
            } else {
                // Una sola colonna (poche righe)
                $pdf->SetFont($fontFamily, 'B', 9);
                $pdf->Cell($colWidth * 2, $height, 'VOTO TESI (T)', 1, 0, 'C');
                $pdf->Cell($colWidth * 2, $height, 'VOTO LAUREA',   1, 1, 'C');
                $pdf->SetFont($fontFamily, '', 9);

                $C = 0;
                for ($T = $tMin; $T <= $tMax; $T += $tStep) {
                    $variabili = ['M' => $M, 'T' => $T, 'C' => $C, 'CFU' => $CFU];
                    try {
                        $voto = Configurazione::valutaFormula($formula, $variabili);
                        $voto = round($voto, 3);
                    } catch (Exception $e) {
                        $voto = 'ERR';
                    }
                    $pdf->Cell($colWidth * 2, $height, $T,    1, 0, 'C');
                    $pdf->Cell($colWidth * 2, $height, $voto, 1, 1, 'C');
                }
            }
        }

        // Aggiungi pedice della commissione sotto le tabelle di simulazione
        $this->aggiungiPediceCommissione($pdf);
    }

    /**
     * Aggiunge il pedice della commissione al termine della simulazione.
     *
     * @param FPDF $pdf Oggetto PDF su cui scrivere
     */
    protected function aggiungiPediceCommissione($pdf) {
        $fontFamily = 'Helvetica';
        $config = $this->carriera->getConfigurazione();
        $pedice = $config->getPediceCommissione();

        if (!empty($pedice)) {
            $pdf->Ln(5);
            $pdf->SetFont($fontFamily, 'I', 8);
            $pdf->MultiCell(0, 4, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $pedice), 0, 'L');
        }
    }

    /**
     * Genera una riga riepilogativa per la tabella della commissione.
     * Usato da ProspettoPDFCommissione per la lista dei laureandi.
     *
     * @param  FPDF $pdf Oggetto PDF su cui scrivere
     * @return FPDF PDF aggiornato
     */
    public function generaRigaCommissione($pdf) {
        $fontFamily = 'Helvetica';
        $width  = 190 / 4;
        $height = 5;

        $pdf->SetFont($fontFamily, '', 12);
        $cognome = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $this->carriera->getCognome());
        $nome    = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $this->carriera->getNome());

        $pdf->Cell($width, $height, $cognome, 1, 0, 'L');
        $pdf->Cell($width, $height, $nome,    1, 0, 'L');
        $pdf->Cell($width, $height, '',       1, 0, 'C');
        $pdf->Cell($width, $height, '/110',   1, 1, 'C');

        return $pdf;
    }
}
