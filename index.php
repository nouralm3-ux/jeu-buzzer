<?php
session_start();
$logged = !empty($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SensorHub — Surveillance de capteurs</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<nav class="navbar">
    <div class="logo">
        &#9881; <span>Sensor</span>Hub
    </div>
    <div class="nav-links">
        <a href="index.php" class="active">Accueil</a>
        <?php if ($logged): ?>
            <a href="dashboard.php">Dashboard</a>
            <a href="logout.php" class="btn btn-outline">Déconnexion</a>
        <?php else: ?>
            <a href="login.php" class="btn btn-primary">Connexion</a>
        <?php endif; ?>
    </div>
</nav>

<section class="hero">
    <h1>Surveillance de capteurs<br><span class="highlight">en temps réel</span></h1>
    <p>Connectez, lisez et gérez vos capteurs séries depuis une interface web moderne. Visualisez les données et gardez le contrôle.</p>
    <div class="hero-actions">
        <?php if ($logged): ?>
            <a href="dashboard.php" class="btn btn-primary">&#9658; Ouvrir le dashboard</a>
        <?php else: ?>
            <a href="login.php" class="btn btn-primary">&#9658; Commencer</a>
            <a href="#features" class="btn btn-outline">En savoir plus</a>
        <?php endif; ?>
    </div>
</section>

<div id="features" class="features">
    <div class="card">
        <div class="card-icon">&#128268;</div>
        <h3>Communication série</h3>
        <p>Lecture et écriture sur ports COM via PHP Direct IO. Compatible Windows et Unix.</p>
    </div>
    <div class="card">
        <div class="card-icon">&#128202;</div>
        <h3>Visualisation en temps réel</h3>
        <p>Graphiques dynamiques mis à jour automatiquement avec les dernières données reçues.</p>
    </div>
    <div class="card">
        <div class="card-icon">&#128275;</div>
        <h3>Accès sécurisé</h3>
        <p>Authentification par mot de passe hashé. Sessions sécurisées pour protéger vos données.</p>
    </div>
    <div class="card">
        <div class="card-icon">&#128190;</div>
        <h3>Historique MySQL</h3>
        <p>Toutes les données capteurs sont enregistrées en base MySQL pour analyse ultérieure.</p>
    </div>
</div>

<footer>
    &copy; <?= date('Y') ?> SensorHub &mdash; Projet PHP + MySQL + Port Série
</footer>

</body>
</html>
