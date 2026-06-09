<?php
session_start();
if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

require_once 'includes/auth.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Veuillez remplir tous les champs.';
    } elseif (login($username, $password)) {
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Identifiant ou mot de passe incorrect.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion — SensorHub</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="login-wrapper">
    <div class="login-box">
        <div style="text-align:center; margin-bottom:1.5rem; font-size:2.5rem;">&#9881;</div>
        <h2>Connexion</h2>
        <p class="subtitle">Accédez à votre tableau de bord de capteurs.</p>

        <?php if ($error): ?>
            <div class="alert alert-error">&#9888; <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="form-group">
                <label for="username">Identifiant</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    placeholder="admin"
                    value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                    required
                    autocomplete="username"
                >
            </div>
            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="••••••••"
                    required
                    autocomplete="current-password"
                >
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center; padding: 0.75rem;">
                Se connecter
            </button>
        </form>

        <p style="text-align:center; margin-top:1.5rem; font-size:0.8rem; color:var(--text-muted);">
            <a href="index.php">&#8592; Retour à l'accueil</a>
        </p>

        <div class="alert alert-success" style="margin-top:1.5rem; font-size:0.8rem;">
            Compte par défaut : <strong>admin</strong> / <strong>admin123</strong>
        </div>
    </div>
</div>

</body>
</html>
