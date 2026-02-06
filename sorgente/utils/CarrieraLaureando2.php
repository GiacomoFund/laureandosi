<?php
require_once(realpath(dirname(__FILE__)) . '/EsameLaureando2.php');
require_once(realpath(dirname(__FILE__)) . '/ProspettoPDFLaureando2.php');
require_once(realpath(dirname(__FILE__)) . '/GestioneCarrieraStudente2.php');


class CarrieraLaureando2 {

	public $_matricola;

	public $_nome;

	public $_cognome;

	public $_cdl;

	public $_email;

	public $_esami;

	private $_media;

	private $_formulaVotoLaurea;

	public function __construct($matricola, $cdl_in){
        //costruisco un oggetto carrieraLaureando del laureando con matricola matricola
        $this->matricola = $matricola;
        //chiamo gcs per prendere tutte le info del laureando
        $gcs = new GestioneCarrieraStudente();
        $anagrafica_json = $gcs->restituisciAnagraficaStudente($matricola);
        $carriera_json = $gcs->restituisciCarrieraStudente($matricola);
        $con_s = file_get_contents('C:\Users\franc\Local Sites\genera-prospetti-laurea\app\public\utils\json_files\formule_laurea.json');
        $configurazione_json = json_decode($con_s, true);
        $anagrafica = json_decode($anagrafica_json, true);
        $this->_nome = $anagrafica["Entries"]["Entry"]["nome"];
        $this->_cognome = $anagrafica["Entries"]["Entry"]["cognome"];
        $this->_email = $anagrafica["Entries"]["Entry"]["email_ate"];
        $this->_cdl = $cdl_in;
        $this->_formulaVotoLaurea =  $configurazione_json[$this->_cdl]["formula"];
        $carriera = json_decode($carriera_json, true);
        $this->_esami = array();
        for ($i = 0; $i < sizeof($carriera["Esami"]["Esame"]); $i++) {
            $esame = $this-> inserisci_esame($carriera["Esami"]["Esame"][$i]["DES"], $carriera["Esami"]["Esame"][$i]["VOTO"], $carriera["Esami"]["Esame"][$i]["PESO"], 1, 1);
            if ($esame != null && is_string($esame->_nomeEsame)) {
                array_push($this->_esami, $esame);
            }
        }

        $this->calcola_media();

    }
    public function calcola_media()
    {
        $esami = $this->_esami;
        $somma_voto_cfu = 0;
        $somma_cfu_tot = 0;

        for ($i = 0; $i < sizeof($esami); $i++) {
            if ($esami[$i]->_faMedia == 1) {

                $somma_voto_cfu += intval($esami[$i]->_votoEsame) * $esami[$i]->_cfu;
//devi convertire il voto in un int prima
                $somma_cfu_tot += $esami[$i]->_cfu;
            }
            //console_log($somma_voto_cfu);
        }
        $this->_media = $somma_voto_cfu / $somma_cfu_tot;
        return $this->_media;
    }
    public function restituisciMedia(){
        return $this->_media;
    }



	public function creditiCurricolariConseguiti() {
        $crediti = 0;
        for ($i = 0; sizeof($this->_esami) > $i; $i++) {
            if ($this->_esami[$i]->_nomeEsame != "PROVA FINALE" &&  $this->_esami[$i]->_nomeEsame != "LIBERA SCELTA PER RICONOSCIMENTI") {
                $crediti += ($this->_esami[$i]->_curricolare == 1) ? $this->_esami[$i]->_cfu : 0;
            }
        }
        return $crediti;
	}


	public function restituisciFormula() {
		return $this->_formulaVotoLaurea;
	}
    public function creditiCheFannoMedia()
    {
        $crediti = 0;

        for ($i = 0; sizeof($this->_esami) > $i; $i++) {
            $crediti += ($this->_esami[$i]->_curricolare == 1 && $this->_esami[$i]->_faMedia == 1) ? $this->_esami[$i]->_cfu : 0;
        }
        return $crediti;
    }


    private function inserisci_esame($nome, $voto, $cfu, $faMedia, $curricolare)
    {

        if (
            $nome == "LIBERA SCELTA PER RICONOSCIMENTI" || $nome == "PROVA FINALE" || $nome ==  "TEST DI VALUTAZIONE DI INGEGNERIA"
            || $nome == "PROVA DI LINGUA INGLESE B2" || $voto == 0
        ) {
            $faMedia = 0;
        }
        // non metto esami con parametri malformati
        if ($nome != "TEST DI VALUTAZIONE DI INGEGNERIA" && $nome != null) {
            if ($voto == "30 e lode" || $voto == "30 e lode " || $voto == "30  e lode") {
// -_- ci hanno messo 2 spazi
                $voto = "33";
            }

            trim($voto);
//toglie gli spazi bianchi
            //trim($cfu);
            $esame = new EsameLaureando2();
            $esame->_nomeEsame = $nome;
            $esame->_votoEsame = $voto;
            $esame->_cfu = $cfu;
            $esame->_faMedia = $faMedia;
            $esame->_curricolare = $curricolare;
            return $esame;
        } else {
            return null;
        }
    }
    public function get_class(){
        return $this->_cdl;
    }
}
?>