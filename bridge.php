<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: connexion.php");
    exit;
}

// Chemin vers l'interpréteur Python à utiliser pour lancer le pont série.
$pythonExe = 'C:\\Users\\chatt\\AppData\\Local\\Python\\pythoncore-3.14-64\\python.exe';
$scriptPath = __DIR__ . '\\test.py';
$pidFile = __DIR__ . '\\bridge.pid';
$logFile = __DIR__ . '\\bridge.log';
$errLogFile = __DIR__ . '\\bridge_error.log';

function bridgePid(string $pidFile): ?int {
    if (!file_exists($pidFile)) {
        return null;
    }
    $pid = trim(file_get_contents($pidFile));
    return ctype_digit($pid) ? (int) $pid : null;
}

function bridgeIsRunning(?int $pid): bool {
    if (!$pid) {
        return false;
    }
    $output = shell_exec("tasklist /FI \"PID eq $pid\" /FO CSV /NH 2>NUL");
    return $output !== null && stripos($output, 'python.exe') !== false;
}

function tailFile(string $path, int $lines = 30): string {
    if (!file_exists($path)) {
        return "";
    }
    $content = file($path, FILE_IGNORE_NEW_LINES);
    return implode("\n", array_slice($content, -$lines));
}

$message = "";
$pid = bridgePid($pidFile);
$running = bridgeIsRunning($pid);

if (!$running && $pid !== null) {
    @unlink($pidFile);
    $pid = null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'start' && !$running) {
        $cmd = sprintf(
            'powershell -NoProfile -Command "(Start-Process -FilePath \'%s\' -ArgumentList \'%s\' -WindowStyle Hidden -RedirectStandardOutput \'%s\' -RedirectStandardError \'%s\' -PassThru).Id"',
            $pythonExe,
            $scriptPath,
            $logFile,
            $errLogFile
        );
        $newPid = trim((string) shell_exec($cmd));

        if (ctype_digit($newPid)) {
            file_put_contents($pidFile, $newPid);
            $pid = (int) $newPid;
            $running = true;
            $message = "Pont série démarré (PID $pid).";
        } else {
            $message = "Erreur lors du démarrage du pont série.";
        }
    } elseif ($action === 'stop' && $running) {
        shell_exec("taskkill /F /T /PID $pid 2>NUL");
        @unlink($pidFile);
        $pid = null;
        $running = false;
        $message = "Pont série arrêté.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TIVA - Pont série</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header class="navbar">
    <div class="navbar-brand">TIVA</div>
    <nav class="navbar-links">
        <a href="accueil.php">Accueil</a>
        <a href="index.php">État du bouton</a>
        <a href="bridge.php" class="active">Pont série</a>
        <span class="navbar-user">Bonjour, <?= htmlspecialchars($_SESSION['username']) ?></span>
        <a href="deconnexion.php" class="btn-link">Déconnexion</a>
    </nav>
</header>

<main>
    <section class="status-section bridge-section">
        <h1>Pont série (test.py)</h1>

        <div class="status-card <?= $running ? 'status-released' : '' ?>">
            <div class="status-indicator"></div>
            <p><?= $running ? "EN COURS" : "ARRÊTÉ" ?></p>
        </div>

        <?php if ($running): ?>
            <p class="bridge-message">PID : <?= $pid ?></p>
        <?php endif; ?>

        <?php if ($message): ?>
            <p class="bridge-message"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>

        <form method="POST" class="bridge-actions">
            <button type="submit" name="action" value="start" class="btn" <?= $running ? 'disabled' : '' ?>>Démarrer</button>
            <button type="submit" name="action" value="stop" class="btn btn-danger" <?= !$running ? 'disabled' : '' ?>>Arrêter</button>
        </form>

        <h2 class="log-title">Journal (sortie standard)</h2>
        <pre class="log-box"><?= htmlspecialchars(tailFile($logFile) ?: "Aucun journal pour le moment.") ?></pre>

        <h2 class="log-title">Journal (erreurs)</h2>
        <pre class="log-box"><?= htmlspecialchars(tailFile($errLogFile) ?: "Aucune erreur pour le moment.") ?></pre>

        <a href="accueil.php" class="back-home">&larr; Retour à l'accueil</a>
    </section>
</main>

<footer class="footer">
    <p>&copy; <?= date('Y') ?> TIVA</p>
</footer>

</body>
</html>
