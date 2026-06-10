<!DOCTYPE html>
<html>
<head>
    <title>TIVA</title>
</head>
<body>

<h1>Etat du bouton</h1>
<h2 id="etat">Chargement...</h2>

<script>
let last = null;

function update() {
    fetch("etat.php?cache=" + Date.now())
        .then(r => r.text())
        .then(v => {

            if (v === last) return;
            last = v;

            document.getElementById("etat").innerText =
                (v == "1") ? "APPUYÉ" : "RELÂCHÉ";
        });
}

setInterval(update, 1000);
update();
</script>

</body>
</html>