<?php
/**
 * indexTEST.php
 * Pagina per l'esecuzione dei test unitari.
 * Esegue tutti i test nella directory tests/ e mostra i risultati.
 */
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laureandosi - Test</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f6f8;
            color: #333;
        }
        header {
            background-color: #1a3e5c;
            color: white;
            padding: 1.5em;
            text-align: center;
        }
        header h1 { font-size: 1.5em; }
        main { max-width: 860px; margin: 2em auto; padding: 0 1em; }
        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            padding: 1.5em;
            margin-bottom: 1.5em;
        }
        .card h2 {
            font-size: 1.1em;
            margin-bottom: 1em;
            color: #1a3e5c;
            border-bottom: 2px solid #e0e4e8;
            padding-bottom: 0.5em;
        }
        .test-ok {
            background: #d4edda; color: #155724;
            padding: 0.4em 0.8em; margin: 0.3em 0;
            border-radius: 4px; font-family: monospace; font-size: 0.9em;
            cursor: pointer;
            transition: background 0.2s;
        }
        .test-ok:hover {
            background: #c3e6cb;
        }
        .test-fail {
            background: #f8d7da; color: #721c24;
            padding: 0.4em 0.8em; margin: 0.3em 0;
            border-radius: 4px; font-family: monospace; font-size: 0.9em;
            cursor: pointer;
            transition: background 0.2s;
        }
        .test-fail:hover {
            background: #f5c6cb;
        }
        .test-errore {
            background: #fff3cd; color: #856404;
            padding: 0.4em 0.8em; margin: 0.3em 0;
            border-radius: 4px; font-family: monospace; font-size: 0.9em;
            cursor: pointer;
        }
        .test-dettagli {
            display: none;
            margin-top: 0.5em;
            padding: 0.8em;
            background: #f8f9fa;
            border-left: 3px solid #6c757d;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            font-size: 0.85em;
            line-height: 1.6;
        }
        .test-dettagli.visible {
            display: block;
        }
        .test-dettagli strong {
            color: #495057;
        }
        .riepilogo {
            font-size: 1.1em;
            font-weight: bold;
            padding: 1em;
            border-radius: 5px;
            margin-top: 1em;
        }
        .riepilogo-ok {
            background: #d4edda; color: #155724;
            border: 1px solid #c3e6cb;
        }
        .riepilogo-fail {
            background: #f8d7da; color: #721c24;
            border: 1px solid #f5c6cb;
        }
        footer { text-align: center; padding: 1.5em; color: #888; font-size: 0.85em; }
    </style>
</head>
<body>

<header>
    <h1>Test Laureandosi</h1>
</header>

<main>

<?php

require __DIR__ . '/tests/esegui.php';
?>

</main>

<script>
function toggleDetails(testId) {
    const detailsDiv = document.getElementById(testId);
    if (detailsDiv) {
        detailsDiv.classList.toggle('visible');
    }
}
</script>

<footer>
    Laureandosi &copy; DII - Universit&agrave; di Pisa
</footer>

</body>
</html>
