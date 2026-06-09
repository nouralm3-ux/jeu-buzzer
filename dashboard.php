<?php
session_start();
require_once 'includes/auth.php';
requireLogin();
require_once 'includes/config.php';

$pdo = getDB();

// ── Handle POST actions ──
$flash     = '';
$flashType = 'success';

// Lire le flash depuis la session (après redirect)
if (!empty($_SESSION['flash'])) {
    $flash     = $_SESSION['flash'];
    $flashType = $_SESSION['flash_type'] ?? 'success';
    unset($_SESSION['flash'], $_SESSION['flash_type']);
}

function redirectWithFlash(string $tab, string $msg, string $type = 'success'): never {
    $_SESSION['flash']      = $msg;
    $_SESSION['flash_type'] = $type;
    header('Location: dashboard.php#' . $tab);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_sensor') {
        $name = trim($_POST['name'] ?? '');
        $port = trim($_POST['port'] ?? '');
        $baud = (int)($_POST['baud_rate'] ?? 9600);
        $desc = trim($_POST['description'] ?? '');

        if ($name && $port) {
            $pdo->prepare('INSERT INTO sensors (name, port, baud_rate, description) VALUES (?,?,?,?)')
                ->execute([$name, $port, $baud, $desc]);
            redirectWithFlash('sensors', "Capteur \"$name\" ajouté avec succès.");
        } else {
            redirectWithFlash('sensors', 'Nom et port sont obligatoires.', 'error');
        }
    }

    if ($action === 'delete_sensor') {
        $id = (int)($_POST['sensor_id'] ?? 0);
        $pdo->prepare('DELETE FROM sensors WHERE id = ?')->execute([$id]);
        redirectWithFlash('sensors', 'Capteur supprimé.');
    }

    if ($action === 'toggle_sensor') {
        $id = (int)($_POST['sensor_id'] ?? 0);
        $pdo->prepare('UPDATE sensors SET active = NOT active WHERE id = ?')->execute([$id]);
        redirectWithFlash('sensors', 'Statut mis à jour.');
    }

    if ($action === 'delete_data') {
        $sid = (int)($_POST['sensor_id'] ?? 0);
        $pdo->prepare('DELETE FROM sensor_data WHERE sensor_id = ?')->execute([$sid]);
        redirectWithFlash('data', 'Historique effacé.');
    }

    if ($action === 'delete_all_sensors') {
        $pdo->exec('DELETE FROM sensor_data');
        $pdo->exec('DELETE FROM sensors');
        redirectWithFlash('sensors', 'Tous les capteurs et leurs données ont été supprimés.');
    }
}

// ── Fetch data ──
$sensors     = $pdo->query('SELECT * FROM sensors ORDER BY created_at DESC')->fetchAll();
$totalSensors = count($sensors);
$activeSensors = count(array_filter($sensors, fn($s) => $s['active']));
$totalData    = $pdo->query('SELECT COUNT(*) FROM sensor_data')->fetchColumn();

$selectedSensorId = (int)($_GET['sensor'] ?? ($sensors[0]['id'] ?? 0));
$selectedSensor   = null;
$sensorData       = [];
$sensorCounts     = [];

if ($selectedSensorId) {
    foreach ($sensors as $s) {
        if ($s['id'] === $selectedSensorId) { $selectedSensor = $s; break; }
    }
    $stmt = $pdo->prepare('SELECT * FROM sensor_data WHERE sensor_id = ? ORDER BY recorded_at DESC LIMIT 100');
    $stmt->execute([$selectedSensorId]);
    $sensorData = $stmt->fetchAll();
}

foreach ($sensors as $s) {
    $cnt = $pdo->prepare('SELECT COUNT(*) FROM sensor_data WHERE sensor_id = ?');
    $cnt->execute([$s['id']]);
    $sensorCounts[] = (int)$cnt->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — SensorHub</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>

<nav class="navbar">
    <div class="logo">&#9881; <span>Sensor</span>Hub</div>
    <div class="nav-links">
        <span style="font-size:0.85rem; color:var(--text-muted);">
            &#128100; <?= htmlspecialchars($_SESSION['username']) ?>
        </span>
        <a href="index.php" class="btn btn-outline">Accueil</a>
        <a href="logout.php" class="btn btn-outline">Déconnexion</a>
    </div>
</nav>

<div class="dashboard">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-section">Navigation</div>
        <button class="sidebar-item active" onclick="showTab('overview')">
            &#128202; Vue d'ensemble
        </button>
        <button class="sidebar-item" onclick="showTab('sensors')">
            &#128268; Capteurs
        </button>
        <button class="sidebar-item" onclick="showTab('data')">
            &#128190; Données
        </button>
        <button class="sidebar-item" onclick="showTab('serial')">
            &#9654; Console série
        </button>
    </aside>

    <!-- MAIN -->
    <main class="main-content">

        <?php if ($flash): ?>
            <div class="alert alert-<?= $flashType ?>" style="margin-bottom:1.5rem;">
                <?= htmlspecialchars($flash) ?>
            </div>
        <?php endif; ?>

        <!-- ══ OVERVIEW TAB ══ -->
        <div id="tab-overview" class="tab-panel active">
            <div class="page-header">
                <h1>Vue d'ensemble</h1>
                <span style="color:var(--text-muted); font-size:0.875rem;">
                    Mis à jour : <?= date('d/m/Y H:i:s') ?>
                </span>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Capteurs total</div>
                    <div class="stat-value purple"><?= $totalSensors ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Capteurs actifs</div>
                    <div class="stat-value green"><?= $activeSensors ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Données enregistrées</div>
                    <div class="stat-value purple"><?= number_format($totalData) ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Capteurs inactifs</div>
                    <div class="stat-value red"><?= $totalSensors - $activeSensors ?></div>
                </div>
            </div>

            <div class="chart-wrap">
                <h3>&#128202; Lectures récentes — tous capteurs</h3>
                <canvas id="overviewChart" height="100"></canvas>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Capteur</th>
                            <th>Port</th>
                            <th>Baud</th>
                            <th>Statut</th>
                            <th>Dernière lecture</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sensors as $s): ?>
                        <?php
                            $last = $pdo->prepare('SELECT recorded_at, raw_value FROM sensor_data WHERE sensor_id=? ORDER BY recorded_at DESC LIMIT 1');
                            $last->execute([$s['id']]);
                            $last = $last->fetch();
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($s['name']) ?></strong></td>
                            <td><code style="background:var(--surface2);padding:2px 6px;border-radius:4px;"><?= htmlspecialchars($s['port']) ?></code></td>
                            <td><?= $s['baud_rate'] ?></td>
                            <td>
                                <?php if ($s['active']): ?>
                                    <span class="badge badge-green">Actif</span>
                                <?php else: ?>
                                    <span class="badge badge-gray">Inactif</span>
                                <?php endif; ?>
                            </td>
                            <td style="color:var(--text-muted);">
                                <?= $last ? htmlspecialchars(substr($last['raw_value'],0,30)).'… — '.date('d/m H:i', strtotime($last['recorded_at'])) : '—' ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (!$sensors): ?>
                        <tr><td colspan="5" style="text-align:center;color:var(--text-muted);padding:2rem;">
                            Aucun capteur configuré. <a href="#" onclick="showTab('sensors')">Ajouter un capteur</a>
                        </td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ══ SENSORS TAB ══ -->
        <div id="tab-sensors" class="tab-panel">
            <div class="page-header">
                <h1>Gestion des capteurs</h1>
                <div style="display:flex;gap:0.75rem;">
                    <form method="POST" onsubmit="return confirm('Supprimer TOUS les capteurs et toutes leurs données ?');">
                        <input type="hidden" name="action" value="delete_all_sensors">
                        <button type="submit" class="btn btn-danger">&#128465; Tout vider</button>
                    </form>
                    <button class="btn btn-primary" onclick="openModal('modal-add')">
                        &#43; Ajouter un capteur
                    </button>
                </div>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nom</th>
                            <th>Port série</th>
                            <th>Baud rate</th>
                            <th>Description</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sensors as $s): ?>
                        <tr>
                            <td style="color:var(--text-muted);"><?= $s['id'] ?></td>
                            <td><strong><?= htmlspecialchars($s['name']) ?></strong></td>
                            <td><code style="background:var(--surface2);padding:2px 6px;border-radius:4px;"><?= htmlspecialchars($s['port']) ?></code></td>
                            <td><?= $s['baud_rate'] ?> bps</td>
                            <td style="color:var(--text-muted);"><?= htmlspecialchars($s['description'] ?: '—') ?></td>
                            <td>
                                <?php if ($s['active']): ?>
                                    <span class="badge badge-green">Actif</span>
                                <?php else: ?>
                                    <span class="badge badge-gray">Inactif</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display:flex;gap:0.4rem;">
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="toggle_sensor">
                                        <input type="hidden" name="sensor_id" value="<?= $s['id'] ?>">
                                        <button type="submit" class="btn btn-outline" style="padding:0.35rem 0.7rem;font-size:0.75rem;">
                                            <?= $s['active'] ? '&#9646;&#9646; Pause' : '&#9654; Activer' ?>
                                        </button>
                                    </form>
                                    <a href="?sensor=<?= $s['id'] ?>" onclick="showTab('serial')" class="btn btn-success" style="padding:0.35rem 0.7rem;font-size:0.75rem;">
                                        &#128268; Lire
                                    </a>
                                    <form method="POST" onsubmit="return confirm('Supprimer ce capteur et toutes ses données ?');">
                                        <input type="hidden" name="action" value="delete_sensor">
                                        <input type="hidden" name="sensor_id" value="<?= $s['id'] ?>">
                                        <button type="submit" class="btn btn-danger" style="padding:0.35rem 0.7rem;font-size:0.75rem;">
                                            &#128465;
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (!$sensors): ?>
                        <tr><td colspan="7" style="text-align:center;color:var(--text-muted);padding:2rem;">
                            Aucun capteur. Cliquez sur "Ajouter un capteur".
                        </td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ══ DATA TAB ══ -->
        <div id="tab-data" class="tab-panel">
            <div class="page-header">
                <h1>Historique des données</h1>
                <div style="display:flex;gap:0.75rem;align-items:center;">
                    <select id="sensorSelect" class="form-group" style="margin:0;padding:0.5rem 1rem;background:var(--surface);border:1px solid var(--border);border-radius:8px;color:var(--text);"
                        onchange="window.location.href='?sensor='+this.value+'#data'" >
                        <?php foreach ($sensors as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= $s['id']==$selectedSensorId ? 'selected' : '' ?>>
                            <?= htmlspecialchars($s['name']) ?> — <?= $s['port'] ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($selectedSensorId): ?>
                    <form method="POST" onsubmit="return confirm('Effacer tout l\'historique ?');">
                        <input type="hidden" name="action" value="delete_data">
                        <input type="hidden" name="sensor_id" value="<?= $selectedSensorId ?>">
                        <button type="submit" class="btn btn-danger" style="padding:0.5rem 1rem;">
                            &#128465; Vider
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($sensorData): ?>
            <div class="chart-wrap">
                <h3>&#128202; Valeurs numériques — <?= htmlspecialchars($selectedSensor['name'] ?? '') ?></h3>
                <canvas id="sensorChart" height="80"></canvas>
            </div>
            <?php endif; ?>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Date / Heure</th>
                            <th>Valeur brute</th>
                            <th>Valeur numérique</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sensorData as $row): ?>
                        <tr>
                            <td style="color:var(--text-muted);"><?= $row['id'] ?></td>
                            <td><?= date('d/m/Y H:i:s', strtotime($row['recorded_at'])) ?></td>
                            <td><code><?= htmlspecialchars(substr($row['raw_value'], 0, 80)) ?></code></td>
                            <td>
                                <?php if ($row['numeric_value'] !== null): ?>
                                    <span class="badge badge-purple"><?= $row['numeric_value'] ?></span>
                                <?php else: ?>
                                    <span style="color:var(--text-muted);">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (!$sensorData): ?>
                        <tr><td colspan="4" style="text-align:center;color:var(--text-muted);padding:2rem;">
                            Aucune donnée enregistrée pour ce capteur.
                        </td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ══ SERIAL CONSOLE TAB ══ -->
        <div id="tab-serial" class="tab-panel">
            <div class="page-header">
                <h1>Console série</h1>
                <div style="display:flex;gap:0.75rem;align-items:center;">
                    <select id="serialSensorSelect" style="padding:0.5rem 1rem;background:var(--surface);border:1px solid var(--border);border-radius:8px;color:var(--text);">
                        <?php foreach ($sensors as $s): ?>
                        <option value="<?= $s['id'] ?>" data-port="<?= htmlspecialchars($s['port']) ?>" data-baud="<?= $s['baud_rate'] ?>" <?= $s['id']==$selectedSensorId ? 'selected' : '' ?>>
                            <?= htmlspecialchars($s['name']) ?> — <?= $s['port'] ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <button id="btnStart" class="btn btn-success" onclick="startReading()">
                        &#9654; Démarrer lecture
                    </button>
                    <button id="btnStop" class="btn btn-danger" onclick="stopReading()" style="display:none;">
                        &#9632; Arrêter
                    </button>
                    <button class="btn btn-outline" onclick="clearConsole()">Effacer</button>
                </div>
            </div>

            <div style="margin-bottom:1rem; display:flex; gap:1rem; align-items:flex-end;">
                <div class="form-group" style="margin:0; flex:1;">
                    <label>Message à envoyer</label>
                    <input type="text" id="serialSendMsg" placeholder="Tapez un message et appuyez Entrée...">
                </div>
                <button class="btn btn-primary" onclick="sendMessage()">&#10148; Envoyer</button>
            </div>

            <div class="console" id="serialConsole">Prêt. Sélectionnez un capteur et démarrez la lecture.
</div>

            <div style="display:flex; gap:1rem; margin-top:1rem;">
                <div class="stat-card" style="flex:1;">
                    <div class="stat-label">Octets reçus</div>
                    <div class="stat-value green" id="statBytes">0</div>
                </div>
                <div class="stat-card" style="flex:1;">
                    <div class="stat-label">Messages lus</div>
                    <div class="stat-value purple" id="statMessages">0</div>
                </div>
                <div class="stat-card" style="flex:1;">
                    <div class="stat-label">Statut</div>
                    <div class="stat-value" id="statStatus" style="font-size:1rem;color:var(--text-muted);">En attente</div>
                </div>
            </div>
        </div>

    </main>
</div>

<!-- ── MODAL: Add Sensor ── -->
<div class="modal-overlay" id="modal-add">
    <div class="modal">
        <h2>&#43; Ajouter un capteur</h2>
        <form method="POST" action="dashboard.php">
            <input type="hidden" name="action" value="add_sensor">
            <div class="form-group">
                <label>Nom du capteur *</label>
                <input type="text" name="name" placeholder="ex: Température salle A" required>
            </div>
            <div class="form-group">
                <label>Port série *</label>
                <input type="text" name="port" placeholder="com3 (Windows) ou /dev/ttyUSB0 (Unix)" required>
            </div>
            <div class="form-group">
                <label>Baud rate</label>
                <select name="baud_rate">
                    <option value="9600" selected>9600</option>
                    <option value="19200">19200</option>
                    <option value="38400">38400</option>
                    <option value="57600">57600</option>
                    <option value="115200">115200</option>
                </select>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" placeholder="Description optionnelle..."></textarea>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-outline" onclick="closeModal('modal-add')">Annuler</button>
                <button type="submit" class="btn btn-primary">Ajouter</button>
            </div>
        </form>
    </div>
</div>

<script>
// ── Tab navigation ──
function showTab(name) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.sidebar-item').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + name).classList.add('active');
    event && event.target.classList.add('active');
}

// ── Modal ──
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.modal-overlay').forEach(o =>
    o.addEventListener('click', e => { if (e.target === o) o.classList.remove('open'); })
);

// ── Overview chart ──
(function () {
    const ctx = document.getElementById('overviewChart');
    if (!ctx) return;
    const labels = <?= json_encode(array_map(fn($s) => $s['name'], $sensors)) ?>;
    const counts = <?= json_encode($sensorCounts) ?>;
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Lectures enregistrées',
                data: counts,
                backgroundColor: 'rgba(108,99,255,0.7)',
                borderColor: '#6c63ff',
                borderWidth: 1,
                borderRadius: 6
            }]
        },
        options: {
            plugins: { legend: { labels: { color: '#e2e8f0' } } },
            scales: {
                x: { ticks: { color: '#8892a4' }, grid: { color: '#2d3148' } },
                y: { ticks: { color: '#8892a4' }, grid: { color: '#2d3148' }, beginAtZero: true }
            }
        }
    });
})();

// ── Sensor data chart ──
(function () {
    const ctx = document.getElementById('sensorChart');
    if (!ctx) return;
    const rows = <?= json_encode(array_reverse($sensorData)) ?>;
    const labels = rows.map(r => r.recorded_at.substring(11, 19));
    const values = rows.map(r => r.numeric_value);
    new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Valeur numérique',
                data: values,
                borderColor: '#00d4aa',
                backgroundColor: 'rgba(0,212,170,0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 3
            }]
        },
        options: {
            plugins: { legend: { labels: { color: '#e2e8f0' } } },
            scales: {
                x: { ticks: { color: '#8892a4', maxTicksLimit: 15 }, grid: { color: '#2d3148' } },
                y: { ticks: { color: '#8892a4' }, grid: { color: '#2d3148' } }
            }
        }
    });
})();

// ── Serial reading via AJAX ──
let readingInterval = null;
let totalBytes = 0;
let totalMessages = 0;

function startReading() {
    document.getElementById('btnStart').style.display = 'none';
    document.getElementById('btnStop').style.display = '';
    document.getElementById('statStatus').textContent = 'Lecture...';
    document.getElementById('statStatus').style.color = 'var(--accent2)';
    readingInterval = setInterval(pollSerial, 2000);
    pollSerial();
}

function stopReading() {
    clearInterval(readingInterval);
    readingInterval = null;
    document.getElementById('btnStart').style.display = '';
    document.getElementById('btnStop').style.display = 'none';
    document.getElementById('statStatus').textContent = 'Arrêté';
    document.getElementById('statStatus').style.color = 'var(--text-muted)';
    appendConsole('[Lecture arrêtée]\n');
}

function pollSerial() {
    const sel = document.getElementById('serialSensorSelect');
    const sensorId = sel.value;
    fetch('serial_read.php?sensor_id=' + sensorId)
        .then(r => r.json())
        .then(data => {
            if (data.error) {
                appendConsole('[ERREUR] ' + data.error + '\n');
                stopReading();
                return;
            }
            if (data.data) {
                appendConsole(data.data);
                totalBytes += data.bytes || data.data.length;
                totalMessages++;
                document.getElementById('statBytes').textContent = totalBytes;
                document.getElementById('statMessages').textContent = totalMessages;
            }
        })
        .catch(e => appendConsole('[Erreur réseau] ' + e.message + '\n'));
}

function sendMessage() {
    const sel    = document.getElementById('serialSensorSelect');
    const msg    = document.getElementById('serialSendMsg');
    const sensorId = sel.value;
    if (!msg.value.trim()) return;
    fetch('serial_write.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'sensor_id=' + encodeURIComponent(sensorId) + '&message=' + encodeURIComponent(msg.value)
    })
    .then(r => r.json())
    .then(d => {
        if (d.sent) appendConsole('[ENVOYÉ] ' + msg.value + '\n');
        else appendConsole('[ERREUR envoi] ' + (d.error || '') + '\n');
        msg.value = '';
    });
}

document.getElementById('serialSendMsg').addEventListener('keydown', e => {
    if (e.key === 'Enter') sendMessage();
});

function appendConsole(text) {
    const c = document.getElementById('serialConsole');
    c.textContent += text;
    c.scrollTop = c.scrollHeight;
}

function clearConsole() {
    document.getElementById('serialConsole').textContent = '';
    totalBytes = 0; totalMessages = 0;
    document.getElementById('statBytes').textContent = '0';
    document.getElementById('statMessages').textContent = '0';
}

// Auto-open tab from URL hash
const hash = window.location.hash.replace('#','');
if (['overview','sensors','data','serial'].includes(hash)) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.sidebar-item').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + hash)?.classList.add('active');
    const idx = ['overview','sensors','data','serial'].indexOf(hash);
    document.querySelectorAll('.sidebar-item')[idx]?.classList.add('active');
}
</script>

</body>
</html>
