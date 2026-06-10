<?php
header("Content-Type: text/plain");

$conn = new mysqli(
    "178.33.122.21", 
    "dade64253", 
    "hbxfdIxJRzZPd5nq3wEuxuyF", 
    "hangardb_dade64253"
    );

$res = $conn->query("SELECT etat FROM g8b_etat_actuel WHERE id = 1");
$row = $res->fetch_assoc();

echo $row['etat'];
?>