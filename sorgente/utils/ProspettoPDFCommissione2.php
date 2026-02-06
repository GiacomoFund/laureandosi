<?php
require_once(realpath(dirname(__FILE__)) . '/ProspettoConSimulazione2.php');

/**
 * @access public
 * @author franc
 */
class ProspettoPDFCommissione2 {
	/**
	 * @AttributeType int[]
	 */
	private $_matricole = array();
	/**
	 * @AttributeType string
	 */
	private $_dataLaurea;
	/**
	 * @AttributeType string
	 */
	private $_cdl;
	/**
	 * @AssociationType ProspettoConSimulazione2
	 * @AssociationKind Composition
	 */


	/**
	 * @access public
	 * @param int[] aMatricole
	 * @param string aDataLaurea
	 * @param string aCdl
	 * @ParamType aMatricole int[]
	 * @ParamType aDataLaurea string
	 * @ParamType aCdl string
	 */
	public function __construct(array $aMatricole, $aDataLaurea, $aCdl) {
		$this->_matricole = $aMatricole;
        $this->_dataLaurea = $aDataLaurea;
        $this->_cdl = $aCdl;
	}

	/**
	 * @access public
	 * @return void
	 * @ReturnType void
	 */
	public function generaProspettiCommissione() {
        $pdf = new FPDF();
        $font_family = "Arial";
        $pdf->AddPage();
        $pdf->SetFont($font_family, "", 14);
// --------  PRIMA PAGINA CON LA LISTA ---------------------
        $pdf->Cell(0, 6, $this->_cdl, 0, 1, 'C');
        $pdf->Ln(2);
        $pdf->SetFont($font_family, "", 16);
        $pdf->Cell(0, 6, 'LISTA LAUREANDI', 0, 1, 'C');
        $pdf->Ln(5);
        $pdf->SetFont($font_family, "", 14);
        $width = 190 / 4;
        $height = 5;
        $pdf->Cell($width, $height, "COGNOME", 1, 0, 'C');
        $pdf->Cell($width, $height, "NOME", 1, 0, 'C');
        $pdf->Cell($width, $height, "CDL", 1, 0, 'C');
        $pdf->Cell($width, $height, "VOTO LAUREA", 1, 1, 'C');
        $pdf->SetFont($font_family, "", 12);
        for ($i = 0; $i < sizeof($this->_matricole); $i++) {
            $pag_con_simulazione = new ProspettoConSimulazione2($this->_matricole[$i], $this->_cdl, $this->_dataLaurea);
            $pdf = $pag_con_simulazione->generaRiga($pdf);
        }

        // --------  PAGINE CON LA CARRIERA ---------------------
        // aggiungo la pagina di ogni laureando
        for ($i = 0; $i < sizeof($this->_matricole); $i++) {
            $pag_con_simulazione = new ProspettoConSimulazione2($this->_matricole[$i], $this->_cdl, $this->_dataLaurea);
            $pdf = $pag_con_simulazione->generaContenuto($pdf);
        }

        $percorso_output = "data\pdf_generati\\";
        $nome_file = "prospettoCommissione.pdf";
        $pdf->Output('F', $percorso_output . $nome_file);

    }
    public function generaProspettiLaureandi()
    {
        for ($i = 0; $i < sizeof($this->_matricole); $i++) {
            $prospetto = new ProspettoPdfLaureando2($this->_matricole[$i], $this->_cdl, $this->_dataLaurea);
            $prospetto->generaProspetto();
        }
    }
    public function popolaJSON($nomeFile){
        $json_string = json_encode($this->_matricole,JSON_PRETTY_PRINT);
        file_put_contents($nomeFile,$json_string);
    }
    public function popolaJSON2($nomeFile){
        $json_string = json_encode($this->_cdl,JSON_PRETTY_PRINT);
        file_put_contents($nomeFile,$json_string);
    }
    public function popolaJSON3($nomeFile){
        $json_string = json_encode($this->_dataLaurea,JSON_PRETTY_PRINT);
        file_put_contents($nomeFile,$json_string);
    }
}
?>