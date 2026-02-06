<?php
require_once('C:\Users\franc\Local Sites\genera-prospetti-laurea\app\public\utils\InvioPDFLaureando2.php');

    $invio = new InvioPDFLaureando2();
    $invio->invioProspetti();
    echo "i prospetti sono stati inviati";

?>