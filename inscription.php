<?php
session_start();

if (isset($_SESSION['username'])) {
    header("Location: accueil.php");
    exit;
}

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if ($username === '' || $password === '') {
        $error = "Veuillez remplir tous les champs.";
    } elseif (strlen($password) < 6) {
        $error = "Le mot de passe doit contenir au moins 6 caractères.";
    } elseif ($password !== $confirm) {
        $error = "Les mots de passe ne correspondent pas.";
    } else {
        $conn = new mysqli(
            "178.33.122.21",
            "dade64253",
            "hbxfdIxJRzZPd5nq3wEuxuyF",
            "hangardb_dade64253"
        );

        if ($conn->connect_error) {
            $error = "Erreur de connexion à la base de données.";
        } else {
            $stmt = $conn->prepare("SELECT id FROM g8b_users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $error = "Ce nom d'utilisateur est déjà pris.";
                $stmt->close();
            } else {
                $stmt->close();

                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO g8b_users (username, password_hash) VALUES (?, ?)");
                $stmt->bind_param("ss", $username, $hash);

                if ($stmt->execute()) {
                    $success = "Compte créé avec succès. Vous pouvez maintenant vous connecter.";
                } else {
                    $error = "Erreur lors de la création du compte.";
                }

                $stmt->close();
            }

            $conn->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TIVA - Inscription</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header class="navbar">
    <div class="navbar-brand">TIVA</div>
    <nav class="navbar-links">
        <a href="accueil.php">Accueil</a>
        <a href="index.php">État du bouton</a>
        <a href="inscription.php" class="active">Inscription</a>
        <a href="connexion.php" class="btn-link">Connexion</a>
    </nav>
</header>

<div class="login-wrapper">
    <form method="POST" class="login-form">
        <h1>Créer un compte</h1>

        <?php if ($error): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if ($success): ?>
            <p class="success"><?= htmlspecialchars($success) ?></p>
        <?php endif; ?>

        <label for="username">Identifiant</label>
        <input type="text" id="username" name="username" value="<?= htmlspecialchars($username ?? '') ?>" required autofocus>

        <label for="password">Mot de passe</label>
        <input type="password" id="password" name="password" required>

        <label for="confirm">Confirmer le mot de passe</label>
        <input type="password" id="confirm" name="confirm" required>

        <button type="submit">S'inscrire</button>

        <span class="form-hint">Déjà un compte ? <a href="connexion.php">Connectez-vous</a></span>

        <a href="accueil.php" class="back-link">&larr; Retour à l'accueil</a>
    </form>
</div>

</body>
</html>
