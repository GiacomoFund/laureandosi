<?php
require_once('C:\Users\franc\Local Sites\genera-prospetti-laurea\app\public\utils\ProspettoPDFLaureando2.php');
class TESTprospettoPDFLaureando{
    public function test(){
        $val = new ProspettoPDFLaureando2(123456,"T. Ing. Informatica","2024_01_05");
        $vecchio = file_get_contents('C:\Users\franc\Local Sites\genera-prospetti-laurea\app\public\data\pdf_generati\123456-prospetto.pdf');
        $val->generaProspetto();
        $aux = file_get_contents('C:\Users\franc\Local Sites\genera-prospetti-laurea\app\public\data\pdf_generati\123456-prospetto.pdf');
        file_put_contents('C:\Users\franc\Local Sites\genera-prospetti-laurea\app\public\data\pdf_generati\123456-prospetto.pdf',$vecchio);

        $vecchio1 = file_get_contents('C:\Users\franc\Local Sites\genera-prospetti-laurea\app\public\data\pdf_generati\123456-prospetto.pdf');
        $var = new ProspettoPDFLaureando2(123456, "T. Ing. Informatica", "2018_01_05");
        $var->generaProspetto();
        $aux1 = file_get_contents('C:\Users\franc\Local Sites\genera-prospetti-laurea\app\public\data\pdf_generati\123456-prospetto.pdf');
        file_put_contents('C:\Users\franc\Local Sites\genera-prospetti-laurea\app\public\data\pdf_generati\123456-prospetto.pdf',$vecchio1);

        if($aux == $aux1)
            echo "prospetti non generati correttamente";
        else
            echo "ProspettoPDFLaureando2 : TEST SUPERATI";
    }
}
?>