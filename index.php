<?php
session_start();
$loggedIn = isset($_SESSION['username']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TIVA - État du bouton</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header class="navbar">
    <div class="navbar-brand">TIVA</div>
    <nav class="navbar-links">
        <a href="accueil.php">Accueil</a>
        <a href="index.php" class="active">État du bouton</a>
        <?php if ($loggedIn): ?>
            <a href="bridge.php">Pont série</a>
            <span class="navbar-user">Bonjour, <?= htmlspecialchars($_SESSION['username']) ?></span>
            <a href="deconnexion.php" class="btn-link">Déconnexion</a>
        <?php else: ?>
            <a href="inscription.php">Inscription</a>
            <a href="connexion.php" class="btn-link">Connexion</a>
        <?php endif; ?>
    </nav>
</header>

<main>
    <section class="status-section">
        <h1>État du bouton</h1>

        <div id="status-card" class="status-card">
            <div class="status-indicator" id="status-indicator"></div>
            <p id="etat">Chargement...</p>
        </div>

        <a href="accueil.php" class="back-home">&larr; Retour à l'accueil</a>
    </section>
</main>

<footer class="footer">
    <p>&copy; <?= date('Y') ?> TIVA</p>
</footer>

<script>
let last = null;

function update() {
    fetch("etat.php?cache=" + Date.now())
        .then(r => r.text())
        .then(v => {
            if (v === last) return;
            last = v;

            const etat = document.getElementById("etat");
            const card = document.getElementById("status-card");

            if (v == "1") {
                etat.innerText = "APPUYÉ";
                card.className = "status-card status-pressed";
            } else {
                etat.innerText = "RELÂCHÉ";
                card.className = "status-card status-released";
            }
        });
}

setInterval(update, 1000);
update();
</script>

</body>
</html>
