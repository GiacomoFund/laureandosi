<?php
require_once('C:\Users\franc\Local Sites\genera-prospetti-laurea\app\public\utils\CarrieraLaureando2.php');
class TESTcarrieraLaureando{
    public function test(){
        $this->test_costruttore();
        $this->test_media();
        echo "carrieraLaureando2 : TEST SUPERATI";
    }
    public function test_costruttore(){
          $val = new CarrieraLaureando2(123456, "T. Ing. Informatica");
          $primo_esame = "ELETTROTECNICA";
          if($val->_esami[0]->_nomeEsame != $primo_esame)
              echo "esami non inseriti correttamente";
    }
    public function test_media(){
        $val = new CarrieraLaureando2(123456, "T. Ing. Informatica");
        if ($val->restituisciMedia() < 23 || $val->restituisciMedia() > 24)
            echo "media non calcolata correttamente";
    }
}
?>