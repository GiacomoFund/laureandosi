<?php
/**
 * Template Name: Laureandosi
 * Pagina principale del sistema Laureandosi.
 * Fornisce l'interfaccia grafica all'Unita' Didattica.
 *
 * Funzionalita':
 *  - Generazione prospetti (UC1)
 *  - Visualizzazione prospetti (UC2)
 *  - Invio prospetti via email (UC3)
 */
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laureandosi - Gestione Lauree</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #dfe6ed;
            color: #333;
            min-height: 100vh;
            padding: 2em 1em;
        }

        .page-title {
            text-align: center;
            font-size: 1.6em;
            font-weight: bold;
            color: #1a3e5c;
            margin-bottom: 1.2em;
        }

        .container {
            max-width: 960px;
            margin: 0 auto;
            background: #eaf0f6;
            border: 1px solid #b8c6d4;
            border-radius: 8px;
            padding: 1.5em;
        }

        /* ===== Layout a 3 colonne ===== */
        .layout-tre-colonne {
            display: flex;
            gap: 2em;
            align-items: flex-start;
        }

        .colonna-sx {
            flex: 0 0 220px;
            min-width: 200px;
        }

        .colonna-centro {
            flex: 1 1 auto;
            min-width: 250px;
            display: flex;
            flex-direction: column;
        }

        .colonna-dx {
            flex: 0 0 220px;
            min-width: 200px;
        }

        label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.3em;
            color: #333;
            font-size: 0.95em;
            text-decoration: underline;
        }

        select, textarea, input[type="date"] {
            width: 100%;
            padding: 0.45em;
            border: 1px solid #aab5c0;
            border-radius: 4px;
            font-size: 0.9em;
            margin-bottom: 1em;
            background: white;
        }

        select:focus, textarea:focus, input:focus {
            outline: none;
            border-color: #1a3e5c;
            box-shadow: 0 0 0 2px rgba(26, 62, 92, 0.15);
        }

        textarea {
            min-height: 140px;
            flex: 1;
            resize: vertical;
        }

        .btn {
            display: block;
            padding: 0.5em 1em;
            border: none;
            border-radius: 5px;
            font-size: 0.9em;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            transition: background-color 0.2s, transform 0.1s;
            width: 100%;
            margin-bottom: 0.6em;
        }

        .btn:active {
            transform: scale(0.97);
        }

        .btn-primary {
            background-color: #5a7fa0;
            color: white;
        }

        .btn-primary:hover {
            background-color: #4a6d8c;
        }

        .btn-success {
            background-color: #28a745;
            color: white;
        }

        .btn-success:hover {
            background-color: #218838;
        }

        .btn-info {
            background-color: #5a7fa0;
            color: white;
        }

        .btn-info:hover {
            background-color: #4a6d8c;
        }

        .link-apri {
            display: block;
            text-align: center;
            color: #1a3e5c;
            font-weight: 600;
            text-decoration: underline;
            margin-bottom: 0.6em;
            font-size: 0.9em;
            cursor: pointer;
        }

        .link-apri:hover {
            color: #0f2942;
        }

        .prospetti-creati {
            font-style: italic;
            color: #c67600;
            font-size: 0.9em;
            margin-top: 0.4em;
        }

        .msg-box {
            padding: 0.7em;
            border-radius: 5px;
            margin-top: 0.8em;
            font-size: 0.9em;
            display: none;
        }

        .msg-box.successo {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            display: block;
        }

        .msg-box.errore {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            display: block;
        }

        .msg-box.info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
            display: block;
        }

        .msg-errore-php {
            margin-top: 0.6em;
            padding: 0.5em;
            background-color: #ffe6e6;
            border-left: 4px solid #d32f2f;
            color: #d32f2f;
            border-radius: 4px;
            font-size: 0.85em;
        }

        .pannello-selezione {
            margin-top: 0.8em;
            padding: 0.8em;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            display: none;
        }

        .pannello-selezione h3 {
            margin-top: 0;
            font-size: 0.95em;
        }

        .lista-matricole {
            max-height: 180px;
            overflow-y: auto;
            padding: 0.3em;
            margin-bottom: 0.6em;
        }

        .lista-matricole label {
            display: block;
            font-weight: normal;
            text-decoration: none;
            padding: 0.25em 0;
            cursor: pointer;
            font-size: 0.9em;
        }

        .lista-matricole label:hover {
            background-color: #e9ecef;
        }

        .lista-matricole input[type="checkbox"] {
            margin-right: 0.5em;
            width: auto;
        }

        .progress-bar-container {
            margin-top: 0.5em;
            background-color: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
            height: 24px;
            display: none;
            position: relative;
        }

        .progress-bar {
            height: 100%;
            background-color: #28a745;
            transition: width 0.3s ease;
            width: 0%;
        }

        .progress-label {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            text-align: center;
            line-height: 24px;
            font-size: 0.8em;
            font-weight: 600;
            color: #333;
        }

        /* Responsive: sotto i 900px, torna a colonna singola */
        @media (max-width: 900px) {
            .layout-tre-colonne {
                flex-direction: column;
            }
            .colonna-sx, .colonna-centro, .colonna-dx {
                flex: 1 1 auto;
                min-width: 0;
            }
        }
    </style>
</head>
<body>

<h1 class="page-title">Gestione Prospetti di Laurea</h1>

<div class="container">
    <form id="form-genera" method="post" action="generaProspetti.php">
    <div class="layout-tre-colonne">

        <!-- ===== COLONNA SINISTRA: CdL + Data ===== -->
        <div class="colonna-sx">
            <label for="cdl">CdL:</label>
            <select id="cdl" name="cdl" required>
                <option value="">-- Seleziona CdL --</option>
                <option value="T. Ing. Informatica">T. Ing. Informatica</option>
                <option value="T. Ing. Biomedica">T. Ing. Biomedica</option>
                <option value="T. Ing. Elettronica">T. Ing. Elettronica</option>
                <option value="T. Ing. delle Telecomunicazioni">T. Ing. delle Telecomunicazioni</option>
                <option value="M. Ing. Biomedica, Bionics Engineering">M. Ing. Biomedica, Bionics Eng.</option>
                <option value="M. Ing. Elettronica">M. Ing. Elettronica</option>
                <option value="M. Computer Engineering, Artificial Intelligence and Data Engineering">M. Computer Eng., AI and Data Eng.</option>
                <option value="M. Ing. Robotica e della Automazione">M. Ing. Robotica e Automazione</option>
                <option value="M. Ing. delle Telecomunicazioni">M. Ing. delle Telecomunicazioni</option>
            </select>

            <label for="data_laurea">Data Laurea:</label>
            <input type="date" id="data_laurea" name="data_laurea" required>
        </div>

        <!-- ===== COLONNA CENTRALE: Matricole ===== -->
        <div class="colonna-centro">
            <label for="matricole">Matricole:</label>
            <textarea id="matricole" name="matricole"
                      placeholder="Separate da spazi, virgole o a capo"
                      required></textarea>

            <div id="messaggio-stato" class="msg-box"></div>
        </div>

        <!-- ===== COLONNA DESTRA: Azioni + Invio ===== -->
        <div class="colonna-dx">
            <button type="submit" class="btn btn-primary">Crea Prospetti</button>

            <!-- Link Visualizza Prospetti (UC2) -->
            <?php
            require_once __DIR__ . '/classi/AccessoProspetti.php';
            require_once __DIR__ . '/classi/Configurazione.php';

            $datiInvioPath = __DIR__ . '/data/dati_invio.json';
            $prospettiDisponibili = false;
            $messaggioErrore = '';
            $percorso = '';

            if (!file_exists($datiInvioPath)) {
                $messaggioErrore = "Nessun prospetto generato.";
            } else {
                $datiInvio = json_decode(file_get_contents($datiInvioPath), true);
                if (!$datiInvio || !isset($datiInvio['corsoDiLaurea'])) {
                    $messaggioErrore = "Dati invio corrotti. Rigenerare i prospetti.";
                } else {
                    try {
                        $config = new Configurazione($datiInvio['corsoDiLaurea']);
                        $cdlShort = $config->getCdlShort();
                        $accesso = new AccessoProspetti($cdlShort);
                        $percorso = $accesso->getPercorsoCommissione();

                        if (!$accesso->esisteProspettoCommissione()) {
                            $messaggioErrore = "Nessun prospetto disponibile per questo CdL.";
                        } else {
                            $prospettiDisponibili = true;
                        }
                    } catch (Exception $e) {
                        $messaggioErrore = "Errore configurazione: " . htmlspecialchars($e->getMessage());
                    }
                }
            }
            ?>
            <a href="<?php echo htmlspecialchars($percorso); ?>"
               class="link-apri" id="link-visualizza"
               <?php echo $prospettiDisponibili ? '' : 'style="opacity:0.5;pointer-events:none;"'; ?>
               target="_blank">
                apri prospetti
            </a>

            <?php if ($prospettiDisponibili): ?>
                <div class="prospetti-creati">Prospetti creati</div>
            <?php endif; ?>

            <?php if ($messaggioErrore): ?>
                <div class="msg-errore-php">
                    <strong>Attenzione:</strong> <?php echo htmlspecialchars($messaggioErrore); ?>
                </div>
            <?php endif; ?>

            <!-- Pannello selezione matricole (UC3) -->
            <div id="pannello-selezione" class="pannello-selezione">
                <h3>Invia prospetti a:</h3>
                <div id="lista-matricole" class="lista-matricole"></div>
                <button type="button" class="btn btn-success" id="btn-invia-selezionati">
                    Invia ai Selezionati
                </button>

                <!-- Barra di progresso -->
                <div id="progress-container" class="progress-bar-container">
                    <div id="progress-bar" class="progress-bar"></div>
                    <div id="progress-label" class="progress-label"></div>
                </div>
            </div>

            <div id="messaggio-invio" class="msg-box"></div>
        </div>

    </div>
    </form>
</div>

<script>
    // ===== AJAX per generazione prospetti (UC1) =====
    document.getElementById('form-genera').addEventListener('submit', function(e) {
        e.preventDefault();
        var msgBox = document.getElementById('messaggio-stato');
        msgBox.className = 'msg-box info';
        msgBox.textContent = 'Generazione in corso...';

        // Nascondi pannello selezione e messaggi invio durante nuova generazione
        document.getElementById('pannello-selezione').style.display = 'none';
        document.getElementById('messaggio-invio').style.display = 'none';

        var formData = new FormData(this);

        fetch('generaProspetti.php', {
            method: 'POST',
            body: formData
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.successo) {
                msgBox.className = 'msg-box successo';
                msgBox.textContent = data.messaggio;

                // Aggiorna dinamicamente il link Visualizza Prospetti con il nuovo CdL
                var link = document.getElementById('link-visualizza');
                if (data.cdlShort) {
                    link.href = '/laureandosi/data/pdf_generati/' + data.cdlShort + '/prospettoCommissione.pdf';
                }
                link.style.opacity = '1';
                link.style.pointerEvents = 'auto';

                // Mostra pannello selezione matricole per l'invio
                if (data.matricole && data.matricole.length > 0) {
                    mostraPannelloSelezione(data.matricole);
                }
            } else {
                msgBox.className = 'msg-box errore';
                msgBox.textContent = data.messaggio;
            }
        })
        .catch(function(errore) {
            msgBox.className = 'msg-box errore';
            msgBox.textContent = 'Errore di comunicazione con il server.';
        });
    });

    /**
     * Mostra il pannello con le checkbox per la selezione delle matricole.
     * Tutte le matricole sono pre-selezionate.
     */
    function mostraPannelloSelezione(matricole) {
        var pannello = document.getElementById('pannello-selezione');
        var lista = document.getElementById('lista-matricole');
        lista.innerHTML = '';

        matricole.forEach(function(matricola) {
            var lbl = document.createElement('label');
            var checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.name = 'matricole_invio';
            checkbox.value = matricola;
            checkbox.checked = true;
            lbl.appendChild(checkbox);
            lbl.appendChild(document.createTextNode('Matricola ' + matricola));
            lista.appendChild(lbl);
        });

        // Reset progressi
        document.getElementById('progress-container').style.display = 'none';
        document.getElementById('messaggio-invio').style.display = 'none';

        pannello.style.display = 'block';
    }

    // ===== AJAX per invio prospetti uno alla volta con progresso (UC3) =====
    document.getElementById('btn-invia-selezionati').addEventListener('click', function() {
        var checkboxes = document.querySelectorAll('input[name="matricole_invio"]:checked');
        var selezionati = [];
        checkboxes.forEach(function(cb) { selezionati.push(cb.value); });

        if (selezionati.length === 0) {
            var msgBox = document.getElementById('messaggio-invio');
            msgBox.className = 'msg-box errore';
            msgBox.textContent = 'Selezionare almeno una matricola.';
            return;
        }

        inviaProspettiProgressivo(selezionati);
    });

    /**
     * Invia i prospetti uno alla volta, aggiornando la barra di progresso.
     * @param {Array} matricole Array di matricole selezionate
     */
    function inviaProspettiProgressivo(matricole) {
        var totale = matricole.length;
        var inviati = 0;
        var errori = 0;
        var dettagliErrori = [];

        // Disabilita il pulsante durante l'invio
        var btnInvia = document.getElementById('btn-invia-selezionati');
        btnInvia.disabled = true;
        btnInvia.textContent = 'Invio in corso...';

        // Mostra barra di progresso
        var progressContainer = document.getElementById('progress-container');
        var progressBar = document.getElementById('progress-bar');
        var progressLabel = document.getElementById('progress-label');
        progressContainer.style.display = 'block';
        progressBar.style.width = '0%';
        progressLabel.textContent = '0 / ' + totale;

        var msgBox = document.getElementById('messaggio-invio');
        msgBox.style.display = 'none';

        // Invia uno alla volta con intervallo
        var indice = 0;

        function inviaSuccessivo() {
            if (indice >= totale) {
                // Completato
                btnInvia.disabled = false;
                btnInvia.textContent = 'Invia ai Selezionati';

                var messaggio = 'Invio completato: ' + inviati + ' su ' + totale + '.';
                if (errori > 0) {
                    messaggio += ' Errori: ' + errori + '.';
                    dettagliErrori.forEach(function(d) {
                        messaggio += ' ' + d.matricola + ': ' + d.messaggio + ';';
                    });
                }
                msgBox.className = errori === 0 ? 'msg-box successo' : 'msg-box errore';
                msgBox.textContent = messaggio;
                return;
            }

            var matricola = matricole[indice];
            var formData = new FormData();
            formData.append('matricole[]', matricola);

            fetch('inviaProspetti.php', {
                method: 'POST',
                body: formData
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                indice++;
                if (data.successo) {
                    inviati++;
                } else {
                    errori++;
                    if (data.dettagli && data.dettagli.length > 0) {
                        dettagliErrori.push(data.dettagli[0]);
                    } else {
                        dettagliErrori.push({ matricola: matricola, messaggio: data.messaggio });
                    }
                }

                // Aggiorna barra di progresso
                var percentuale = Math.round((indice / totale) * 100);
                progressBar.style.width = percentuale + '%';
                progressLabel.textContent = indice + ' / ' + totale;

                // Prossimo invio dopo 3 secondi
                if (indice < totale) {
                    setTimeout(inviaSuccessivo, 3000);
                } else {
                    inviaSuccessivo(); // Chiama per mostrare il messaggio finale
                }
            })
            .catch(function(errore) {
                indice++;
                errori++;
                dettagliErrori.push({ matricola: matricola, messaggio: 'Errore di comunicazione' });

                var percentuale = Math.round((indice / totale) * 100);
                progressBar.style.width = percentuale + '%';
                progressLabel.textContent = indice + ' / ' + totale;

                if (indice < totale) {
                    setTimeout(inviaSuccessivo, 3000);
                } else {
                    inviaSuccessivo();
                }
            });
        }

        inviaSuccessivo();
    }
</script>

</body>
</html>
