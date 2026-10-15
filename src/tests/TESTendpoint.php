<?php
/** Prove CLI degli endpoint reali, senza server web o invio di email. */
$richiediEndpoint = function ($endpoint, array $post, $directoryRuntime) {
    $codice = '$_SERVER["REQUEST_METHOD"] = "POST"; $_POST = '
        . var_export($post, true) . '; require '
        . var_export(dirname(__DIR__) . '/' . $endpoint, true) . ';';
    $processo = proc_open(
        [PHP_BINARY, '-r', $codice],
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $canali,
        null,
        ['LAUREANDOSI_RUNTIME_DIR' => $directoryRuntime, 'LAUREANDOSI_BASE_URL' => '/demo']
    );
    if (!is_resource($processo)) {
        throw new RuntimeException('Impossibile avviare il processo PHP di test.');
    }
    fclose($canali[0]);
    $risposta = stream_get_contents($canali[1]);
    $errori = stream_get_contents($canali[2]);
    fclose($canali[1]);
    fclose($canali[2]);
    $uscita = proc_close($processo);
    if ($uscita !== 0 || $errori !== '') {
        throw new RuntimeException('Errore endpoint: ' . $errori);
    }
    return json_decode($risposta, true, 512, JSON_THROW_ON_ERROR);
};

$casiEndpoint = [
    'generazione valida' => ['data' => '2023-04-01', 'matricole' => '123456 345678', 'successo' => true],
    'data impossibile' => ['data' => '2023-02-31', 'matricole' => '123456', 'successo' => false],
    'matricola inesistente' => ['data' => '2023-04-01', 'matricole' => '999888', 'successo' => false],
    'parametro di tipo errato' => ['data' => '2023-04-01', 'matricole' => [], 'successo' => false],
];

foreach (['generaProspetti.php', 'API.php'] as $endpoint) {
    foreach ($casiEndpoint as $nomeCaso => $casoEndpoint) {
        $directoryEndpoint = $runtimeTest . '/endpoint-' . bin2hex(random_bytes(4));
        mkdir($directoryEndpoint, 0700);
        $rispostaEndpoint = $richiediEndpoint($endpoint, [
            'azione' => 'genera_prospetti',
            'cdl' => 'T. Ing. Informatica',
            'data_laurea' => $casoEndpoint['data'],
            'matricole' => $casoEndpoint['matricole'],
        ], $directoryEndpoint);
        $esitoEndpoint = $rispostaEndpoint['successo'] === $casoEndpoint['successo'];
        if ($casoEndpoint['successo']) {
            $esitoEndpoint = $esitoEndpoint
                && $rispostaEndpoint['matricole'] === ['123456', '345678']
                && $rispostaEndpoint['urlCommissione'] === '/demo/data/pdf_generati/t-inf/prospettoCommissione.pdf'
                && is_file($directoryEndpoint . '/pdf_generati/t-inf/prospettoCommissione.pdf');
        } else {
            $esitoEndpoint = $esitoEndpoint && !file_exists($directoryEndpoint . '/dati_invio.json');
        }
        assertTest($endpoint . ': ' . $nomeCaso, $esitoEndpoint);
    }
}

foreach (['inviaProspetti.php', 'API.php'] as $endpoint) {
    $rispostaEndpoint = $richiediEndpoint($endpoint, [
        'azione' => 'invia_prospetti', 'matricole' => [],
    ], $runtimeTest);
    assertTest($endpoint . ': selezione vuota rifiutata prima di inviare email', $rispostaEndpoint['successo'] === false);
}
