<?php
require_once(realpath(dirname(__FILE__)) . '/CarrieraLaureandoInformatica2.php');
require_once(realpath(dirname(__FILE__)) . '/CarrieraLaureando2.php');
require_once(realpath(dirname(__FILE__)) . '/fpdf.php');
/**
 * @access public
 * @author franc
 */

class ProspettoPDFLaureando2 {
	/**
	 * @AttributeType CarrieraLaureando
	 */
	public $_carrieraLaureando;
	/**
	 * @AttributeType int
	 */
	protected $_matricola;
	/**
	 * @AttributeType string
	 */
	protected $_dataLaurea;


	/**
	 * @access public
	 * @param int aMatricola
	 * @param string aCdl
	 * @param string aDataLaurea
	 * @ParamType aMatricola int
	 * @ParamType aCdl string
	 * @ParamType aDataLaurea string
	 */
	public function __construct($aMatricola, $aCdl, $aDataLaurea) {
        if ($aCdl != "INGEGNERIA INFORMATICA (IFO-L)" && $aCdl != "T. Ing. Informatica") {
            $this->_carrieraLaureando = new CarrieraLaureando2($aMatricola, $aCdl);
        } else {
            $this->_carrieraLaureando = new CarrieraLaureandoInformatica2($aMatricola, $aCdl, $aDataLaurea);
        }
        $this->_matricola = $aMatricola;
        $this->_dataLaurea = $aDataLaurea;
	}

	/**
	 * @access public
	 * @return void
	 * @ReturnType void
	 */
	public function generaProspetto() {
        // genera il prospetto in pdf e lo salva in un percorso specifico
        // dati utili;
        $font_family = "Arial";
        $tipo_informatico = 0;
// indica se il laureando è informatico, viene modificato da solo


        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont($font_family, "", 16);
// --------------------- INTESTAZIONE : cdl e scritta prospetto --------------------------

        $pdf->Cell(0, 6, $this->_carrieraLaureando->_cdl, 0, 1, 'C');
// dimensioni, testo, bordo, a capo, align
        $pdf->Cell(0, 8, 'CARRIERA E SIMULAZIONE DEL VOTO DI LAUREA', 0, 1, 'C');
        $pdf->Ln(2);
// ------------------------------ INFORMAZIONI ANAGRAFICHE DELLO STUDENTE ------------------------------

        $pdf->SetFont($font_family, "", 9);
        $anagrafica_stringa = "Matricola:                       " . $this->_matricola . //attenzione: quelli che sembrano spazi in realtà sono &Nbsp perché fpdf non stampa spazi
            "\nNome:                            " . $this->_carrieraLaureando->_nome .
            "\nCognome:                      " . $this->_carrieraLaureando->_cognome .
            "\nEmail:                             " . $this->_carrieraLaureando->_email .
            "\nData:                              " . $this->_dataLaurea;
//aggiungere bonus if inf

        if ($this->_carrieraLaureando->get_class() == "T. Ing. Informatica") {
            $tipo_informatico = 1;
            $anagrafica_stringa .= "\nBonus:                            " . $this->_carrieraLaureando->getBonus();
        }

        $pdf->MultiCell(0, 6, $anagrafica_stringa, 1, 'L');
//$pdf->Cell(0, 100 ,$anagrafica_stringa, 1 ,1, '');
        $pdf->Ln(3);
// spazio bianco

        // ------------------------------- INFORMAZIONI SUGLI ESAMI ----------------------------------------
        // 1 pag = 190 = 21cm con bordi di 1cm
        $larghezza_piccola = 12;
        $altezza = 5.5;
        $larghezza_grande = 190 - (3 * $larghezza_piccola);
        if ($tipo_informatico != 1) {
            $pdf->Cell($larghezza_grande, $altezza, "ESAME", 1, 0, 'C');
            $pdf->Cell($larghezza_piccola, $altezza, "CFU", 1, 0, 'C');
            $pdf->Cell($larghezza_piccola, $altezza, "VOT", 1, 0, 'C');
            $pdf->Cell($larghezza_piccola, $altezza, "MED", 1, 1, 'C');
            // newline
        } else {
            $larghezza_piccola -= 1;
            $larghezza_grande = 190 - (4 * $larghezza_piccola);
            $pdf->Cell($larghezza_grande, $altezza, "ESAME", 1, 0, 'C');
            $pdf->Cell($larghezza_piccola, $altezza, "CFU", 1, 0, 'C');
            $pdf->Cell($larghezza_piccola, $altezza, "VOT", 1, 0, 'C');
            $pdf->Cell($larghezza_piccola, $altezza, "MED", 1, 0, 'C');
            $pdf->Cell($larghezza_piccola, $altezza, "INF", 1, 1, 'C');
            // newline
        }

        $altezza = 4;
        $pdf->SetFont($font_family, "", 8);
        for ($i = 0; $i < sizeof($this->_carrieraLaureando->_esami); $i++) {
            $esame = $this->_carrieraLaureando->_esami[$i];
            $pdf->Cell($larghezza_grande, $altezza, $esame->_nomeEsame, 1, 0, 'L');
            $pdf->Cell($larghezza_piccola, $altezza, $esame->_cfu, 1, 0, 'C');
            $pdf->Cell($larghezza_piccola, $altezza, $esame->_votoEsame, 1, 0, 'C');
            if ($tipo_informatico != 1) {
                $pdf->Cell($larghezza_piccola, $altezza, ($esame->_faMedia == 1) ? 'X' : '', 1, 1, 'C');
                // newline
            } else {
                $pdf->Cell($larghezza_piccola, $altezza, ($esame->_faMedia == 1) ? 'X' : '', 1, 0, 'C');
                $pdf->Cell($larghezza_piccola, $altezza, ($esame->_informatico == 1) ? 'X' : '', 1, 1, 'C');
            }
        }
        $pdf->Ln(5);
// ------------------------------- PARTE RIASUNTIVA  ----------------------------------------
        $pdf->SetFont($font_family, "", 9);
        $string = "Media Pesata (M):                                                  " . $this->_carrieraLaureando->restituisciMedia() .
            "\nCrediti che fanno media (CFU):                             " . $this->_carrieraLaureando->creditiCheFannoMedia() .
            "\nCrediti curriculari conseguiti:                                  " . $this->_carrieraLaureando->creditiCurricolariConseguiti() .
            "\nFormula calcolo voto di laurea:                               " . $this->_carrieraLaureando->restituisciFormula();
        if ($tipo_informatico == 1) {
            $string .= "\nMedia pesata esami INF:                                        " . $this->_carrieraLaureando->getMediaEsamiInformatici();
        }

        $pdf->MultiCell(0, 6, $string, 1, "L");
//$percorso_output =realpath(dirname(__FILE__)) . '\..\data\pdf_generati\\';
        $percorso_output = "data\pdf_generati\\";
        $nome_file = $this->_matricola . "-prospetto.pdf";
        $pdf->Output('F', $percorso_output . $nome_file); // f significa scrivi su file. senza quello non funziona

	}
    public function getCarriera()
    {
        return $this->_carrieraLaureando;
    }
}
?>