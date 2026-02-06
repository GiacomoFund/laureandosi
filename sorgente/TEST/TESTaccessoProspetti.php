<?php
require_once('C:\Users\franc\Local Sites\genera-prospetti-laurea\app\public\utils\AccessoProspetti.php');
class TESTaccessoProspetti{
    public function test(){
        $val = new AccessoProspetti();
        if($val->fornisciAccesso() != 'data\pdf_generati\prospettoCommissione.pdf' )
            echo "invio file errato";
        else
            echo "AccessoProspetti : TEST SUPERATI";
    }
}
?>