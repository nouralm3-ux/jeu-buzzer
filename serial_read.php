<?php
session_start();
require_once 'includes/auth.php';
requireLogin();
require_once 'includes/config.php';

header('Content-Type: application/json');

$sensorId = (int)($_GET['sensor_id'] ?? 0);
if (!$sensorId) {
    echo json_encode(['error' => 'Aucun capteur sélectionné.']);
    exit;
}

$pdo = getDB();
$sensor = $pdo->prepare('SELECT * FROM sensors WHERE id = ? AND active = 1');
$sensor->execute([$sensorId]);
$sensor = $sensor->fetch();

if (!$sensor) {
    echo json_encode(['error' => 'Capteur introuvable ou inactif.']);
    exit;
}

// Check if PHP Direct IO extension is available
if (!function_exists('dio_open')) {
    // Return the latest DB entry pushed by bridge.py (if any)
    $latest = $pdo->prepare(
        'SELECT raw_value, numeric_value, recorded_at FROM sensor_data WHERE sensor_id = ? ORDER BY recorded_at DESC LIMIT 1'
    );
    $latest->execute([$sensorId]);
    $row = $latest->fetch();
    if ($row) {
        echo json_encode([
            'data'       => $row['raw_value'],
            'bytes'      => strlen($row['raw_value']),
            'numeric'    => $row['numeric_value'],
            'recorded_at'=> $row['recorded_at'],
            'from_db'    => true,
        ]);
    } else {
        echo json_encode(['data' => '', 'bytes' => 0, 'from_db' => true]);
    }
    exit;
}

$port     = $sensor['port'];
$baudRate = (int)$sensor['baud_rate'];

$fd = @dio_open($port, O_RDWR | O_NOCTTY | O_NONBLOCK);
if ($fd === false) {
    echo json_encode(['error' => "Impossible d'ouvrir le port $port. Vérifiez la connexion."]);
    exit;
}

dio_tcsetattr($fd, [
    'baud'   => $baudRate,
    'bits'   => 8,
    'stop'   => 1,
    'parity' => 0
]);

$raw = @dio_read($fd, 128);
dio_close($fd);

if ($raw === false || $raw === '') {
    echo json_encode(['data' => '', 'bytes' => 0]);
    exit;
}

$numeric = extractNumeric($raw);
saveData($pdo, $sensorId, $raw, $numeric);

echo json_encode(['data' => $raw, 'bytes' => strlen($raw)]);

// ── Helpers ──

function extractNumeric(string $raw): ?float {
    if (preg_match('/[-+]?\d+(\.\d+)?/', $raw, $m)) {
        return (float)$m[0];
    }
    return null;
}

function saveData(PDO $pdo, int $sensorId, string $raw, ?float $numeric): void {
    $pdo->prepare('INSERT INTO sensor_data (sensor_id, raw_value, numeric_value) VALUES (?,?,?)')
        ->execute([$sensorId, $raw, $numeric]);
}
