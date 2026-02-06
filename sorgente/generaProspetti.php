<?php
require_once(realpath(dirname(__FILE__)) . '/utils/ProspettoPdfCommissione2.php');
if (isset($_GET["matricole"])) {


    $matricole_array = array_map("intval", explode(",", $_GET["matricole"])); //stringa in array di interi

    $prospetto = new ProspettoPdfCommissione2($matricole_array, $_GET["data_laurea"], $_GET["cdl"]);
    $prospetto->generaProspettiCommissione();
    $prospetto->generaProspettiLaureandi();
    $prospetto->popolaJSON('C:\Users\franc\Local Sites\genera-prospetti-laurea\app\public\data\ausiliario.json');
    $prospetto->popolaJSON2('C:\Users\franc\Local Sites\genera-prospetti-laurea\app\public\data\ausiliario2.json');
    $prospetto->popolaJSON3('C:\Users\franc\Local Sites\genera-prospetti-laurea\app\public\data\ausiliario3.json');
    echo "i prospetti sono stati generati";
}
?>