<?php
require_once('C:\Users\franc\Local Sites\genera-prospetti-laurea\app\public\utils\CarrieraLaureandoInformatica2.php');
class TESTcarrieraLaureandoInformatica
{
    public function test()
    {
        $this->test_costruttore();
        $this->test_media();
        echo "CarrieraLaureandoInformatica2 : TEST SUPERATI";
    }

    public function test_costruttore()
    {
        $val = new CarrieraLaureandoInformatica2(123456, "T. Ing. Informatica", "2024_01_05");
        $aspettato = "NO";
        if ($aspettato != $val->getBonus()) {
            echo "aspettato" . $aspettato . "rivevuto" . $val->getBonus();
        }
        $val1 = new CarrieraLaureandoInformatica2(123456, "T. Ing. Informatica", "2018_01_05");
        $aspettato1 = "SI";
        if($aspettato1 != $val1->getBonus()){
            echo "aspettato" . $aspettato1 . "rivevuto" . $val1->getBonus();
        }
    }
    public function test_media(){
        $val = new CarrieraLaureandoInformatica2(123456, "T. Ing. Informatica", "2024_01_05");

        $val1 = new CarrieraLaureandoInformatica2(123456, "T. Ing. Informatica", "2018_01_05");

        if($val->restituisciMedia() == $val1->restituisciMedia())
            echo "il bonus non viene applicato correttamente";
    }
}
?>