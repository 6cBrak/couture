<?php
session_start();
require_once("connexion.php");

$titre = htmlspecialchars(trim(addslashes($_POST['titre'])));
$adresse = htmlspecialchars(trim(addslashes($_POST['adresse'])));
$phone = htmlspecialchars(trim(addslashes($_POST['phone'])));
$num = htmlspecialchars(trim(addslashes($_POST['num'])));


$req="update PLUS set TITRE = '$titre',ADRESSE = '$adresse' ,PHONE = '$phone',NUM_NATIONAL = '$num' ";
mysqli_query($connexion,$req) or die(mysqli_error());


echo 1;
   
mysqli_close($connexion);
?>