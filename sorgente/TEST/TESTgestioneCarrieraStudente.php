<?php
require_once('C:\Users\franc\Local Sites\genera-prospetti-laurea\app\public\utils\GestioneCarrieraStudente2.php');
class TESTgestioneCarrieraStudente{
    public function test(){
        $val = new GestioneCarrieraStudente();
        $aux = $val->restituisciAnagraficaStudente(123456);
        $aux1 = json_decode($aux,true);
        $aux2 = $aux1["Entries"]["Entry"]["nome"];
        if($aux2 == "GIUSEPPE")
            echo "GestioneCarrieraStudente2 : TEST SUPERATI";
        else
            echo "GestioneCarrieraStudente2 non preleva correttamente i dati";
    }
}
