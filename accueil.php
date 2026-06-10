<?php
session_start();
$loggedIn = isset($_SESSION['username']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>It's buzzing time - Accueil</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header class="navbar">
    <div class="navbar-brand">It's buzzing time</div>
    <nav class="navbar-links">
        <a href="accueil.php" class="active">Accueil</a>
        <a href="index.php">État du buzzer</a>
        <?php if ($loggedIn): ?>
            <span class="navbar-user">Bonjour, <?= htmlspecialchars($_SESSION['username']) ?></span>
            <a href="deconnexion.php" class="btn-link">Déconnexion</a>
        <?php else: ?>
            <a href="inscription.php">Inscription</a>
            <a href="connexion.php" class="btn-link">Connexion</a>
        <?php endif; ?>
    </nav>
</header>

<main>
    <section class="hero">
        <h1>Bienvenue sur It's buzzing time</h1>
        <p>Le site vous permettant de connaître en temps réel l'état de votre buzzer !</p>
    </section>

    <section class="cards">
        <a href="index.php" class="card">
            <h2>État du buzzer</h2>
            <p>Consultez en direct l'état actuel du buzzer.</p>
        </a>

        <?php if ($loggedIn): ?>

            <a href="deconnexion.php" class="card">
                <h2>Mon compte</h2>
                <p>Connecté en tant que <?= htmlspecialchars($_SESSION['username']) ?>. Cliquez ici pour vous déconnecter.</p>
            </a>
        <?php else: ?>
            <a href="connexion.php" class="card">
                <h2>Connexion</h2>
                <p>Accédez à votre espace administrateur.</p>
            </a>

            <a href="inscription.php" class="card">
                <h2>Inscription</h2>
                <p>Créez un compte pour accéder à votre espace administrateur.</p>
            </a>
        <?php endif; ?>
    </section>
</main>

<footer class="footer">
    <p>&copy; <?= date('Y') ?> It's buzzing time</p>
</footer>

</body>
</html>
