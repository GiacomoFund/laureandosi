<?php
/**
 * TESTprospettoPDFCommissione.php
 * Verifica output atteso confrontato con i prospetti PDF di esempio forniti dal docente.
 * Confronta i valori calcolati dal sistema (media, CFU, bonus, media INF)
 * con quelli presenti nei PDF di riferimento.
 *
 * La mappa cdl_short -> nome CdL e la lista dei CdL supportati vengono
 * costruite dinamicamente dal file di configurazione formule_laurea.json.
 */

require_once __DIR__ . '/../classi/CarrieraLaureandoInformatica.php';
require_once __DIR__ . '/../classi/CarrieraLaureando.php';
require_once __DIR__ . '/../classi/Configurazione.php';

// Carica configurazione test da JSON
$configFile = __DIR__ . '/fixtures/config_TESTprospettoPDFCommissione.json';
if (!file_exists($configFile)) {
    echo '<div class="test-errore">ERRORE: File di configurazione test non trovato: ' . $configFile . '</div>';
    return;
}

$testConfig = json_decode(file_get_contents($configFile), true);
if (!$testConfig || !isset($testConfig['studenti'])) {
    echo '<div class="test-errore">ERRORE: Configurazione test non valida o malformata</div>';
    return;
}

// Costruisci cdlMap e cdlSupportati dal file di configurazione formule_laurea.json
$formulePath = __DIR__ . '/../config/formule_laurea.json';
if (!file_exists($formulePath)) {
    echo '<div class="test-errore">ERRORE: File formule_laurea.json non trovato</div>';
    return;
}

$formuleConfig = json_decode(file_get_contents($formulePath), true);
if (!$formuleConfig) {
    echo '<div class="test-errore">ERRORE: formule_laurea.json non valido</div>';
    return;
}

// cdlMap: cdl_short => nome completo CdL (costruito da formule_laurea.json)
$cdlMap = [];
foreach ($formuleConfig as $nomeCdl => $datiCdl) {
    $short = $datiCdl['cdl_short'] ?? '';
    if (!empty($short)) {
        $cdlMap[$short] = $nomeCdl;
    }
}

$tolleranza            = $testConfig['tolleranza'] ?? 0.001;
$dataLaureaDefault     = $testConfig['dataLaureaDefault'];
$dataLaureaInformatica = $testConfig['dataLaureaInformatica'];

foreach ($testConfig['studenti'] as $studente) {
    $matricola = $studente['matricola'];
    $cdlShort  = $studente['cdl'];
    $cdlFull   = $cdlMap[$cdlShort] ?? $cdlShort;
    $label     = sprintf('[%s] %s %s', $matricola, $studente['nome'], $studente['cognome']);

    // Data di laurea differenziata: Informatica usa data specifica
    $isInfCdl   = ($cdlShort === 't-inf');
    $dataLaurea = $isInfCdl ? $dataLaureaInformatica : $dataLaureaDefault;

    // Un corso non supportato deve essere rifiutato esplicitamente.
    if (!isset($studente['expected'])) {
        $corsoRifiutato = false;
        try {
            new Configurazione($cdlFull);
        } catch (InvalidArgumentException $e) {
            $corsoRifiutato = true;
        }
        assertTest($label . ': corso non supportato rifiutato', $corsoRifiutato);
        continue;
    }

    // ---- Caso normale: confronto valori attesi ----
    $expected = $studente['expected'];

    try {
        $config        = new Configurazione($cdlFull);
        $isInformatica = $config->isInformatica();

        if ($isInformatica) {
            $carriera = new CarrieraLaureandoInformatica(
                $matricola, $cdlFull, $dataLaurea, $config
            );
        } else {
            $carriera = new CarrieraLaureando(
                $matricola, $cdlFull, $dataLaurea, $config
            );
        }
    } catch (Throwable $e) {
        assertTest(
            $label,
            false,
            'Eccezione durante la creazione della carriera per ' . $cdlFull
                . ': ' . $e->getMessage(),
            'Carriera creata e indicatori calcolati',
            'Eccezione: ' . $e->getMessage()
        );
        continue;
    }

    // Confronto di tutti i valori
    $tuttoOk      = true;
    $attesoStr    = '';
    $calcolatoStr = '';

    // Anagrafica
    $nomeMatch    = ($carriera->getNome() === $studente['nome']);
    $cognomeMatch = ($carriera->getCognome() === $studente['cognome']);
    $tuttoOk      = $tuttoOk && $nomeMatch && $cognomeMatch;
    $attesoStr    .= sprintf('Nome=%s, Cognome=%s', $studente['nome'], $studente['cognome']);
    $calcolatoStr .= sprintf('Nome=%s, Cognome=%s', $carriera->getNome(), $carriera->getCognome());

    // Media pesata
    $mediaCalc = $carriera->getMediaPesata();
    $mediaAtt  = floatval($expected['media']);
    $tuttoOk   = $tuttoOk && (abs($mediaCalc - $mediaAtt) < $tolleranza);
    $attesoStr    .= sprintf(' | Media(M)=%.3f', $mediaAtt);
    $calcolatoStr .= sprintf(' | Media(M)=%.3f', $mediaCalc);

    // CFU conseguiti
    $cfuTotCalc = $carriera->calcolaCreditiConseguiti();
    $cfuTotAtt  = intval($expected['cfuTotali']);
    $tuttoOk    = $tuttoOk && ($cfuTotCalc === $cfuTotAtt);
    $attesoStr    .= sprintf(' | CFU conseguiti=%d', $cfuTotAtt);
    $calcolatoStr .= sprintf(' | CFU conseguiti=%d', $cfuTotCalc);

    // CFU media
    $cfuMedCalc = $carriera->calcolaCreditiMedia();
    $cfuMedAtt  = intval($expected['cfuMedia']);
    $tuttoOk    = $tuttoOk && ($cfuMedCalc === $cfuMedAtt);
    $attesoStr    .= sprintf(' | CFU media=%d', $cfuMedAtt);
    $calcolatoStr .= sprintf(' | CFU media=%d', $cfuMedCalc);

    // Bonus (solo Informatica)
    if (isset($expected['bonus']) && $carriera instanceof CarrieraLaureandoInformatica) {
        $bonusCalc = $carriera->getIdoneitaBonus();
        $bonusAtt  = (bool)$expected['bonus'];
        $tuttoOk   = $tuttoOk && ($bonusCalc === $bonusAtt);
        $attesoStr    .= sprintf(' | Bonus=%s', $bonusAtt ? 'SI' : 'NO');
        $calcolatoStr .= sprintf(' | Bonus=%s', $bonusCalc ? 'SI' : 'NO');
    }

    // Media INF (solo Informatica)
    if (isset($expected['mediaInf']) && $carriera instanceof CarrieraLaureandoInformatica) {
        $mediaInfCalc = $carriera->getMediaEsamiInformatici();
        $mediaInfAtt  = floatval($expected['mediaInf']);
        $tuttoOk      = $tuttoOk && (abs($mediaInfCalc - $mediaInfAtt) < $tolleranza);
        $attesoStr    .= sprintf(' | MediaINF=%.3f', $mediaInfAtt);
        $calcolatoStr .= sprintf(' | MediaINF=%.3f', $mediaInfCalc);
    }

    $dettaglio = sprintf(
        'Verifica output atteso dal prospetto PDF di esempio. Matricola %s, CdL: %s, Data laurea: %s.',
        $matricola, $cdlFull, $dataLaurea
    );

    assertTest($label, $tuttoOk, $dettaglio, $attesoStr, $calcolatoStr);
}
