<?php
/** Percorsi di installazione; i dati dimostrativi restano nella cartella data/. */
$baseUrl = getenv('LAUREANDOSI_BASE_URL');
$runtimeDir = getenv('LAUREANDOSI_RUNTIME_DIR');

return [
    'base_url' => rtrim($baseUrl === false ? '/laureandosi' : $baseUrl, '/'),
    'runtime_dir' => rtrim($runtimeDir === false || $runtimeDir === '' ? dirname(__DIR__) . '/data' : $runtimeDir, '/'),
];
