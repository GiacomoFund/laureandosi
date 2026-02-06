<!DOCTYPE html>
<head>
    <title>Test</title>
    <style type = "text/css">

    </style>
</head>
<body style = "background-color: whitesmoke" >

<h1>EVENTUALI ERRORI:</h1>
<br>

<?php
require_once('C:\Users\franc\Local Sites\genera-prospetti-laurea\app\public\TEST\TESTconfigurazione.php');
$val = new TESTconfigurazione();
$val->test();
?>
<br>
<?php
require_once('C:\Users\franc\Local Sites\genera-prospetti-laurea\app\public\TEST\TESTesameLaureando.php');

$valore = new TESTesameLaureando();
$valore->test();
?>
<br>
<?php
require_once('C:\Users\franc\Local Sites\genera-prospetti-laurea\app\public\TEST\TESTcarrieraLaureandoInformatica.php');
$val = new TESTcarrieraLaureandoInformatica();
$val->test();
?>
<br>
<?php
require_once('C:\Users\franc\Local Sites\genera-prospetti-laurea\app\public\TEST\TESTcarrieraLaureando.php');
$val = new TESTcarrieraLaureando();
$val->test();
?>
<br>
<?php
require_once('C:\Users\franc\Local Sites\genera-prospetti-laurea\app\public\TEST\TESTaccessoProspetti.php');
$val = new TESTaccessoProspetti();
$val->test();
?>
<br>
<?php
require_once('C:\Users\franc\Local Sites\genera-prospetti-laurea\app\public\TEST\TESTprospettoPDFCommissione.php');
$val = new TESTprospettoPDFCommissione();
$val->test();
?>

<br>
<?php
require_once('C:\Users\franc\Local Sites\genera-prospetti-laurea\app\public\TEST\TESTprospettoPDFLaureando.php');
$val = new TESTprospettoPDFLaureando();
$val->test();
?>
<br>
<?php
require_once('C:\Users\franc\Local Sites\genera-prospetti-laurea\app\public\TEST\TESTgestioneCarrieraStudente.php');
$val = new TESTgestioneCarrieraStudente();
$val->test();
?>
<br>

