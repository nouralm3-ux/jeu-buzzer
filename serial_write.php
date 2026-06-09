<?php
session_start();
require_once 'includes/auth.php';
requireLogin();
require_once 'includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Méthode non autorisée.']);
    exit;
}

$sensorId = (int)($_POST['sensor_id'] ?? 0);
$message  = $_POST['message'] ?? '';

if (!$sensorId || $message === '') {
    echo json_encode(['error' => 'Paramètres manquants.']);
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

if (!function_exists('dio_open')) {
    // Simulate write
    echo json_encode(['sent' => true, 'bytes' => strlen($message), 'simulated' => true]);
    exit;
}

$fd = @dio_open($sensor['port'], O_RDWR | O_NOCTTY | O_NONBLOCK);
if ($fd === false) {
    echo json_encode(['error' => "Impossible d'ouvrir le port " . $sensor['port']]);
    exit;
}

dio_tcsetattr($fd, [
    'baud'   => (int)$sensor['baud_rate'],
    'bits'   => 8,
    'stop'   => 1,
    'parity' => 0
]);

$written = dio_write($fd, $message . "\r\n");
dio_close($fd);

echo json_encode(['sent' => true, 'bytes' => $written]);
