<?php
require_once(realpath(dirname(__FILE__)) . '/CarrieraLaureando2.php');
class CarrieraLaureandoInformatica2 extends CarrieraLaureando2{
    private $dataImmatricolazione;
    private $dataLaurea;
    private $mediaEsamiInformatici;
    private $bonus;
    public function __construct($matricola, $cdl_in, $dataLaurea){
        parent::__construct($matricola, $cdl_in);
        $this->dataLaurea = $dataLaurea;
        $this->bonus = "NO";
        $gcs = new GestioneCarrieraStudente();
        $carriera_json = $gcs->restituisciCarrieraStudente($matricola);
        $carriera = json_decode($carriera_json, true);
        $this->dataImmatricolazione = $carriera["Esami"]["Esame"][0]["ANNO_IMM"];
        $fine_bonus = ($this->dataImmatricolazione + 4) . ("-05-01");
        if ($dataLaurea < $fine_bonus) {
            $this->bonus = "SI";
            $this->applicaBonus();
        }

        $e_info = file_get_contents('C:\Users\franc\Local Sites\genera-prospetti-laurea\app\public\data\esami_informatici.json');
        $esami_info = json_decode($e_info, true);

        for ($i = 0; $i < sizeof($this->_esami); $i++) {
            if (in_array($this->_esami[$i]->_nomeEsame, $esami_info["nomi_esami"])) {
                $this->_esami[$i]->_informatico = 1;
            }
        }
        $this->mediaEsamiInformatici = $this->calcolaMediaEsamiInformatici();
        $this->calcola_media();
    }

    public function getMediaEsamiInformatici()
    {
        return $this->mediaEsamiInformatici;
    }
    private function calcolaMediaEsamiInformatici()
    {
        $somma = 0;
        $numero = 0;
        for ($i = 0; $i < sizeof($this->_esami); $i++) {
            if ($this->_esami[$i]->_faMedia == 1) {
                $somma += intval($this->_esami[$i]->_votoEsame) ;
                $numero++;
            }
        }
        return $somma / $numero;
    }
    public function getBonus()
    {
        return $this->bonus;
    }
    private function applicaBonus(){

            $voto_min = 33;
            $indice_min = 0;

            for ($i = 0; $i < sizeof($this->_esami); $i++) {
                $esame = $this->_esami[$i];
                if ($esame->_faMedia == 1 && $esame->_votoEsame < $voto_min) {
                    $voto_min = $esame->_votoEsame;
                    $indice_min = $i;
                }
            }

            $this->_esami[$indice_min]->_faMedia = 0;

    }
}
?>