<?php
class GestioneCarrieraStudente{
    public function restituisciCarrieraStudente($matricola){
        $json_carriera = file_get_contents('C:/Users/franc/Local Sites/genera-prospetti-laurea/app/public/utils/json_files/' . $matricola . "_esami.json");
        return $json_carriera;
    }
    public static function restituisciAnagraficaStudente($matricola){
        $json_anagrafica = file_get_contents('C:/Users/franc/Local Sites/genera-prospetti-laurea/app/public/utils/json_files/' . $matricola . "_anagrafica.json");
        return $json_anagrafica;
    }
}
?>