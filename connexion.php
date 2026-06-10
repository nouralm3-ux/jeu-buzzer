<?php
session_start();

if (isset($_SESSION['username'])) {
    header("Location: accueil.php");
    exit;
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $conn = new mysqli(
        "178.33.122.21",
        "dade64253",
        "hbxfdIxJRzZPd5nq3wEuxuyF",
        "hangardb_dade64253"
    );

    if ($conn->connect_error) {
        $error = "Erreur de connexion à la base de données.";
    } else {
        $stmt = $conn->prepare("SELECT password_hash FROM g8b_users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $conn->close();

        if ($row && password_verify($password, $row['password_hash'])) {
            $_SESSION['username'] = $username;
            header("Location: accueil.php");
            exit;
        } else {
            $error = "Identifiant ou mot de passe incorrect.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TIVA - Connexion</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header class="navbar">
    <div class="navbar-brand">TIVA</div>
    <nav class="navbar-links">
        <a href="accueil.php">Accueil</a>
        <a href="index.php">État du bouton</a>
        <a href="inscription.php">Inscription</a>
        <a href="connexion.php" class="btn-link active">Connexion</a>
    </nav>
</header>

<div class="login-wrapper">
    <form method="POST" class="login-form">
        <h1>Connexion</h1>

        <?php if ($error): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <label for="username">Identifiant</label>
        <input type="text" id="username" name="username" required autofocus>

        <label for="password">Mot de passe</label>
        <input type="password" id="password" name="password" required>

        <button type="submit">Se connecter</button>

        <span class="form-hint">Pas encore de compte ? <a href="inscription.php">Inscrivez-vous</a></span>

        <a href="accueil.php" class="back-link">&larr; Retour à l'accueil</a>
    </form>
</div>

</body>
</html>
