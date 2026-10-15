<?php
/** Verifica generazione PDF e gestione di carriere parzialmente disponibili. */
require_once __DIR__ . '/../classi/GestoreProspetti.php';

$commissione = new ProspettoPDFCommissione(
    ['123456', '345678'], 'T. Ing. Informatica', '2023-04-01'
);
$pdf = $commissione->creaPDFCommissione();
assertUguale('Commissione: lista e due carriere producono tre pagine', 3, $pdf->PageNo());
assertUguale('Commissione: restituisce anche le carriere create internamente', 2, count($commissione->getCarriere()));

$commissioneParziale = null;
try {
    $commissioneParziale = new ProspettoPDFCommissione(
        ['123456', '999888'], 'T. Ing. Informatica', '2023-04-01'
    );
    $pdfParziale = $commissioneParziale->creaPDFCommissione();
    assertUguale('Commissione: una matricola assente non blocca quella valida', 2, $pdfParziale->PageNo());
} catch (RuntimeException $e) {
    assertTest('Commissione: una matricola assente non blocca quella valida', false, $e->getMessage());
}

$gestore = new GestoreProspetti(['123456', '999888'], 'T. Ing. Informatica', '2023-04-01');
$gestore->generaProspettiLaureandi();
$gestore->generaProspettoCommissione();
assertUguale('Gestore: conserva solo la matricola generata', ['123456'], $gestore->getProspettiGenerati());
assertUguale('Gestore: registra la matricola assente', '999888', $gestore->getErrori()[0]['matricola']);
$gestore->salvaDatiPerInvio();
$datiInvioGenerati = json_decode(file_get_contents(AccessoProspetti::getPercorsoDatiInvio()), true);
assertUguale('Invio: conserva solo gli studenti con PDF generato', ['123456'], $datiInvioGenerati['matricole']);
$accessoGenerazione = new AccessoProspetti('t-inf');
$contenutoPDF = file_get_contents($accessoGenerazione->getPercorsoCommissioneAssoluto());
assertTest('Gestore: salva un documento PDF valido', strncmp($contenutoPDF, '%PDF-', 5) === 0);
