<?php
require_once('C:\Users\franc\Local Sites\genera-prospetti-laurea\app\public\utils\EsameLaureando2.php');
class TESTesameLaureando{
    public function test(){
        $ausiliaria = new EsameLaureando2();
        $ausiliaria->_nomeEsame = "STATISTICA";
        $ausiliaria->_votoEsame = 28;
        $aux = $ausiliaria->_nomeEsame;
        if($aux != "STATISTICA")
            echo "errore nome esame su esameLaureando2";
        $aux1 = $ausiliaria->_votoEsame;
        if($aux1 != 28)
            echo "errore voto esame su esameLaureando2";
        echo "EsameLaureando2 : TEST SUPERATI";
    }
}
?>
