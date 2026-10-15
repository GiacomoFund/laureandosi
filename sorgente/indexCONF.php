<?php
/**
 * indexCONF.php
 * Pannello di configurazione per l'amministratore.
 * Permette di modificare: formula, oggetto/testo mail, pedice commissione,
 * valore lode, esami informatici e parametri vari.
 *
 * Utilizza POST per le modifiche.
 */

require_once __DIR__ . '/classi/Configurazione.php';

$messaggio = '';
$tipoMessaggio = '';

// Gestione salvataggio configurazione (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['azione'])) {
    try {
        $cdl = trim($_POST['cdl'] ?? '');
        if (empty($cdl)) {
            throw new InvalidArgumentException("Selezionare un Corso di Laurea.");
        }

        $config = new Configurazione($cdl);
        $azione = $_POST['azione'];

        // Salvataggio sezione "Calcolo e reportistica"
        if ($azione === 'salva_calcolo') {
            if (isset($_POST['formula']) && !empty(trim($_POST['formula']))) {
                $config->setFormula(trim($_POST['formula']));
            }
            if (isset($_POST['valore_lode']) && is_numeric($_POST['valore_lode'])) {
                $config->setValoreLode(floatval($_POST['valore_lode']));
            }
            if (isset($_POST['cfu_richiesti']) && is_numeric($_POST['cfu_richiesti'])) {
                $config->setCfuRichiesti(intval($_POST['cfu_richiesti']));
            }
            $config->setParametriSimulazione(
                intval($_POST['tmin'] ?? 0),
                intval($_POST['tmax'] ?? 0),
                intval($_POST['tstep'] ?? 0),
                intval($_POST['cmin'] ?? 0),
                intval($_POST['cmax'] ?? 0),
                intval($_POST['cstep'] ?? 0)
            );
            if (isset($_POST['oggetto_mail'])) {
                $config->setOggettoMail(trim($_POST['oggetto_mail']));
            }
            if (isset($_POST['testo_mail'])) {
                $config->setTestoMail(trim($_POST['testo_mail']));
            }
            if (isset($_POST['pedice_commissione'])) {
                $config->setPediceCommissione(trim($_POST['pedice_commissione']));
            }
            $config->salvaConfigurazione();
            $messaggio = "Configurazione 'Calcolo e reportistica' salvata con successo per: " . htmlspecialchars($cdl);
            $tipoMessaggio = 'successo';
        }

        // Salvataggio sezione "Filtro esami"
        elseif ($azione === 'salva_filtro') {
            if (isset($_POST['esami_da_togliere'])) {
                $esami = array_map('trim', explode("\n", trim($_POST['esami_da_togliere'])));
                $esami = array_filter($esami, function($e) { return !empty($e); });
                $config->setEsamiDaTogliere(array_values($esami));
            }
            if (isset($_POST['esami_non_media'])) {
                $esami = array_map('trim', explode("\n", trim($_POST['esami_non_media'])));
                $esami = array_filter($esami, function($e) { return !empty($e); });
                $config->setEsamiNonMedia(array_values($esami));
            }
            $config->salvaConfigurazione();
            $messaggio = "Configurazione 'Filtro esami' salvata con successo per: " . htmlspecialchars($cdl);
            $tipoMessaggio = 'successo';
        }

        // Salvataggio sezione "Esami informatici"
        elseif ($azione === 'salva_informatici') {
            if (isset($_POST['esami_informatici']) && !empty(trim($_POST['esami_informatici']))) {
                $esami = array_map('trim', explode("\n", trim($_POST['esami_informatici'])));
                $esami = array_filter($esami, function($e) { return !empty($e); });
                $config->setEsamiInformatici(array_values($esami));
            }
            $config->salvaConfigurazione();
            $messaggio = "Configurazione 'Esami informatici' salvata con successo per: " . htmlspecialchars($cdl);
            $tipoMessaggio = 'successo';
        }

    } catch (Exception $e) {
        $messaggio = "Errore: " . htmlspecialchars($e->getMessage());
        $tipoMessaggio = 'errore';
    }
}

// Carica configurazione corrente per visualizzazione
$cdlSelezionato = '';
$configCorrente = null;

if (isset($_POST['cdl']) && !empty($_POST['cdl'])) {
    $cdlSelezionato = trim($_POST['cdl']);
} elseif (isset($_GET['cdl']) && !empty($_GET['cdl'])) {
    $cdlSelezionato = trim($_GET['cdl']);
}

if (!empty($cdlSelezionato)) {
    try {
        $configCorrente = new Configurazione($cdlSelezionato);
    } catch (Exception $e) {
        $messaggio = "Errore nel caricamento: " . htmlspecialchars($e->getMessage());
        $tipoMessaggio = 'errore';
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laureandosi - Configurazione</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #ecf0f3;
            color: #333;
        }
        header {
            background-color: #1a3e5c;
            color: white;
            padding: 1.5em;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        header h1 { font-size: 1.5em; }
        main { max-width: 900px; margin: 2em auto; padding: 0 1em; }
        .card {
            background: white;
            border-radius: 6px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            padding: 1.5em;
            margin-bottom: 1.5em;
        }
        .card h2 {
            font-size: 1.15em;
            margin-bottom: 1em;
            color: #1a3e5c;
            border-bottom: 2px solid #d0d7e0;
            padding-bottom: 0.5em;
        }
        .card h3 {
            font-size: 1em;
            margin: 1.5em 0 0.8em 0;
            color: #1a3e5c;
            font-weight: 600;
        }
        label {
            display: block;
            font-weight: 500;
            margin-bottom: 0.4em;
            color: #555;
            font-size: 0.95em;
        }
        select, textarea, input[type="text"], input[type="number"] {
            width: 100%;
            padding: 0.6em;
            border: 1px solid #d0d7e0;
            border-radius: 4px;
            font-size: 0.95em;
            margin-bottom: 1em;
            background: #f8fafb;
        }
        select:focus, textarea:focus, input:focus {
            outline: none;
            border-color: #1a3e5c;
            background: white;
        }
        textarea {
            min-height: 60px;
            resize: vertical;
            font-family: 'Courier New', monospace;
            line-height: 1.5;
        }
        small {
            font-size: 0.85em;
            color: #666;
        }
        .btn {
            padding: 0.7em 1.5em;
            border: none;
            border-radius: 5px;
            font-size: 0.95em;
            font-weight: 600;
            cursor: pointer;
            color: white;
            transition: background 0.2s;
        }
        .btn-primary { background-color: #1a3e5c; }
        .btn-primary:hover { background-color: #0f2942; }
        .btn-success { background-color: #27ae60; }
        .btn-success:hover { background-color: #229954; }
        .msg-successo {
            background: #d4edda; color: #155724;
            border: 1px solid #c3e6cb; padding: 1em;
            border-radius: 5px; margin-bottom: 1em;
        }
        .msg-errore {
            background: #f8d7da; color: #721c24;
            border: 1px solid #f5c6cb; padding: 1em;
            border-radius: 5px; margin-bottom: 1em;
        }
        .sezione-form {
            margin-top: 1.5em;
            padding-top: 1em;
            border-top: 1px solid #e0e4e8;
        }
        footer {
            text-align: center;
            padding: 2em;
            color: #7f8c8d;
            font-size: 0.85em;
        }
    </style>
</head>
<body>

<header>
    <h1>Configurazione Laureandosi</h1>
</header>

<main>

    <?php if (!empty($messaggio)): ?>
        <div class="msg-<?php echo $tipoMessaggio; ?>">
            <?php echo $messaggio; ?>
        </div>
    <?php endif; ?>

    <!-- Selettore CdL per caricare configurazione -->
    <div class="card">
        <h2>Seleziona Corso di Laurea</h2>
        <form method="post" action="indexCONF.php">
            <label for="cdl_select">CdL:</label>
            <select id="cdl_select" name="cdl" onchange="this.form.submit()">
                <option value="">-- Seleziona --</option>
                <?php
                $corsi = Configurazione::getCorsiDisponibili();
                foreach ($corsi as $corso):
                    $selected = ($corso === $cdlSelezionato) ? 'selected' : '';
                ?>
                    <option value="<?php echo htmlspecialchars($corso); ?>" <?php echo $selected; ?>>
                        <?php echo htmlspecialchars($corso); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <!-- Modifica configurazione -->
    <?php if ($configCorrente !== null):
        $parametriSimulazione = $configCorrente->getParametriSimulazione();
        $parametriMail = $configCorrente->getParametriMail();
        $isInformatica = ($cdlSelezionato === 'T. Ing. Informatica');
    ?>
    <div class="card">
        <h2>Configurazione: <?php echo htmlspecialchars($cdlSelezionato); ?></h2>

        <!-- Submenu selezione sezione -->
        <label for="sezione_select">Sezione di configurazione:</label>
        <select id="sezione_select" name="sezione" onchange="mostraSezione(this.value)">
            <option value="">-- Seleziona sezione --</option>
            <option value="calcolo">Calcolo e reportistica</option>
            <option value="filtro">Filtro esami</option>
            <?php if ($isInformatica): ?>
            <option value="informatici">Esami informatici</option>
            <?php endif; ?>
        </select>

        <!-- Sezione: Calcolo e reportistica -->
        <form method="post" action="indexCONF.php" id="form_calcolo" class="sezione-form" style="display:none;">
            <input type="hidden" name="azione" value="salva_calcolo">
            <input type="hidden" name="cdl" value="<?php echo htmlspecialchars($cdlSelezionato); ?>">

            <h3>Calcolo e reportistica</h3>

            <label for="formula">Formula calcolo voto di laurea:</label>
            <input type="text" id="formula" name="formula"
                   value="<?php echo htmlspecialchars($configCorrente->getFormula()); ?>">

            <label for="valore_lode">Valore della Lode:</label>
            <input type="number" id="valore_lode" name="valore_lode" step="0.5"
                   value="<?php echo htmlspecialchars($configCorrente->getValoreLode()); ?>">

            <label for="cfu_richiesti">CFU richiesti:</label>
            <input type="number" id="cfu_richiesti" name="cfu_richiesti"
                   value="<?php echo htmlspecialchars($configCorrente->getCfuRichiesti()); ?>">

            <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap: 0.5em;">
                <div>
                    <label for="tmin">Tmin:</label>
                    <input type="number" id="tmin" name="tmin" value="<?php echo $parametriSimulazione['Tmin']; ?>">
                </div>
                <div>
                    <label for="tmax">Tmax:</label>
                    <input type="number" id="tmax" name="tmax" value="<?php echo $parametriSimulazione['Tmax']; ?>">
                </div>
                <div>
                    <label for="tstep">Tstep:</label>
                    <input type="number" id="tstep" name="tstep" value="<?php echo $parametriSimulazione['Tstep']; ?>">
                </div>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap: 0.5em;">
                <div>
                    <label for="cmin">Cmin:</label>
                    <input type="number" id="cmin" name="cmin" value="<?php echo $parametriSimulazione['Cmin']; ?>">
                </div>
                <div>
                    <label for="cmax">Cmax:</label>
                    <input type="number" id="cmax" name="cmax" value="<?php echo $parametriSimulazione['Cmax']; ?>">
                </div>
                <div>
                    <label for="cstep">Cstep:</label>
                    <input type="number" id="cstep" name="cstep" value="<?php echo $parametriSimulazione['Cstep']; ?>">
                </div>
            </div>

            <label for="oggetto_mail">Oggetto mail:</label>
            <input type="text" id="oggetto_mail" name="oggetto_mail"
                   value="<?php echo htmlspecialchars($parametriMail['oggetto']); ?>">

            <label for="testo_mail">Testo mail:</label>
            <textarea id="testo_mail" name="testo_mail" rows="6"><?php echo htmlspecialchars($parametriMail['testo']); ?></textarea>

            <label for="pedice_commissione">Pedice commissione:</label>
            <textarea id="pedice_commissione" name="pedice_commissione" rows="3"><?php echo htmlspecialchars($configCorrente->getPediceCommissione()); ?></textarea>

            <button type="submit" class="btn btn-success">Salva Calcolo e reportistica</button>
        </form>

        <!-- Sezione: Filtro esami -->
        <form method="post" action="indexCONF.php" id="form_filtro" class="sezione-form" style="display:none;">
            <input type="hidden" name="azione" value="salva_filtro">
            <input type="hidden" name="cdl" value="<?php echo htmlspecialchars($cdlSelezionato); ?>">

            <h3>Filtro esami</h3>

            <p>I filtri si applicano a tutti gli studenti del corso selezionato.</p>

            <label for="esami_da_togliere">Esami da escludere dal prospetto (uno per riga):</label>
            <textarea id="esami_da_togliere" name="esami_da_togliere" rows="4"><?php echo htmlspecialchars(implode("\n", $configCorrente->getEsamiDaTogliere())); ?></textarea>

            <label for="esami_non_media">Esami che non fanno media (uno per riga):</label>
            <textarea id="esami_non_media" name="esami_non_media" rows="4"><?php echo htmlspecialchars(implode("\n", $configCorrente->getEsamiNonMedia())); ?></textarea>

            <button type="submit" class="btn btn-success">Salva Filtro esami</button>
        </form>

        <!-- Sezione: Esami informatici (solo per T. Ing. Informatica) -->
        <?php if ($isInformatica): ?>
        <form method="post" action="indexCONF.php" id="form_informatici" class="sezione-form" style="display:none;">
            <input type="hidden" name="azione" value="salva_informatici">
            <input type="hidden" name="cdl" value="<?php echo htmlspecialchars($cdlSelezionato); ?>">

            <h3>Esami informatici</h3>

            <label for="esami_informatici">Esami informatici (uno per riga):</label>
            <textarea id="esami_informatici" name="esami_informatici" rows="8"><?php echo htmlspecialchars(implode("\n", $configCorrente->getEsamiInformatici())); ?></textarea>
            <small style="color:#666; display:block; margin-top:-0.5em; margin-bottom:1em;">
                Lista degli esami che danno diritto al bonus informatica per studenti in corso
            </small>

            <button type="submit" class="btn btn-success">Salva Esami informatici</button>
        </form>
        <?php endif; ?>
    </div>

    <script>
    function mostraSezione(sezione) {
        // Nascondi tutti i form
        var forms = document.querySelectorAll('.sezione-form');
        forms.forEach(function(form) {
            form.style.display = 'none';
        });

        // Mostra il form selezionato
        if (sezione === 'calcolo') {
            document.getElementById('form_calcolo').style.display = 'block';
        } else if (sezione === 'filtro') {
            document.getElementById('form_filtro').style.display = 'block';
        } else if (sezione === 'informatici') {
            document.getElementById('form_informatici').style.display = 'block';
        }
    }
    </script>
    <?php endif; ?>

    <!-- Sezione: Eliminazione dati generati -->
    <div class="card" style="border-left: 4px solid #e74c3c;">
        <h2 style="color: #e74c3c;">Eliminazione dati generati</h2>
        <p style="margin-bottom: 1em; font-size: 0.9em; color: #555;">
            Elimina tutti i file PDF e i dati temporanei dalla cartella di output.
            Questa operazione e' irreversibile e rimuove prospetti di tutti i Corsi di Laurea.
        </p>
        <button type="button" class="btn" id="btn-elimina-dati"
                style="background-color: #e74c3c;"
                onclick="eliminaDatiGenerati()">
            Elimina tutti i dati generati
        </button>
        <div id="msg-eliminazione" style="margin-top: 0.8em; display:none;"></div>
    </div>

    <script>
    function eliminaDatiGenerati() {
        if (!confirm('Sei sicuro di voler eliminare tutti i PDF e i dati generati?\nQuesta operazione e\' irreversibile.')) {
            return;
        }

        var btn = document.getElementById('btn-elimina-dati');
        var msgBox = document.getElementById('msg-eliminazione');
        btn.disabled = true;
        btn.textContent = 'Eliminazione in corso...';
        msgBox.style.display = 'none';

        var formData = new FormData();
        formData.append('azione', 'elimina_dati');

        fetch('API.php', {
            method: 'POST',
            body: formData
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            btn.disabled = false;
            btn.textContent = 'Elimina tutti i dati generati';
            msgBox.style.display = 'block';
            if (data.successo) {
                msgBox.className = 'msg-successo';
                msgBox.textContent = data.messaggio;
            } else {
                msgBox.className = 'msg-errore';
                msgBox.textContent = data.errore || data.messaggio || 'Errore sconosciuto.';
            }
        })
        .catch(function(err) {
            btn.disabled = false;
            btn.textContent = 'Elimina tutti i dati generati';
            msgBox.style.display = 'block';
            msgBox.className = 'msg-errore';
            msgBox.textContent = 'Errore di comunicazione con il server.';
        });
    }
    </script>

</main>

<footer>
    Laureandosi &copy; DII - Universit&agrave; di Pisa
</footer>

</body>
</html>
