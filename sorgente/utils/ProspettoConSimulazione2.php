<?php
require_once(realpath(dirname(__FILE__)) . '/ProspettoPDFCommissione2.php');
require_once(realpath(dirname(__FILE__)) . '/ProspettoPDFLaureando2.php');

/**
 * @access public
 * @author franc
 */
class ProspettoConSimulazione2 extends ProspettoPDFLaureando2 {
	/**
	 * @AssociationType ProspettoPDFCommissione2
	 */


	/**
	 * @access public
	 * @return void
	 * @ReturnType void
	 */
	public function __construct($matricola, $cdl_in, $data_laurea) {
		parent::__construct($matricola, $cdl_in, $data_laurea);
	}
    public function generaProspettoConSimulazione(){
        $pdf = new FPDF();
        $pdf = $this->generaContenuto($pdf);
        return $pdf;
    }
	/**
	 * @access public
	 * @param FPDF aPdf
	 * @return FPDF
	 * @ParamType aPdf FPDF
	 * @ReturnType FPDF
	 */
	public function generaContenuto($pdf) {
        $font_family = "Arial";
        $tipo_informatico = 0;
// indica se il laureando è informatico, viene modificato da solo

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


        // ------------------------- PARTE DELLA SIMULAZIONE ------------------------------------

        //prendere l'intervallo dei parametri t e c
        $con_s = file_get_contents('C:\Users\franc\Local Sites\genera-prospetti-laurea\app\public\utils\json_files\formule_laurea.json');
        $configurazione_json = json_decode($con_s, true);
        $t_min =  $configurazione_json[$this->_carrieraLaureando->_cdl]["Tmin"];
        $t_max =  $configurazione_json[$this->_carrieraLaureando->_cdl]["Tmax"];
        $t_step =  $configurazione_json[$this->_carrieraLaureando->_cdl]["Tstep"];
        $c_min =  $configurazione_json[$this->_carrieraLaureando->_cdl]["Cmin"];
        $c_max =  $configurazione_json[$this->_carrieraLaureando->_cdl]["Cmax"];
        $c_step =  $configurazione_json[$this->_carrieraLaureando->_cdl]["Cstep"];
        $CFU = $this->_carrieraLaureando->creditiCheFannoMedia();

        // aggiungere al pdf le parti necessarie
        $pdf->Ln(4);
        $pdf->Cell(0, 5.5, "SIMULAZIONE DI VOTO DI LAUREA", 1, 1, 'C');
        $width = 190 / 2;
        $height = 4.5;




        if ($c_min != 0) {
            $pdf->Cell($width, $height, "VOTO COMMISSIONE (C)", 1, 0, 'C');
            $pdf->Cell($width, $height, "VOTO LAUREA", 1, 1, 'C');
            $M = $this->_carrieraLaureando->restituisciMedia();
            $T = 0;

            for ($C = $c_min; $C <= $c_max; $C += $c_step) {
                $voto = 0;
                eval("\$voto = " . $this->_carrieraLaureando->restituisciFormula() . ";");
//$voto = intval($voto);
                $pdf->Cell($width, $height, $C, 1, 0, 'C');
                $pdf->Cell($width, $height, $voto, 1, 1, 'C');
            }
        }
        if ($t_min != 0) {
            $pdf->Cell($width, $height, "VOTO TESI (T)", 1, 0, 'C');
            $pdf->Cell($width, $height, "VOTO LAUREA", 1, 1, 'C');
            $M = $this->_carrieraLaureando->restituisciMedia();
            $C = 0;

            for ($T = $t_min; $T <= $t_max; $T += $t_step) {
                $voto = 0;
                eval("\$voto = " . $this->_carrieraLaureando->restituisciFormula() . ";");
//$voto = intval($voto);
                $pdf->Cell($width, $height, $T, 1, 0, 'C');
                $pdf->Cell($width, $height, $voto, 1, 1, 'C');
            }
        }

        return $pdf;
	}

	/**
	 * @access public
	 * @param FPDF aPdf
	 * @return FPDF
	 * @ParamType aPdf FPDF
	 * @ReturnType FPDF
	 */
	public function generaRiga( $pdf) {
        $width = 190 / 4;
        $height = 5;
        $pdf->Cell($width, $height, $this->_carrieraLaureando->_cognome, 1, 0, 'L');
        $pdf->Cell($width, $height, $this->_carrieraLaureando->_nome, 1, 0, 'L');
        $pdf->Cell($width, $height, "", 1, 0, 'C');
// è vuoto apposta, il cdl è scritto sopra. nell'esempio era così
        $pdf->Cell($width, $height, "/110", 1, 1, 'C');
        return $pdf;
	}
}
?>