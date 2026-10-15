<?php
/** Verifica che installazione e risultati siano configurabili senza modificare il codice. */
require_once __DIR__ . '/../classes/AccessoProspetti.php';
require_once __DIR__ . '/../classes/Configurazione.php';

$basePrecedente = getenv('LAUREANDOSI_BASE_URL');
try {
    putenv('LAUREANDOSI_BASE_URL=/demo');
    $accessoConfigurato = new AccessoProspetti('t-inf');
    assertUguale('URL PDF: installazione in sottocartella', '/demo/data/pdf_generati/t-inf/prospettoCommissione.pdf', $accessoConfigurato->getPercorsoCommissione());
    putenv('LAUREANDOSI_BASE_URL=');
    $accessoRadice = new AccessoProspetti('t-inf');
    assertUguale('URL PDF: installazione alla radice', '/data/pdf_generati/t-inf/prospettoCommissione.pdf', $accessoRadice->getPercorsoCommissione());
} finally {
    putenv($basePrecedente === false ? 'LAUREANDOSI_BASE_URL' : 'LAUREANDOSI_BASE_URL=' . $basePrecedente);
}

try {
    $corsiDisponibili = Configurazione::getCorsiDisponibili();
    assertTest('Corsi: elenco ricavato dalla configurazione', in_array('T. Ing. Informatica', $corsiDisponibili, true));
    $runtimeConfigurato = getenv('LAUREANDOSI_RUNTIME_DIR');
    assertTest('Risultati: directory temporanea rispettata', $runtimeConfigurato !== false && strpos((new AccessoProspetti())->getCartellaOutput(), $runtimeConfigurato . '/') === 0);
    assertUguale('Invio: stato nella directory runtime', $runtimeConfigurato . '/dati_invio.json', AccessoProspetti::getPercorsoDatiInvio());
} catch (Throwable $e) {
    assertTest('Configurazione condivisa di corsi e percorsi disponibile', false, $e->getMessage());
}
