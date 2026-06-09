<?php
/**
 * Endpoint appelé par bridge.py pour pousser les données série dans la DB.
 */
require_once 'includes/config.php';

define('PUSH_KEY', 'tiva_bridge_2024');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (($_POST['key'] ?? '') !== PUSH_KEY) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$sensorId = (int)($_POST['sensor_id'] ?? 0);
$data     = trim($_POST['data'] ?? '');

if (!$sensorId || $data === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Missing sensor_id or data']);
    exit;
}

$pdo  = getDB();
$stmt = $pdo->prepare('SELECT id FROM sensors WHERE id = ? AND active = 1');
$stmt->execute([$sensorId]);
if (!$stmt->fetch()) {
    http_response_code(404);
    echo json_encode(['error' => 'Sensor not found or inactive']);
    exit;
}

$numeric = null;
if (preg_match('/[-+]?\d+(\.\d+)?/', $data, $m)) {
    $numeric = (float)$m[0];
}

$pdo->prepare('INSERT INTO sensor_data (sensor_id, raw_value, numeric_value) VALUES (?,?,?)')
    ->execute([$sensorId, $data, $numeric]);

echo json_encode(['ok' => true, 'numeric' => $numeric]);
