<!DOCTYPE html>
<head>
    <title>Conf</title>
    <style type = "text/css">

    </style>
</head>
<body style = "background-color: whitesmoke" >

<h1>CONFIGURAZIONE:</h1>
<br>
<form action = "indexCONF.php" method = "get">
    <p>Cdl:</p>
    <select name = "cdl"><!-- tutti quelli dei test  -->
        <option name = "cdl">T. Ing. Informatica</option>
        <option name = "cdl">M. Cybersecurity</option>
        <option name = "cdl">M. Ing. Elettronica</option>
        <option name = "cdl">T. Ing. Biomedica</option>
        <option name = "cdl">M. Ing. Biomedica, Bionics Engineering</option>
        <option name = "cdl">T. Ing. Elettronica</option>
        <option name = "cdl">T. Ing. delle Telecomunicazioni</option>
        <option name = "cdl">M. Ing. delle Telecomunicazioni</option>
        <option name = "cdl">M. Computer Engineering, Artificial Intelligence and Data Enginering</option>
        <option name = "cdl">M. Ing. Robotica e della Automazione"</option>
    </select>
    <br>
    <p>Formula:</p>
    <textarea name = "formula"></textarea>
    <br>
    <p>Esami Informatici:</p>
    <textarea name = "esami_informatici"></textarea>
    <br>
    <button type = "submit">
        Configura
    </button>
    <br>

<?php
require_once('C:\Users\franc\Local Sites\genera-prospetti-laurea\app\public\utils\ModificaParametriConfigurazione.php');
if(isset($_GET["formula"]) && isset($_GET["esami informatici"])){
    $array_inf = array_map("intval", explode(",", $_GET["esami_informatici"]));
    $val = new ModificaParametriCofigurazione($_GET["cdl"],$array_inf);
    $val->modificaFormula($_GET["formula"]);
    $val->modificaEsamiInformatici();
    echo "parametri configurati";
}
?>
</form>